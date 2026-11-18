<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

use App\Invoice\Helpers\Telegram\TelegramHelper;
use Phptg\BotApi\Type\Update\Update;

/**
 * What the bot does with a person's own messages to it.
 *
 *   /start <token>   links this chat to the account that made the token (TelegramLinkService)
 *   /start           says how to link
 *   /stop            unlinks this chat and switches the Telegram reminders off
 *   blocking the bot (a `my_chat_member` update to kicked or left) does the same as /stop, without a message
 *
 * Only a private chat counts: a group the bot is added to, or a channel, is never linked. Anything else
 * a person writes is ignored. The caller must already have verified the webhook secret token, which is
 * the only thing that tells these updates from forged ones.
 */
final readonly class TelegramUpdateHandler
{
    public const string REPLY_LINKED = 'Your Telegram is now linked. If you have ticked the Telegram reminder box on '
        . 'your profile, you will get a reminder here when an invoice is awaiting payment. Send /stop at any time '
        . 'to stop them.';
    public const string REPLY_INVALID = 'That link is not valid or has already been used. Open your profile page and '
        . 'press "Link Telegram" again.';
    public const string REPLY_EXPIRED = 'That link has expired. Open your profile page and press "Link Telegram" again.';
    public const string REPLY_HOW_TO_LINK = 'To get payment reminders here, open your profile page on the invoice site '
        . 'and press "Link Telegram".';
    public const string REPLY_STOPPED = 'Stopped. You will not get any more reminders here. You can link again from '
        . 'your profile page.';

    private const string PRIVATE_CHAT = 'private';

    /** the statuses of a person who has blocked the bot or left it */
    private const array GONE = ['kicked', 'left'];

    public function __construct(private TelegramLinkService $links)
    {
    }

    public function handle(Update $update, TelegramHelper $bot): void
    {
        $this->handleMessage($update, $bot);
        $this->handleBlocked($update);
    }

    private function handleMessage(Update $update, TelegramHelper $bot): void
    {
        $message = $update->message;
        $text = $message?->text;
        if (null === $message || null === $text || self::PRIVATE_CHAT !== $message->chat->type) {
            return;
        }
        $chatId = (string) $message->chat->id;
        [$command, $argument] = self::parseCommand($text);
        if ('start' === $command) {
            $bot->sendMessage($chatId, null === $argument ? self::REPLY_HOW_TO_LINK : $this->startReply($argument, $chatId));
            return;
        }
        if ('stop' === $command) {
            $this->links->unlinkChat($chatId);
            $bot->sendMessage($chatId, self::REPLY_STOPPED);
        }
    }

    private function startReply(string $token, string $chatId): string
    {
        return match ($this->links->link($token, $chatId)) {
            LinkOutcome::Linked => self::REPLY_LINKED,
            LinkOutcome::Expired => self::REPLY_EXPIRED,
            LinkOutcome::InvalidToken => self::REPLY_INVALID,
        };
    }

    private function handleBlocked(Update $update): void
    {
        $change = $update->myChatMember;
        if (null === $change || self::PRIVATE_CHAT !== $change->chat->type) {
            return;
        }
        if (in_array($change->newChatMember->getStatus(), self::GONE, true)) {
            $this->links->unlinkChat((string) $change->chat->id);
        }
    }

    /**
     * "/start abc", "/start@SomeBot abc" and "/STOP" -> ['start', 'abc'], ['start', 'abc'], ['stop', null].
     *
     * @return array{0: string|null, 1: string|null} the command in lower case (null when the text is not a
     *                                              command) and its first argument
     */
    private static function parseCommand(string $text): array
    {
        if (preg_match('#^/([A-Za-z]+)(?:@\w+)?(?:\s+(\S+))?#', trim($text), $m) !== 1) {
            return [null, null];
        }
        return [strtolower($m[1]), $m[2] ?? null];
    }
}
