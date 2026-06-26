<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\UserClient;

use App\Infrastructure\Persistence\UserClient\UserClient;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class UserClientTest
{
    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new UserClient())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new UserClient())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'UserClient not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdAndRetrieve(): void
    {
        $uc = new UserClient();
        $uc->setId(5);

        Assert::same($uc->reqId(), 5);
    }

    #[ExpectException(\LogicException::class)]
    public function reqUserIdThrowsWhenUnpersisted(): void
    {
        (new UserClient())->reqUserId();
    }

    public function reqUserIdThrowsWithCorrectMessage(): void
    {
        try {
            (new UserClient())->reqUserId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'User not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setUserIdAndRetrieve(): void
    {
        $uc = new UserClient();
        $uc->setUserId(10);

        Assert::same($uc->reqUserId(), 10);
    }

    #[ExpectException(\LogicException::class)]
    public function reqClientIdThrowsWhenUnpersisted(): void
    {
        (new UserClient())->reqClientId();
    }

    public function reqClientIdThrowsWithCorrectMessage(): void
    {
        try {
            (new UserClient())->reqClientId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'Client not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setClientIdAndRetrieve(): void
    {
        $uc = new UserClient();
        $uc->setClientId(20);

        Assert::same($uc->reqClientId(), 20);
    }

    public function constructorWithAllArgs(): void
    {
        $uc = new UserClient(id: 1, user_id: 2, client_id: 3);

        Assert::same($uc->reqId(), 1);
        Assert::same($uc->reqUserId(), 2);
        Assert::same($uc->reqClientId(), 3);
    }

    public function userRelationNullByDefault(): void
    {
        Assert::null((new UserClient())->getUser());
    }

    public function clientRelationNullByDefault(): void
    {
        Assert::null((new UserClient())->getClient());
    }
}
