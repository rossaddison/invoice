<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

/**
 * Decides whether a request to the Telegram webhook may be acted on.
 *
 * The webhook address has to be open to the internet and has no login, so the only thing that tells a real
 * Telegram delivery from a forged one is the secret token Telegram sends back in the
 * X-Telegram-Bot-Api-Secret-Token header (the one given to setWebhook). A webhook with no secret set used to
 * let every request through, so anyone who knew the address could post a fake `successful_payment` and have a
 * payment recorded against an invoice, or fake the commands that link a chat to an account.
 *
 * It now fails closed: no secret configured means every request is refused, and a secret that does not match
 * is refused.
 */
final class TelegramWebhookGuard
{
    public const string BOT_TOKEN_NOT_SET = 'bot token not set';
    public const string SECRET_NOT_SET = 'webhook secret token not set';
    public const string SECRET_MISMATCH = 'secret token missing or wrong';

    /**
     * @param string $botToken the bot token in Settings (blank when Telegram is not set up)
     * @param string $configuredSecret the Webhook Secret Token in Settings
     * @param string $presentedSecret the X-Telegram-Bot-Api-Secret-Token header of the request, '' when absent
     *
     * @return string|null why the request is refused, or null when it may be acted on
     */
    public static function refusal(string $botToken, string $configuredSecret, string $presentedSecret): ?string
    {
        if (strlen($botToken) < 2) {
            return self::BOT_TOKEN_NOT_SET;
        }
        if ($configuredSecret === '') {
            return self::SECRET_NOT_SET;
        }
        // constant time: the comparison must not reveal how many leading characters were right
        return hash_equals($configuredSecret, $presentedSecret) ? null : self::SECRET_MISMATCH;
    }
}
