<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Telegram;

use App\Invoice\Telegram\TelegramWebhookGuard;
use Testo\Assert;
use Testo\Test;

/**
 * The webhook is open to the internet with no login, so the secret token is the only thing that tells a real
 * Telegram delivery from a forged one. No secret configured must refuse everything, not let everything through.
 *
 * @see TelegramWebhookGuard
 */
#[Test]
final class TelegramWebhookGuardTest
{
    private const string BOT = '123456:ABC-DEF';
    private const string SECRET = 'Xk3-9_pQ7vLm2Wz8RtYh5NcB4aGd1sFe';

    public function aRequestCarryingTheConfiguredSecretIsAllowed(): void
    {
        Assert::null(TelegramWebhookGuard::refusal(self::BOT, self::SECRET, self::SECRET));
    }

    public function withNoSecretConfiguredEveryRequestIsRefused(): void
    {
        // the case that used to let everything through: a forged payment could be posted by anyone
        Assert::same(TelegramWebhookGuard::refusal(self::BOT, '', ''), TelegramWebhookGuard::SECRET_NOT_SET);
        Assert::same(TelegramWebhookGuard::refusal(self::BOT, '', 'anything'), TelegramWebhookGuard::SECRET_NOT_SET);
        Assert::same(TelegramWebhookGuard::refusal(self::BOT, '', self::SECRET), TelegramWebhookGuard::SECRET_NOT_SET);
    }

    public function aRequestWithoutTheHeaderIsRefused(): void
    {
        Assert::same(TelegramWebhookGuard::refusal(self::BOT, self::SECRET, ''), TelegramWebhookGuard::SECRET_MISMATCH);
    }

    public function aWrongSecretIsRefused(): void
    {
        Assert::same(TelegramWebhookGuard::refusal(self::BOT, self::SECRET, 'wrong'), TelegramWebhookGuard::SECRET_MISMATCH);
    }

    public function aSecretThatOnlyStartsRightIsRefused(): void
    {
        $prefix = substr(self::SECRET, 0, 31);
        Assert::same(TelegramWebhookGuard::refusal(self::BOT, self::SECRET, $prefix), TelegramWebhookGuard::SECRET_MISMATCH);
        Assert::same(TelegramWebhookGuard::refusal(self::BOT, self::SECRET, self::SECRET . 'x'), TelegramWebhookGuard::SECRET_MISMATCH);
    }

    public function theCaseOfTheSecretMatters(): void
    {
        Assert::same(
            TelegramWebhookGuard::refusal(self::BOT, self::SECRET, strtolower(self::SECRET)),
            TelegramWebhookGuard::SECRET_MISMATCH,
        );
    }

    public function withoutABotTokenEverythingIsRefused(): void
    {
        foreach (['', 'x'] as $botToken) {
            Assert::same(
                TelegramWebhookGuard::refusal($botToken, self::SECRET, self::SECRET),
                TelegramWebhookGuard::BOT_TOKEN_NOT_SET,
            );
        }
    }

    public function theBotTokenIsCheckedBeforeTheSecret(): void
    {
        Assert::same(TelegramWebhookGuard::refusal('', '', ''), TelegramWebhookGuard::BOT_TOKEN_NOT_SET);
    }
}
