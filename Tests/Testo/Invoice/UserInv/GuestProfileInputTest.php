<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UserInv;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\UserInv\GuestProfileInput;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\UserInv\UserInvService;
use App\User\UserRepository;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * An observer's own-profile form must not be able to change what the administrator controls: type, active,
 * all_clients, the user the row belongs to, and the verified telegram chat id.
 *
 * @see GuestProfileInput
 */
#[Test]
final class GuestProfileInputTest
{
    /** the row as stored: an ordinary, active observer, linked to chat 555 */
    private function stored(): UserInv
    {
        $userInv = new UserInv();
        $userInv->setUserId(7);
        $userInv->setType(1);
        $userInv->setActive(true);
        $userInv->setAllClients(false);
        $userInv->setName('Virtual Observer');
        $userInv->setTelegramChatId('555');
        return $userInv;
    }

    /** what a visitor who edits the hidden fields would post */
    private function tampered(): array
    {
        return [
            'user_id' => '1',
            'type' => '0',
            'active' => '0',
            'all_clients' => '1',
            'telegram_chat_id' => '999',
            'name' => 'Renamed Observer',
            'language' => 'English',
        ];
    }

    public function theProtectedFieldsComeFromTheStoredRow(): void
    {
        $safe = GuestProfileInput::forSave($this->tampered(), $this->stored());

        Assert::same($safe['user_id'], '7');
        Assert::same($safe['type'], '1');
        Assert::same($safe['active'], '1');
        Assert::same($safe['all_clients'], '0');
        Assert::same($safe['telegram_chat_id'], '555');
    }

    public function theUsersOwnFieldsPassThroughUntouched(): void
    {
        $posted = $this->tampered() + ['address_1' => '1 Virtual Street', 'consent_telegram_outstanding' => '1'];
        $safe = GuestProfileInput::forSave($posted, $this->stored());

        Assert::same($safe['name'], 'Renamed Observer');
        Assert::same($safe['language'], 'English');
        Assert::same($safe['address_1'], '1 Virtual Street');
        Assert::same($safe['consent_telegram_outstanding'], '1');
    }

    public function aFieldLeftOutOfThePostIsStillSuppliedFromTheStoredRow(): void
    {
        // the form no longer carries the chat id, and the service clears it when it is missing
        $safe = GuestProfileInput::forSave(['name' => 'Only A Name'], $this->stored());

        Assert::same($safe['telegram_chat_id'], '555');
        Assert::same($safe['active'], '1');
        Assert::same($safe['all_clients'], '0');
    }

    public function anUnlinkedAccountStaysUnlinked(): void
    {
        $stored = $this->stored();
        $stored->setTelegramChatId(null);

        Assert::same(GuestProfileInput::forSave($this->tampered(), $stored)['telegram_chat_id'], '');
    }

    public function anAdministratorTypeRowStaysAdministratorType(): void
    {
        $stored = $this->stored();
        $stored->setType(0);

        Assert::same(GuestProfileInput::forSave(['type' => '1'], $stored)['type'], '0');
    }

    public function throughTheRealServiceATamperedPostChangesOnlyWhatTheUserMayChange(): void
    {
        $stored = $this->stored();
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(7);
        $users = TypedMock::of(UserRepository::class);
        // persist() looks the user up by the posted id: the stored id, not the tampered one, must be asked for
        $users->shouldReceive('findById')->with(7)->andReturn($user);
        $repository = TypedMock::of(UserInvRepository::class);
        $repository->shouldReceive('save')->once();

        new UserInvService($repository, $users)->saveUserInv(
            $stored,
            GuestProfileInput::forSave($this->tampered(), $stored),
        );

        Assert::same($stored->getName(), 'Renamed Observer');
        Assert::same($stored->reqUserId(), 7);
        Assert::same($stored->getType(), 1);
        Assert::true($stored->getActive());
        Assert::false($stored->getAllClients());
        Assert::same($stored->getTelegramChatId(), '555');
    }
}
