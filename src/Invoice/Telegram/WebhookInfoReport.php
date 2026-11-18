<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

use DateTimeImmutable;
use DateTimeZone;
use Phptg\BotApi\Type\Update\WebhookInfo;

/**
 * The lines of the admin's Webhook info page: what Telegram itself reports about the webhook (its address,
 * how many updates are waiting, the last delivery error) and, first, a warning when the Webhook Secret Token
 * is not set, since the webhook then refuses every update (see TelegramWebhookGuard).
 *
 * Kept apart from the view so the wording and the handling of a missing error can be tested.
 */
final class WebhookInfoReport
{
    public const string NO_WEBHOOK = 'Your url is an empty string which shows that you are using getUpdates which '
        . 'mutually excludes webhook use.';
    public const string NO_ERROR = 'Last error: none reported by Telegram.';
    public const string ERROR_STAYS = 'Telegram keeps showing the last error until a later update is delivered '
        . 'successfully.';
    public const string NO_SECRET = 'The Webhook Secret Token is not set in Settings: the webhook refuses every '
        . 'update until it is set and the webhook is registered again (step 9).';

    /**
     * @return list<array{text: string, warning: bool}> in the order they are shown
     */
    public static function lines(WebhookInfo $info, bool $secretConfigured): array
    {
        $lines = [];
        if (!$secretConfigured) {
            $lines[] = ['text' => self::NO_SECRET, 'warning' => true];
        }
        $lines[] = [
            'text' => $info->url === ''
                ? self::NO_WEBHOOK
                : 'Here is your currently setup webhook url: ' . $info->url,
            'warning' => false,
        ];
        $lines[] = ['text' => 'Pending Update Count: ' . $info->pendingUpdateCount, 'warning' => false];

        $message = trim((string) $info->lastErrorMessage);
        if ($message === '') {
            $lines[] = ['text' => self::NO_ERROR, 'warning' => false];
        } else {
            $lines[] = ['text' => 'Last error: ' . $message . self::when($info->lastErrorDate), 'warning' => true];
            $lines[] = ['text' => self::ERROR_STAYS, 'warning' => false];
        }
        if (null !== $info->lastSynchronizationErrorDate) {
            $lines[] = [
                'text' => 'Telegram last failed to synchronise this webhook with its servers' . self::when($info->lastSynchronizationErrorDate) . '.',
                'warning' => true,
            ];
        }
        return $lines;
    }

    /**
     * " (2026-11-18 13:05:11 UTC)", or nothing when Telegram gave no time.
     */
    private static function when(?DateTimeImmutable $date): string
    {
        return null === $date ? '' : ' (' . $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s') . ' UTC)';
    }
}
