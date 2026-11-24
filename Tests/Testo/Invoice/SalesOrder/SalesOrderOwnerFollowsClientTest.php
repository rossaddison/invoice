<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrder;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Client\ClientActiveUser;
use App\Invoice\SalesOrder\SalesOrderOwnerFollowsClient;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * A sales order belongs to the user assigned to its client (SalesOrderRbacGuard::isObserver() compares it). The staff
 * edit form lets the client be changed but never posts a user, so a moved order kept the old client's user and then
 * belonged to nobody who could open it. A move now hands the order to the new client's user.
 */
#[Test]
final class SalesOrderOwnerFollowsClientTest
{
    private int $lookups = 0;

    private function order(int $clientId): SalesOrder
    {
        $so = new SalesOrder();
        $so->setId(5);
        $so->setClientId($clientId);

        return $so;
    }

    private function rule(?int $ownerOfAnyClient): SalesOrderOwnerFollowsClient
    {
        $this->lookups = 0;
        $owner = null;
        if (null !== $ownerOfAnyClient) {
            $owner = TypedMock::of(User::class);
            $owner->shouldReceive('reqId')->andReturn($ownerOfAnyClient);
        }
        $owners = TypedMock::of(ClientActiveUser::class);
        $owners->shouldReceive('forClient')->andReturnUsing(
            function () use ($owner): ?User {
                ++$this->lookups;
                return $owner;
            },
        );

        return new SalesOrderOwnerFollowsClient($owners);
    }

    public function anOrderLeftOnItsClientIsSavedAsPostedAndKeepsItsOwner(): void
    {
        $body = ['client_id' => '3', 'notes' => 'x'];

        Assert::same($this->rule(99)->forStaffSave($this->order(3), $body), $body);
        Assert::same($this->lookups, 0);
    }

    public function anOrderWithNoClientInThePostIsLeftAlone(): void
    {
        $body = ['notes' => 'x'];

        Assert::same($this->rule(99)->forStaffSave($this->order(3), $body), $body);
        Assert::same($this->lookups, 0);
    }

    public function anOrderMovedToAnotherClientIsGivenToThatClientsUser(): void
    {
        $saved = $this->rule(99)->forStaffSave($this->order(3), ['client_id' => '8', 'notes' => 'x']);

        Assert::same($saved, ['client_id' => '8', 'notes' => 'x', 'user_id' => '99']);
        Assert::same($this->lookups, 1);
    }

    public function anOrderCannotBeMovedToAClientWithNoSingleActiveUser(): void
    {
        Assert::null($this->rule(null)->forStaffSave($this->order(3), ['client_id' => '8']));
    }
}
