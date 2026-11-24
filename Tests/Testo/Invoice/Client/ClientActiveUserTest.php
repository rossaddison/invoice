<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Client;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Client\ClientActiveUser;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\User\UserRepository;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * The owner of a client's records is the one user assigned to it, and only if their account is active. Nobody
 * assigned, several users assigned or an inactive account is no owner.
 */
#[Test]
final class ClientActiveUserTest
{
    private const int CLIENT = 12;
    private const int USER = 7;

    /**
     * @param bool $linked is any user assigned to the client
     * @param int $assigned how many users are assigned
     * @param bool|null $active the user's account state; null = no user_inv row
     */
    private function owner(bool $linked, int $assigned, ?bool $active): ?User
    {
        $userClient = TypedMock::of(UserClient::class);
        $userClient->shouldReceive('reqUserId')->andReturn(self::USER);
        $userClients = TypedMock::of(UserClientRepository::class);
        $userClients->shouldReceive('repoUserquery')->with(self::CLIENT)->andReturn($linked ? $userClient : null);
        $userClients->shouldReceive('repoUserqueryCount')->with(self::CLIENT)->andReturn($assigned);

        $userInv = null;
        if (null !== $active) {
            $userInv = TypedMock::of(UserInv::class);
            $userInv->shouldReceive('getActive')->andReturn($active);
        }
        $userInvs = TypedMock::of(UserInvRepository::class);
        $userInvs->shouldReceive('repoUserInvUserIdquery')->with(self::USER)->andReturn($userInv);

        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(self::USER);
        $users = TypedMock::of(UserRepository::class);
        $users->shouldReceive('findById')->with(self::USER)->andReturn($user);

        return new ClientActiveUser($users, $userClients, $userInvs)->forClient(self::CLIENT);
    }

    public function theSingleActiveUserOfTheClientIsTheOwner(): void
    {
        Assert::same($this->owner(true, 1, true)?->reqId(), self::USER);
    }

    public function aClientWithNoUserHasNoOwner(): void
    {
        Assert::null($this->owner(false, 0, true));
    }

    public function aClientWithSeveralUsersHasNoOwner(): void
    {
        Assert::null($this->owner(true, 2, true));
    }

    public function anInactiveUserIsNoOwner(): void
    {
        Assert::null($this->owner(true, 1, false));
    }

    public function aUserWithNoAccountRecordIsNoOwner(): void
    {
        Assert::null($this->owner(true, 1, null));
    }
}
