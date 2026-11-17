<?php

declare(strict_types=1);

namespace Tests\Testo\User;

use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\AppConstants;
use App\Invoice\UserInv\UserInvRepository;
use App\Middleware\AccessChecker;
use App\User\UserRepository;
use App\User\UserService;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Role;
use Yiisoft\Session\SessionInterface;
use Yiisoft\User\CurrentUser;

/**
 * The login flow authenticates the identity at the password step (CurrentUser::login() saves it in
 * the session) and only later asks for the one-time code. Permissions must not be granted in
 * between.
 *
 * @see UserService
 */
#[Test]
final class UserServiceGateTest
{
    private const int USER_ID = 7;

    /** @var int how many times the userinv row was read */
    private int $userInvReads = 0;

    /**
     * @param mixed $tfaVerified what the session holds under 'tfa_verified' (null = never set)
     * @param bool $rbacGrants whether the role based check would grant a permission
     * @param bool|null $userInvActive userinv.active for the user; null = the user has no userinv row
     * @param bool $admin whether the user holds the admin role
     */
    private function service(
        mixed $tfaVerified,
        bool $rbacGrants = true,
        ?bool $userInvActive = true,
        bool $admin = false,
    ): UserService {
        $this->userInvReads = 0;
        $identity = TypedMock::of(Identity::class);
        $identity->shouldReceive('getUserId')->andReturn(self::USER_ID);
        $currentUser = TypedMock::of(CurrentUser::class);
        $currentUser->shouldReceive('getIdentity')->andReturn($identity);

        $accessChecker = TypedMock::of(AccessCheckerInterface::class);
        // Like the real Manager (includeRolesInAccessChecks = false), asking it about a role name is
        // always false: the admin exemption must not depend on this call.
        $accessChecker->shouldReceive('userHasPermission')->andReturnUsing(
            static fn (mixed $userId, string $permission): bool => $permission === AppConstants::ROLE_ADMIN ? false : $rbacGrants,
        );
        $manager = TypedMock::of(Manager::class);
        $manager->shouldReceive('getRolesByUserId')->andReturn(
            $admin ? [new Role(AppConstants::ROLE_OBSERVER), new Role(AppConstants::ROLE_ADMIN)] : [new Role(AppConstants::ROLE_OBSERVER)],
        );

        $userInv = null;
        if (null !== $userInvActive) {
            $userInv = TypedMock::of(UserInv::class);
            $userInv->shouldReceive('getActive')->andReturn($userInvActive);
        }
        $userInvRepository = TypedMock::of(UserInvRepository::class);
        $userInvRepository->shouldReceive('repoUserInvUserIdquery')->with(self::USER_ID)->andReturnUsing(
            function () use ($userInv): ?UserInv {
                ++$this->userInvReads;
                return $userInv;
            },
        );

        $session = TypedMock::of(SessionInterface::class);
        $session->shouldReceive('get')->with(UserService::SESSION_TFA_VERIFIED)->andReturn($tfaVerified);

        // only read when the route gate logs a denial
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(self::USER_ID);
        $repository = TypedMock::of(UserRepository::class);
        $repository->shouldReceive('findById')->andReturn($user);

        return new UserService($currentUser, $repository, $accessChecker, $session, $userInvRepository, $manager);
    }

    public function anAccountDeactivatedWhileLoggedInLosesItsPermissionsAtOnce(): void
    {
        // The session was opened while the account was active; the flag has since been cleared.
        Assert::false($this->service(null, userInvActive: false)->hasPermission('editInv'));
        Assert::false($this->service(true, userInvActive: false)->hasPermission('viewInv'));
    }

    public function anActiveAccountIsDecidedByItsRoleAsBefore(): void
    {
        Assert::true($this->service(null, userInvActive: true)->hasPermission('editInv'));
        Assert::false($this->service(null, rbacGrants: false, userInvActive: true)->hasPermission('editInv'));
    }

    public function anAdminIsNeverLockedOutByTheActiveFlag(): void
    {
        // same exemption as at login: an admin may be marked inactive and must still get in
        Assert::true($this->service(null, userInvActive: false, admin: true)->hasPermission('editInv'));
    }

    public function anAccountWithNoUserInvRowIsLeftToTheRoleCheck(): void
    {
        Assert::true($this->service(null, userInvActive: null)->hasPermission('editInv'));
        Assert::false($this->service(null, rbacGrants: false, userInvActive: null)->hasPermission('editInv'));
    }

    public function theActiveFlagIsReadOnceNoMatterHowManyPermissionsAreChecked(): void
    {
        $service = $this->service(null, userInvActive: true);
        foreach (['editInv', 'viewInv', 'editUserInv', 'editInv', 'viewInv'] as $permission) {
            $service->hasPermission($permission);
        }

        Assert::same($this->userInvReads, 1);
    }

    public function aPendingSecondFactorStillWinsWithoutReadingTheActiveFlag(): void
    {
        $service = $this->service(false, userInvActive: true);

        Assert::false($service->hasPermission('editInv'));
        Assert::same($this->userInvReads, 0);
    }

    public function aSessionThatHasOnlyPassedThePasswordStepHoldsNoPermission(): void
    {
        // The role would grant it; the pending second factor still wins.
        Assert::false($this->service(false)->hasPermission('editInv'));
    }

    public function afterTheCodeIsEnteredTheRoleDecides(): void
    {
        Assert::true($this->service(true)->hasPermission('editInv'));
        Assert::false($this->service(true, rbacGrants: false)->hasPermission('editInv'));
    }

    public function aSessionThatNeverWentThroughTheSecondFactorIsUnaffected(): void
    {
        // remember-me cookie, OAuth, or 2FA switched off: no 'tfa_verified' value at all
        Assert::true($this->service(null)->hasPermission('editInv'));
    }

    public function onlyAnExplicitFalseCountsAsPending(): void
    {
        foreach (['', '0', 0, [], 'false'] as $value) {
            Assert::true($this->service($value)->hasPermission('editInv'));
        }
    }

    public function theRouteGateAnswers403WhileTheCodeIsPending(): void
    {
        $handler = new class () implements RequestHandlerInterface {
            public bool $reached = false;

            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->reached = true;
                return new HttpFactory()->createResponse(Status::OK);
            }
        };
        $gate = new AccessChecker(new HttpFactory(), $this->service(false), new NullLogger())->withPermission('editInv');

        $response = $gate->process(new HttpFactory()->createServerRequest('GET', 'https://example.test/en/invoice/inv'), $handler);

        Assert::same($response->getStatusCode(), Status::FORBIDDEN);
        Assert::false($handler->reached);
    }

    public function theRouteGateLetsAVerifiedSessionThrough(): void
    {
        $handler = new class () implements RequestHandlerInterface {
            public bool $reached = false;

            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->reached = true;
                return new HttpFactory()->createResponse(Status::OK);
            }
        };
        $gate = new AccessChecker(new HttpFactory(), $this->service(true), new NullLogger())->withPermission('editInv');

        $response = $gate->process(new HttpFactory()->createServerRequest('GET', 'https://example.test/en/invoice/inv'), $handler);

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::true($handler->reached);
    }
}
