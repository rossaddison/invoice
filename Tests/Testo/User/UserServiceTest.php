<?php

declare(strict_types=1);

namespace Tests\Testo\User;

use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use App\User\UserRepository;
use App\User\UserService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\User\CurrentUser;

/**
 * Covers UserService: getUser's lookup by the resolved user id (via
 * Identity::getUserId(), not CurrentUser::getId() — the identity table's
 * own row id, a different value) and the anonymous "no identity" case, and
 * hasPermission's delegation to the access checker (and the anonymous
 * short-circuit that never reaches it).
 */
#[Test]
final class UserServiceTest
{

    /**
     * @return IdentityInterface&m\MockInterface
     */
    private function makeIdentityInterfaceMock(): IdentityInterface
    {
        /** @var IdentityInterface&m\MockInterface $mock */
        $mock = m::mock(IdentityInterface::class);
        return $mock;
    }
    private function makeService(
        ?CurrentUser $currentUser = null,
        ?UserRepository $repository = null,
        ?AccessCheckerInterface $accessChecker = null,
    ): UserService {
        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = $currentUser ?? m::mock(CurrentUser::class);
        /** @var UserRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(UserRepository::class);
        /** @var AccessCheckerInterface&m\MockInterface $accessChecker */
        $accessChecker = $accessChecker ?? m::mock(AccessCheckerInterface::class);
        return new UserService($currentUser, $repository, $accessChecker);
    }

    private function mockIdentityWithUserId(int $userId): m\MockInterface
    {
        /** @var Identity&m\MockInterface $identity */
        $identity = m::mock(Identity::class);
        $e = $identity->shouldReceive('getUserId');
        $e->once()->andReturn($userId);
        return $identity;
    }

    public function getUserReturnsUserForLoggedInId(): void
    {
        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $e = $currentUser->shouldReceive('getIdentity');
        $e->once()->andReturn($this->mockIdentityWithUserId(42));

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        /** @var UserRepository&m\MockInterface $repository */
        $repository = m::mock(UserRepository::class);
        $e2 = $repository->shouldReceive('findById');
        $e2->once()->with(42)->andReturn($user);

        $service = $this->makeService($currentUser, $repository);

        Assert::same($user, $service->getUser());
    }

    public function getUserReturnsNullWhenNoCurrentId(): void
    {
        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $e = $currentUser->shouldReceive('getIdentity');
        $e->once()->andReturn($this->makeIdentityInterfaceMock());

        /** @var UserRepository&m\MockInterface $repository */
        $repository = m::mock(UserRepository::class);
        $repository->shouldNotReceive('findById');

        $service = $this->makeService($currentUser, $repository);

        Assert::null($service->getUser());
    }

    public function hasPermissionReturnsTrueWhenAccessCheckerGrants(): void
    {
        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $e = $currentUser->shouldReceive('getIdentity');
        $e->once()->andReturn($this->mockIdentityWithUserId(7));

        /** @var AccessCheckerInterface&m\MockInterface $accessChecker */
        $accessChecker = m::mock(AccessCheckerInterface::class);
        $e2 = $accessChecker->shouldReceive('userHasPermission');
        $e2->once()->with(7, 'invoice.edit')->andReturn(true);

        $service = $this->makeService($currentUser, accessChecker: $accessChecker);

        Assert::true($service->hasPermission('invoice.edit'));
    }

    public function hasPermissionReturnsFalseWhenAccessCheckerDenies(): void
    {
        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $e = $currentUser->shouldReceive('getIdentity');
        $e->once()->andReturn($this->mockIdentityWithUserId(7));

        /** @var AccessCheckerInterface&m\MockInterface $accessChecker */
        $accessChecker = m::mock(AccessCheckerInterface::class);
        $e2 = $accessChecker->shouldReceive('userHasPermission');
        $e2->once()->with(7, 'invoice.delete')->andReturn(false);

        $service = $this->makeService($currentUser, accessChecker: $accessChecker);

        Assert::false($service->hasPermission('invoice.delete'));
    }

    public function hasPermissionShortCircuitsWhenNoCurrentId(): void
    {
        /** @var CurrentUser&m\MockInterface $currentUser */
        $currentUser = m::mock(CurrentUser::class);
        $e = $currentUser->shouldReceive('getIdentity');
        $e->once()->andReturn($this->makeIdentityInterfaceMock());

        /** @var AccessCheckerInterface&m\MockInterface $accessChecker */
        $accessChecker = m::mock(AccessCheckerInterface::class);
        $accessChecker->shouldNotReceive('userHasPermission');

        $service = $this->makeService($currentUser, accessChecker: $accessChecker);

        Assert::false($service->hasPermission('invoice.edit'));
    }
}
