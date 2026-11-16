<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\AuthService;
use App\Auth\Controller\SignupController;
use App\Auth\Controller\SignupDeps;
use App\Auth\Form\SignupForm;
use App\Invoice\AppConstants;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserRbacLinkRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Header;
use Yiisoft\Rbac\Manager;
use Yiisoft\Router\CurrentRoute;

/**
 * The generic /signup action: who is turned away, what happens before the email is confirmed.
 * Built without its constructor (which reads OAuth settings) and with only the collaborators the
 * tested path touches.
 */
#[Test]
final class SignupControllerTest
{
    /** @param array<string, mixed> $extra properties to set besides the setting repository and web service */
    private function makeController(string $stopSigningUp = '', string $turnstileSecret = '', array $extra = []): SignupController
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('stop_signing_up')->andReturn($stopSigningUp);
        $sR->shouldReceive('getSetting')->with('turnstile_secret_key')->andReturn($turnstileSecret);
        $sR->shouldReceive('getSetting')->andReturn('');

        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->andReturnUsing(
            static fn (string $url): ResponseInterface => new HttpFactory()
                ->createResponse(302)
                ->withHeader(Header::LOCATION, $url),
        );

        $reflection = new \ReflectionClass(SignupController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $clientIp = new \App\Service\ClientIpResolver([]);
        foreach (['sR' => $sR, 'webService' => $webService, 'clientIp' => $clientIp, ...$extra] as $property => $value) {
            $reflection->getProperty($property)->setValue($controller, $value);
        }
        return $controller;
    }

    private function authService(bool $guest): AuthService
    {
        $authService = m::mock(AuthService::class);
        $authService->shouldReceive('isGuest')->andReturn($guest);
        return $authService;
    }

    private function post(): ServerRequestInterface
    {
        return new HttpFactory()
            ->createServerRequest('POST', 'https://example.test/en/signup')
            ->withParsedBody(['Signup' => ['login' => 'bot']]);
    }

    private function signup(SignupController $controller, bool $guest = true, ?FormHydrator $hydrator = null): ResponseInterface
    {
        $deps = m::mock(SignupDeps::class);
        $deps->uR = m::mock(\App\User\UserRepository::class);
        return $controller->signup(
            $this->authService($guest),
            m::mock(CurrentRoute::class),
            $hydrator ?? m::mock(FormHydrator::class)->shouldReceive('populateFromPostAndValidate')->never()->getMock(),
            $this->post(),
            m::mock(SignupForm::class),
            $deps,
        );
    }

    public function aLoggedInUserIsSentAway(): void
    {
        Assert::same($this->signup($this->makeController(), guest: false)->getHeaderLine(Header::LOCATION), 'site/index');
    }

    public function stopSigningUpClosesTheRouteItselfNotJustTheNavbarLink(): void
    {
        Assert::same($this->signup($this->makeController(stopSigningUp: '1'))->getHeaderLine(Header::LOCATION), 'site/index');
    }

    public function aPostWithoutATurnstileTokenFailsBeforeAnyAccountIsCreated(): void
    {
        $controller = $this->makeController(turnstileSecret: 'real-secret');
        // open_banking_provider is read first and is '' here, so no provider URL is built

        Assert::same($this->signup($controller)->getHeaderLine(Header::LOCATION), 'site/signupfailed');
    }

    public function aPostIsRefusedWhenNoTurnstileSecretIsConfigured(): void
    {
        // open_banking_provider is read first and is '' here, so no provider URL is built
        Assert::same(
            $this->signup($this->makeController(turnstileSecret: ''))->getHeaderLine(Header::LOCATION),
            'site/signupfailed',
        );
    }

    private function assignRoleAndVerify(SignupController $controller, int $userId, string $role): bool
    {
        return (bool) new \ReflectionMethod($controller, 'assignRoleAndVerify')->invoke($controller, $userId, $role);
    }

    public function theRoleIsAssignedAtSignupBeforeTheEmailIsConfirmed(): void
    {
        // Characterisation: signup() assigns the RBAC role straight away; only userinv.active is
        // held back until the link is clicked. Anything that checks the role (not userinv.active)
        // already treats the unconfirmed account as an Observer.
        $manager = m::mock(Manager::class);
        $manager->shouldReceive('revokeAll')->once()->with('42');
        $manager->shouldReceive('assign')->once()->with(AppConstants::ROLE_OBSERVER, '42');
        $manager->shouldReceive('getRolesByUserId')->with('42')->andReturn(['observer' => new \stdClass()]);
        $uiR = m::mock(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->with(42)->andReturn(null);

        $controller = $this->makeController(extra: [
            'manager' => $manager,
            'uiR' => $uiR,
            'urlR' => m::mock(UserRbacLinkRepository::class),
            'logger' => m::mock(LoggerInterface::class)->shouldIgnoreMissing(),
        ]);

        Assert::true($this->assignRoleAndVerify($controller, 42, AppConstants::ROLE_OBSERVER));
    }
}
