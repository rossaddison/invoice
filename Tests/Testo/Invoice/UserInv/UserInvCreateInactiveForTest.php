<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UserInv;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\UserInv\UserInvRepository;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * The one place that builds the extension row of a user who has just signed up: inactive, typed
 * administrator only for user 1, one row per user.
 *
 * @see UserInvRepository::createInactiveFor()
 */
#[Test]
final class UserInvCreateInactiveForTest
{
    /** @var list<UserInv> rows handed to save() */
    private array $saved = [];

    private function repository(?UserInv $existing = null): UserInvRepository
    {
        $this->saved = [];
        $repository = TypedMock::of(UserInvRepository::class);
        $repository->makePartial();
        $repository->shouldReceive('repoUserInvUserIdquery')->andReturn($existing);
        $repository->shouldReceive('save')->andReturnUsing(function (mixed $row): void {
            if ($row instanceof UserInv) {
                $this->saved[] = $row;
            }
        });
        return $repository;
    }

    private function user(int $id): User
    {
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn($id);
        return $user;
    }

    public function aNewSignupGetsAnInactiveRowWithItsLanguage(): void
    {
        $row = $this->repository()->createInactiveFor($this->user(7), 'English');

        Assert::same($this->saved, [$row]);
        Assert::same($row->reqUserId(), 7);
        Assert::false($row->getActive());
        Assert::same($row->getLanguage(), 'English');
    }

    public function onlyUserOneIsTheAdministratorType(): void
    {
        Assert::same($this->repository()->createInactiveFor($this->user(1), 'English')->getType(), UserInvRepository::TYPE_ADMIN);
        Assert::same($this->repository()->createInactiveFor($this->user(2), 'English')->getType(), UserInvRepository::TYPE_NOT_ADMIN);
        Assert::same($this->repository()->createInactiveFor($this->user(11), 'English')->getType(), UserInvRepository::TYPE_NOT_ADMIN);
    }

    public function theConsentsDefaultToOffAndAreStoredWhenGiven(): void
    {
        $default = $this->repository()->createInactiveFor($this->user(7), 'English');
        Assert::false($default->getConsentPeriodicInvoice());
        Assert::false($default->getConsentTelegramOutstanding());

        $given = $this->repository()->createInactiveFor($this->user(8), 'English', true, true);
        Assert::true($given->getConsentPeriodicInvoice());
        Assert::true($given->getConsentTelegramOutstanding());
    }

    public function aUserWhoAlreadyHasARowGetsThatRowBackUntouched(): void
    {
        $existing = new UserInv();
        $existing->setUserId(7);
        $existing->setActive(true);
        $existing->setLanguage('French');

        $row = $this->repository($existing)->createInactiveFor($this->user(7), 'English');

        Assert::same($row, $existing);
        Assert::true($row->getActive());
        Assert::same($row->getLanguage(), 'French');
        Assert::same($this->saved, []);
    }
}
