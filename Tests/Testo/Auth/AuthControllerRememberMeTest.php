<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\AuthService;
use App\Auth\Controller\AuthController;
use App\Auth\Form\LoginForm;
use App\Infrastructure\Persistence\User\User;
use App\Service\WebControllerService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\Login\Cookie\CookieLogin;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;

/**
 * Covers the "Remember Me" wiring added to the TFA path:
 * AuthController::handleTfaPath() stashing the choice in session (it has
 * no CookieLogin of its own to act on immediately -- the TFA verify step
 * is a separate HTTP request), and TwoFactorAuth::applyRememberMe()
 * reading it back and applying it once TFA actually succeeds.
 *
 * Built via newInstanceWithoutConstructor() + ReflectionProperty, matching
 * OrderServiceTest/InvRecurringServiceTest's own established technique for
 * otherwise-unsettable private state, applied here to AuthController's
 * 20-dependency constructor: only the handful of properties these two
 * private methods actually touch (session, authService, cookieLogin,
 * webService) are set, everything else stays uninitialized and untouched.
 */
#[Test]
final class AuthControllerRememberMeTest
{
    private function makeController(
        ?SessionInterface $session = null,
        ?AuthService $authService = null,
        ?CookieLogin $cookieLogin = null,
        ?WebControllerService $webService = null,
    ): AuthController {
        $reflectionClass = new ReflectionClass(AuthController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();

        $set = static function (string $name, object $value) use ($controller): void {
            $property = new ReflectionProperty(AuthController::class, $name);
            $property->setValue($controller, $value);
        };
        null !== $session && $set('session', $session);
        null !== $authService && $set('authService', $authService);
        null !== $cookieLogin && $set('cookieLogin', $cookieLogin);
        null !== $webService && $set('webService', $webService);

        return $controller;
    }

    private function handleTfaPath(AuthController $controller, string $userId, User $user, LoginForm $loginForm): ResponseInterface
    {
        $reflectionMethod = new ReflectionMethod(AuthController::class, 'handleTfaPath');
        /** @var ResponseInterface */
        return $reflectionMethod->invoke($controller, $userId, $user, $loginForm);
    }

    private function applyRememberMe(AuthController $controller, ResponseInterface $response): ResponseInterface
    {
        $reflectionMethod = new ReflectionMethod(AuthController::class, 'applyRememberMe');
        /** @var ResponseInterface */
        return $reflectionMethod->invoke($controller, $response);
    }

    private function makeLoginForm(bool $rememberMe): LoginForm
    {
        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $loginForm = new LoginForm($authService, $translator);

        $rememberMeProperty = new ReflectionProperty(LoginForm::class, 'rememberMe');
        $rememberMeProperty->setValue($loginForm, $rememberMe);

        return $loginForm;
    }

    // ─── handleTfaPath() ────────────────────────────────────────────────

    public function handleTfaPathStashesRememberMeTrueInSessionWhenChecked(): void
    {
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $s1 = $session->shouldReceive('set');
        $s1->once()->with('tfa_verified', false);
        $s2 = $session->shouldReceive('set');
        $s2->once()->with('remember_me', true);
        $s3 = $session->shouldReceive('set');
        $s3->once()->with('verified_2fa_user_id', '7');

        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var ResponseInterface&m\MockInterface $response */
        $response = m::mock(ResponseInterface::class);
        $w = $webService->shouldReceive('getRedirectResponse');
        $w->once()->with('auth/verifyLogin')->andReturn($response);

        $controller = $this->makeController(session: $session, webService: $webService);

        $user = new User('login', 'user@example.com', 'password');
        $user->set2FAEnabled(true);

        $result = $this->handleTfaPath($controller, '7', $user, $this->makeLoginForm(true));

        Assert::same($response, $result);
    }

    public function handleTfaPathStashesRememberMeFalseInSessionWhenUnchecked(): void
    {
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $s1 = $session->shouldReceive('set');
        $s1->once()->with('tfa_verified', false);
        $s2 = $session->shouldReceive('set');
        $s2->once()->with('remember_me', false);
        $s3 = $session->shouldReceive('set');
        $s3->once()->with('pending_2fa_user_id', '9');

        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var ResponseInterface&m\MockInterface $response */
        $response = m::mock(ResponseInterface::class);
        $w = $webService->shouldReceive('getRedirectResponse');
        $w->once()->with('auth/showSetup')->andReturn($response);

        $controller = $this->makeController(session: $session, webService: $webService);

        $user = new User('login', 'user@example.com', 'password');
        $user->set2FAEnabled(false);

        $result = $this->handleTfaPath($controller, '9', $user, $this->makeLoginForm(false));

        Assert::same($response, $result);
    }

    // ─── applyRememberMe() (via TwoFactorAuth, hosted on AuthController) ──

    public function applyRememberMeAddsTheCookieWhenSessionFlagIsTrueAndIdentityIsEligible(): void
    {
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $g = $session->shouldReceive('get');
        $g->once()->with('remember_me', false)->andReturn(true);
        $r = $session->shouldReceive('remove');
        $r->once()->with('remember_me');

        /** @var CookieLoginIdentityInterface&m\MockInterface $identity */
        $identity = m::mock(CookieLoginIdentityInterface::class);

        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        $gi = $authService->shouldReceive('getIdentity');
        $gi->once()->andReturn($identity);

        /** @var ResponseInterface&m\MockInterface $response */
        $response = m::mock(ResponseInterface::class);
        /** @var ResponseInterface&m\MockInterface $withCookieResponse */
        $withCookieResponse = m::mock(ResponseInterface::class);

        /** @var CookieLogin&m\MockInterface $cookieLogin */
        $cookieLogin = m::mock(CookieLogin::class);
        $a = $cookieLogin->shouldReceive('addCookie');
        $a->once()->with($identity, $response)->andReturn($withCookieResponse);

        $controller = $this->makeController(session: $session, authService: $authService, cookieLogin: $cookieLogin);

        $result = $this->applyRememberMe($controller, $response);

        Assert::same($withCookieResponse, $result);
    }

    public function applyRememberMeLeavesTheResponseUntouchedWhenSessionFlagIsFalse(): void
    {
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $g = $session->shouldReceive('get');
        $g->once()->with('remember_me', false)->andReturn(false);
        $r = $session->shouldReceive('remove');
        $r->once()->with('remember_me');

        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        $authService->shouldNotReceive('getIdentity');

        /** @var CookieLogin&m\MockInterface $cookieLogin */
        $cookieLogin = m::mock(CookieLogin::class);
        $cookieLogin->shouldNotReceive('addCookie');

        /** @var ResponseInterface&m\MockInterface $response */
        $response = m::mock(ResponseInterface::class);

        $controller = $this->makeController(session: $session, authService: $authService, cookieLogin: $cookieLogin);

        $result = $this->applyRememberMe($controller, $response);

        Assert::same($response, $result);
    }

    public function applyRememberMeLeavesTheResponseUntouchedWhenIdentityIsNotCookieLoginEligible(): void
    {
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $g = $session->shouldReceive('get');
        $g->once()->with('remember_me', false)->andReturn(true);
        $r = $session->shouldReceive('remove');
        $r->once()->with('remember_me');

        // A plain IdentityInterface that doesn't also implement
        // CookieLoginIdentityInterface -- addCookie() requires the latter
        // specifically.
        /** @var IdentityInterface&m\MockInterface $identity */
        $identity = m::mock(IdentityInterface::class);

        /** @var AuthService&m\MockInterface $authService */
        $authService = m::mock(AuthService::class);
        $gi = $authService->shouldReceive('getIdentity');
        $gi->once()->andReturn($identity);

        /** @var CookieLogin&m\MockInterface $cookieLogin */
        $cookieLogin = m::mock(CookieLogin::class);
        $cookieLogin->shouldNotReceive('addCookie');

        /** @var ResponseInterface&m\MockInterface $response */
        $response = m::mock(ResponseInterface::class);

        $controller = $this->makeController(session: $session, authService: $authService, cookieLogin: $cookieLogin);

        $result = $this->applyRememberMe($controller, $response);

        Assert::same($response, $result);
    }
}
