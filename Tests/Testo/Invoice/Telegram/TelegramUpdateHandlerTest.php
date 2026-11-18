<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Telegram;

use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Telegram\LinkOutcome;
use App\Invoice\Telegram\TelegramLinkService;
use App\Invoice\Telegram\TelegramUpdateHandler;
use DateTimeImmutable;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\ChatMember;
use Phptg\BotApi\Type\ChatMemberBanned;
use Phptg\BotApi\Type\ChatMemberLeft;
use Phptg\BotApi\Type\ChatMemberMember;
use Phptg\BotApi\Type\ChatMemberUpdated;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\RecordingTelegramTransport;
use Tests\Testo\Support\TypedMock;

/**
 * What the bot does with a person's own messages: link on /start <token>, unlink on /stop or when the bot is
 * blocked, and nothing at all for anything outside a private chat. The replies are read from the requests
 * the bot would have made.
 *
 * The updates are built directly: the library's own JSON parser reads only `readonly` properties, which the
 * test setup's final-class bypass removes, so it cannot parse under test (it does in production).
 *
 * @see TelegramUpdateHandler
 */
#[Test]
final class TelegramUpdateHandlerTest
{
    private const string TOKEN = 'AbCdEfGhIjKlMnOpQrStUvWxYz012345';

    private function person(): User
    {
        return new User(555, false, 'Virtual');
    }

    private function bot(): User
    {
        return new User(9, true, 'Bot', username: 'virtual_bot');
    }

    private function message(string $text, string $chatType = 'private'): Update
    {
        return new Update(1, message: new Message(
            messageId: 1,
            date: new DateTimeImmutable('@1700000000'),
            chat: new Chat(555, $chatType, firstName: 'Virtual'),
            from: $this->person(),
            text: $text,
        ));
    }

    private function botChange(ChatMember $new, string $chatType = 'private'): Update
    {
        return new Update(2, myChatMember: new ChatMemberUpdated(
            chat: new Chat(555, $chatType, firstName: 'Virtual'),
            from: $this->person(),
            date: new DateTimeImmutable('@1700000000'),
            oldChatMember: new ChatMemberMember($this->bot()),
            newChatMember: $new,
        ));
    }

    /**
     * @return list<string> the texts the bot sent
     */
    private function run(Update $update, TelegramLinkService $links): array
    {
        $transport = new RecordingTelegramTransport();
        $bot = new TelegramHelper('123:TEST', new NullLogger(), $transport);
        new TelegramUpdateHandler($links)->handle($update, $bot);
        return $transport->sentTexts();
    }

    public function startWithAValidTokenLinksTheChatAndSaysSo(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('link')->once()->with(self::TOKEN, '555')->andReturn(LinkOutcome::Linked);

        Assert::same($this->run($this->message('/start ' . self::TOKEN), $links), [TelegramUpdateHandler::REPLY_LINKED]);
    }

    public function startWithATokenThatIsNotValidSaysTheLinkIsNotValid(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('link')->once()->andReturn(LinkOutcome::InvalidToken);

        Assert::same($this->run($this->message('/start nonsense'), $links), [TelegramUpdateHandler::REPLY_INVALID]);
    }

    public function startWithAnExpiredTokenSaysTheLinkHasExpired(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('link')->once()->andReturn(LinkOutcome::Expired);

        Assert::same($this->run($this->message('/start ' . self::TOKEN), $links), [TelegramUpdateHandler::REPLY_EXPIRED]);
    }

    public function startWithoutATokenSaysHowToLinkAndLinksNothing(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('link')->never();

        Assert::same($this->run($this->message('/start'), $links), [TelegramUpdateHandler::REPLY_HOW_TO_LINK]);
    }

    public function aCommandAddressedToTheBotByNameStillWorks(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('link')->once()->with(self::TOKEN, '555')->andReturn(LinkOutcome::Linked);

        Assert::same(
            $this->run($this->message('/start@VirtualBot ' . self::TOKEN), $links),
            [TelegramUpdateHandler::REPLY_LINKED],
        );
    }

    public function stopUnlinksTheChatAndConfirms(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('unlinkChat')->once()->with('555')->andReturn(1);

        Assert::same($this->run($this->message('/STOP'), $links), [TelegramUpdateHandler::REPLY_STOPPED]);
    }

    public function aGroupChatIsNeverLinkedOrUnlinked(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('link')->never();
        $links->shouldReceive('unlinkChat')->never();

        Assert::same($this->run($this->message('/start ' . self::TOKEN, 'supergroup'), $links), []);
        Assert::same($this->run($this->message('/stop', 'group'), $links), []);
    }

    public function ordinaryTextIsIgnored(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('link')->never();
        $links->shouldReceive('unlinkChat')->never();

        Assert::same($this->run($this->message('hello, is anyone there?'), $links), []);
        Assert::same($this->run($this->message('start'), $links), []);
    }

    public function blockingTheBotUnlinksTheChatWithoutAMessage(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('unlinkChat')->once()->with('555')->andReturn(1);

        $blocked = $this->botChange(new ChatMemberBanned($this->bot(), false));

        Assert::same($this->run($blocked, $links), []);
    }

    public function leavingTheBotUnlinksTheChatToo(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('unlinkChat')->once()->with('555')->andReturn(0);

        $this->run($this->botChange(new ChatMemberLeft($this->bot())), $links);
    }

    public function aChangeThatKeepsThePersonInTheChatUnlinksNothing(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('unlinkChat')->never();

        $this->run($this->botChange(new ChatMemberMember($this->bot())), $links);
    }

    public function theBotBeingRemovedFromAGroupUnlinksNothing(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('unlinkChat')->never();

        $this->run($this->botChange(new ChatMemberBanned($this->bot(), false), 'supergroup'), $links);
    }
}
