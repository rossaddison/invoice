<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\Worker;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\Worker\Worker;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class WorkerTest
{
    public function defaultsToUnpersisted(): void
    {
        $worker = new Worker();

        Assert::false($worker->hasIdentity());
        Assert::same($worker->getFirstname(), '');
        Assert::same($worker->getLastname(), '');
        Assert::true($worker->getActive());
        Assert::null($worker->getUser());
        Assert::null($worker->getUserId());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new Worker())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new Worker())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'Worker not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityIdentifiable(): void
    {
        $worker = new Worker();
        $worker->setId(42);

        Assert::true($worker->hasIdentity());
        Assert::same($worker->reqId(), 42);
    }

    public function settersAndGetters(): void
    {
        $worker = new Worker(
            firstname: 'Jane',
            lastname: 'Doe',
            active: false,
        );

        Assert::same($worker->getFirstname(), 'Jane');
        Assert::same($worker->getLastname(), 'Doe');
        Assert::false($worker->getActive());
    }

    public function settersMutateValues(): void
    {
        $worker = new Worker();
        $worker->setFirstname('John');
        $worker->setLastname('Smith');
        $worker->setActive(false);

        Assert::same($worker->getFirstname(), 'John');
        Assert::same($worker->getLastname(), 'Smith');
        Assert::false($worker->getActive());
    }

    public function linkingAUserExposesItOnGetUser(): void
    {
        $user = new User('jane.doe', 'jane@example.com', 'password123');
        $worker = new Worker(firstname: 'Jane');

        $worker->setUser($user);

        Assert::same($worker->getUser(), $user);
    }

    #[ExpectException(\LogicException::class)]
    public function getUserIdThrowsWhenLinkedUserIsUnpersisted(): void
    {
        $worker = new Worker(firstname: 'Jane');
        $worker->setUser(new User('jane.doe', 'jane@example.com', 'password123'));

        $worker->getUserId();
    }
}
