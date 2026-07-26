<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\AuthService;
use App\Auth\IdentityRepository;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use App\User\UserRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\User\CurrentUser;

/**
 * Covers AuthService: login/oauthLogin credential checks and the resulting
 * CurrentUser::login() call, logout()'s cookie-login-key regeneration and
 * conditional IdentityRepository::save() (only for our own Identity
 * implementation, not arbitrary IdentityInterface identities), and the
 * simple getIdentity()/isGuest() pass-throughs.
 */
#[Test]
final class AuthServiceTest
{
    private function makeService(
        CurrentUser $currentUser,
        ?UserRepository $userR = null,
        ?IdentityRepository $identityR = null,
    ): AuthService {
        $userR = $userR ?? m::mock(UserRepository::class);
        $identityR = $identityR ?? m::mock(IdentityRepository::class);
        return new AuthService($currentUser, $userR, $identityR);
    }

    public function loginSucceedsWithValidCredentialsAndLogsInIdentity(): void
    {
        $identity = m::mock(Identity::class);

        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('validatePassword');
        $e->once()->with('correct horse battery staple')->andReturn(true);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $user->shouldReceive('getIdentity');
        $e2->once()->andReturn($identity);

        $userR = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $userR->shouldReceive('findByLoginWithAuthIdentity');
        $e3->once()->with('jane')->andReturn($user);

        $currentUser = m::mock(CurrentUser::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $currentUser->shouldReceive('login');
        $e4->once()->with($identity)->andReturn(true);

        $service = $this->makeService($currentUser, $userR);

        Assert::true($service->login('jane', 'correct horse battery staple'));
    }

    public function loginFailsWhenUserNotFound(): void
    {
        $userR = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $userR->shouldReceive('findByLoginWithAuthIdentity');
        $e->once()->with('unknown')->andReturn(null);

        $currentUser = m::mock(CurrentUser::class);
        $currentUser->shouldNotReceive('login');

        $service = $this->makeService($currentUser, $userR);

        Assert::false($service->login('unknown', 'whatever'));
    }

    public function loginFailsWhenPasswordInvalid(): void
    {
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('validatePassword');
        $e->once()->with('wrong password')->andReturn(false);
        $user->shouldNotReceive('getIdentity');

        $userR = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $userR->shouldReceive('findByLoginWithAuthIdentity');
        $e2->once()->with('jane')->andReturn($user);

        $currentUser = m::mock(CurrentUser::class);
        $currentUser->shouldNotReceive('login');

        $service = $this->makeService($currentUser, $userR);

        Assert::false($service->login('jane', 'wrong password'));
    }

    public function oauthLoginSucceedsWhenUserFound(): void
    {
        $identity = m::mock(Identity::class);

        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('getIdentity');
        $e->once()->andReturn($identity);
        $user->shouldNotReceive('validatePassword');

        $userR = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $userR->shouldReceive('findByLoginWithAuthIdentity');
        $e2->once()->with('jane')->andReturn($user);

        $currentUser = m::mock(CurrentUser::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $currentUser->shouldReceive('login');
        $e3->once()->with($identity)->andReturn(true);

        $service = $this->makeService($currentUser, $userR);

        Assert::true($service->oauthLogin('jane'));
    }

    public function oauthLoginFailsWhenUserNotFound(): void
    {
        $userR = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $userR->shouldReceive('findByLoginWithAuthIdentity');
        $e->once()->with('unknown')->andReturn(null);

        $currentUser = m::mock(CurrentUser::class);
        $currentUser->shouldNotReceive('login');

        $service = $this->makeService($currentUser, $userR);

        Assert::false($service->oauthLogin('unknown'));
    }

    public function logoutRegeneratesCookieKeyAndSavesIdentityWhenIdentityIsOurs(): void
    {
        $identity = m::mock(Identity::class);
        /** @var \Mockery\Expectation $e */
        $e = $identity->shouldReceive('regenerateCookieLoginKey');
        $e->once()->andReturn('new-key');

        $currentUser = m::mock(CurrentUser::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $currentUser->shouldReceive('getIdentity');
        $e2->once()->andReturn($identity);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $currentUser->shouldReceive('logout');
        $e3->once()->andReturn(true);

        $identityR = m::mock(IdentityRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $identityR->shouldReceive('save');
        $e4->once()->with($identity);

        $service = $this->makeService($currentUser, null, $identityR);

        Assert::true($service->logout());
    }

    public function logoutDoesNotSaveWhenIdentityIsNotOurIdentityClass(): void
    {
        $identity = m::mock(IdentityInterface::class);

        $currentUser = m::mock(CurrentUser::class);
        /** @var \Mockery\Expectation $e */
        $e = $currentUser->shouldReceive('getIdentity');
        $e->once()->andReturn($identity);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $currentUser->shouldReceive('logout');
        $e2->once()->andReturn(false);

        $identityR = m::mock(IdentityRepository::class);
        $identityR->shouldNotReceive('save');

        $service = $this->makeService($currentUser, null, $identityR);

        Assert::false($service->logout());
    }

    public function getIdentityReturnsCurrentUserIdentity(): void
    {
        $identity = m::mock(IdentityInterface::class);

        $currentUser = m::mock(CurrentUser::class);
        /** @var \Mockery\Expectation $e */
        $e = $currentUser->shouldReceive('getIdentity');
        $e->once()->andReturn($identity);

        $service = $this->makeService($currentUser);

        Assert::same($identity, $service->getIdentity());
    }

    public function isGuestReturnsTrueWhenCurrentUserIsGuest(): void
    {
        $currentUser = m::mock(CurrentUser::class);
        /** @var \Mockery\Expectation $e */
        $e = $currentUser->shouldReceive('isGuest');
        $e->once()->andReturn(true);

        $service = $this->makeService($currentUser);

        Assert::true($service->isGuest());
    }

    public function isGuestReturnsFalseWhenCurrentUserIsAuthenticated(): void
    {
        $currentUser = m::mock(CurrentUser::class);
        /** @var \Mockery\Expectation $e */
        $e = $currentUser->shouldReceive('isGuest');
        $e->once()->andReturn(false);

        $service = $this->makeService($currentUser);

        Assert::false($service->isGuest());
    }
}
