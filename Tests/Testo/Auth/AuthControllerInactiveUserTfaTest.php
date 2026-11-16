<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\AuthService;
use App\Auth\Controller\AuthController;
use App\Auth\Form\LoginForm;
use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\AppConstants;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Http\Header;
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Role;
use Yiisoft\Session\SessionInterface;
use Yiisoft\User\Login\Cookie\CookieLogin;

/**
 * The login decision after a correct password, for accounts that have and have not been activated
 * (userinv.active, set when the emailed confirmation link is clicked). Drives
 * AuthController::resolveLoginResponse() directly, with the controller built from mocks.
 */
#[Test]
final class AuthControllerInactiveUserTfaTest
{
    /** @var list<string> every value the controller stored under 'tfa_verified' */
    private array $tfaVerifiedWrites = [];

    /** @var int how many times the controller signed the visitor out */
    private int $logouts = 0;

    private function makeController(string $enableTfa, bool $admin): AuthController
    {
        $this->tfaVerifiedWrites = [];
        $this->logouts = 0;

        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('enable_tfa')->andReturn($enableTfa);
        $sR->shouldReceive('getSetting')->andReturn('');

        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->andReturnUsing(
            static fn (string $url): ResponseInterface => new HttpFactory()
                ->createResponse(302)
                ->withHeader(Header::LOCATION, $url),
        );

        $identity = m::mock(Identity::class);
        $identity->shouldReceive('getUserId')->andReturn(7);
        $authService = m::mock(AuthService::class);
        $authService->shouldReceive('getIdentity')->andReturn($identity);
        $authService->shouldReceive('logout')->andReturnUsing(function (): bool {
            ++$this->logouts;
            return true;
        });

        $manager = m::mock(Manager::class);
        $manager->shouldReceive('getRolesByUserId')->andReturn(
            $admin ? [new Role(AppConstants::ROLE_ADMIN)] : [new Role(AppConstants::ROLE_OBSERVER)],
        );

        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('set')->andReturnUsing(function (string $key, mixed $value): void {
            if ($key === 'tfa_verified' && $value === true) {
                $this->tfaVerifiedWrites[] = $key;
            }
        });
        $session->shouldReceive('get')->andReturn(null);
        $session->shouldReceive('regenerateId')->andReturnNull();

        $reflection = new \ReflectionClass(AuthController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $values = [
            'authService' => $authService,
            'webService' => $webService,
            'manager' => $manager,
            'session' => $session,
            'sR' => $sR,
        ];
        foreach ($values as $property => $value) {
            $reflection->getProperty($property)->setValue($controller, $value);
        }
        return $controller;
    }

    private function userInv(bool $active): UserInvRepository
    {
        $user = m::mock(User::class);
        $user->shouldReceive('is2FAEnabled')->andReturn(false);
        $userInv = m::mock(UserInv::class);
        $userInv->shouldReceive('getUser')->andReturn($user);
        $userInv->shouldReceive('getActive')->andReturn($active);
        $uiR = m::mock(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->andReturn($userInv);
        return $uiR;
    }

    private function resolve(AuthController $controller, bool $active): string
    {
        $tR = m::mock(TokenRepository::class)->shouldIgnoreMissing();
        $cookieLogin = m::mock(CookieLogin::class);
        $loginForm = m::mock(LoginForm::class);
        $loginForm->shouldReceive('getPropertyValue')->andReturn(false);

        /** @var ?ResponseInterface $response */
        $response = new \ReflectionMethod($controller, 'resolveLoginResponse')
            ->invoke($controller, $loginForm, $cookieLogin, $this->userInv($active), $tR);

        return $response?->getHeaderLine(Header::LOCATION) ?? '';
    }

    public function withTfaOnAnUnconfirmedAccountCannotEnrolItsOwnAuthenticator(): void
    {
        $location = $this->resolve($this->makeController('1', admin: false), active: false);

        Assert::false($location === 'auth/showSetup');
        Assert::false($location === 'auth/verifyLogin');
    }

    public function withTfaOnAnUnconfirmedAccountIsToldTheAdminMustMakeItActive(): void
    {
        // no unused email-verification token exists in this fixture, so it is the admin branch
        Assert::same(
            $this->resolve($this->makeController('1', admin: false), active: false),
            'site/adminmustmakeactive',
        );
    }

    public function anUnconfirmedAccountIsNeverMarkedTfaVerified(): void
    {
        foreach (['0', '1'] as $enableTfa) {
            $controller = $this->makeController($enableTfa, admin: false);
            $this->resolve($controller, active: false);

            Assert::same($this->tfaVerifiedWrites, []);
        }
    }

    public function withTfaOffAnUnconfirmedAccountIsNotLetIn(): void
    {
        Assert::same(
            $this->resolve($this->makeController('0', admin: false), active: false),
            'site/adminmustmakeactive',
        );
    }

    public function anUnconfirmedAccountDoesNotKeepTheSessionTheCorrectPasswordCreated(): void
    {
        // LoginForm logs the identity in while validating the password; the controller must undo
        // that for an inactive account, with TFA on or off, before sending it to the public page.
        foreach (['0', '1'] as $enableTfa) {
            $controller = $this->makeController($enableTfa, admin: false);
            $this->resolve($controller, active: false);

            Assert::same($this->logouts, 1);
        }
    }

    public function aConfirmedAccountOrAnAdminIsNotSignedOut(): void
    {
        foreach ([['0', false, true], ['1', false, true], ['0', true, false], ['1', true, true]] as [$enableTfa, $admin, $active]) {
            $controller = $this->makeController($enableTfa, admin: $admin);
            $this->resolve($controller, active: $active);

            Assert::same($this->logouts, 0);
        }
    }

    public function withTfaOnAConfirmedAccountStillGoesThroughTfaSetup(): void
    {
        Assert::same($this->resolve($this->makeController('1', admin: false), active: true), 'auth/showSetup');
    }

    public function anAdminStillGoesThroughTfaEvenIfNotMarkedActive(): void
    {
        Assert::same($this->resolve($this->makeController('0', admin: true), active: false), 'auth/showSetup');
    }
}
