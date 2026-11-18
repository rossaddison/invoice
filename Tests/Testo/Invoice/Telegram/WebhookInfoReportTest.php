<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Telegram;

use App\Invoice\Telegram\WebhookInfoReport;
use DateTimeImmutable;
use Phptg\BotApi\Type\Update\WebhookInfo;
use Testo\Assert;
use Testo\Test;

/**
 * What the admin reads on the Webhook info page: the address, the pending count, Telegram's last error with
 * its time, and a warning when no secret token is set.
 *
 * @see WebhookInfoReport
 */
#[Test]
final class WebhookInfoReportTest
{
    private const string URL = 'https://invoice.example.test/en/telegram/webhook';

    private function info(
        string $url = self::URL,
        int $pending = 0,
        ?string $error = null,
        ?DateTimeImmutable $errorDate = null,
        ?DateTimeImmutable $syncDate = null,
    ): WebhookInfo {
        return new WebhookInfo(
            url: $url,
            hasCustomCertificate: false,
            pendingUpdateCount: $pending,
            lastErrorDate: $errorDate,
            lastErrorMessage: $error,
            lastSynchronizationErrorDate: $syncDate,
        );
    }

    /**
     * @param list<array{text: string, warning: bool}> $lines
     *
     * @return list<string>
     */
    private function texts(array $lines): array
    {
        return array_map(static fn (array $line): string => $line['text'], $lines);
    }

    public function aHealthyWebhookShowsTheAddressThePendingCountAndNoError(): void
    {
        $lines = WebhookInfoReport::lines($this->info(pending: 3), true);

        Assert::same($this->texts($lines), [
            'Here is your currently setup webhook url: ' . self::URL,
            'Pending Update Count: 3',
            WebhookInfoReport::NO_ERROR,
        ]);
        Assert::same(array_column($lines, 'warning'), [false, false, false]);
    }

    public function theLastErrorIsShownWithItsTimeInUtcAndFlaggedAsAWarning(): void
    {
        $when = new DateTimeImmutable('2026-11-18 13:05:11', new \DateTimeZone('UTC'));
        $lines = WebhookInfoReport::lines(
            $this->info(error: 'Wrong response from the webhook: 403 Forbidden', errorDate: $when),
            true,
        );

        Assert::same($lines[2], [
            'text' => 'Last error: Wrong response from the webhook: 403 Forbidden (2026-11-18 13:05:11 UTC)',
            'warning' => true,
        ]);
        Assert::same($lines[3], ['text' => WebhookInfoReport::ERROR_STAYS, 'warning' => false]);
    }

    public function aTimeInAnotherZoneIsShownInUtc(): void
    {
        $when = new DateTimeImmutable('2026-11-18 15:05:11', new \DateTimeZone('Europe/London'));
        $text = WebhookInfoReport::lines($this->info(error: 'Connection timed out', errorDate: $when), true)[2]['text'];

        Assert::same($text, 'Last error: Connection timed out (2026-11-18 15:05:11 UTC)');
    }

    public function anErrorWithoutATimeStillShowsTheMessage(): void
    {
        $text = WebhookInfoReport::lines($this->info(error: 'Connection timed out'), true)[2]['text'];

        Assert::same($text, 'Last error: Connection timed out');
    }

    public function aBlankErrorMessageCountsAsNoError(): void
    {
        foreach ([null, '', '   '] as $blank) {
            Assert::same(WebhookInfoReport::lines($this->info(error: $blank), true)[2]['text'], WebhookInfoReport::NO_ERROR);
        }
    }

    public function noSecretTokenIsWarnedAboutFirst(): void
    {
        $lines = WebhookInfoReport::lines($this->info(), false);

        Assert::same($lines[0], ['text' => WebhookInfoReport::NO_SECRET, 'warning' => true]);
        Assert::same(count($lines), 4);
    }

    public function noWebhookAtAllSaysYouAreUsingGetUpdates(): void
    {
        $lines = WebhookInfoReport::lines($this->info(url: ''), true);

        Assert::same($lines[0]['text'], WebhookInfoReport::NO_WEBHOOK);
    }

    public function aSynchronisationErrorIsShownToo(): void
    {
        $when = new DateTimeImmutable('2026-11-17 08:00:00', new \DateTimeZone('UTC'));
        $lines = WebhookInfoReport::lines($this->info(syncDate: $when), true);

        Assert::same(
            end($lines),
            [
                'text' => 'Telegram last failed to synchronise this webhook with its servers (2026-11-17 08:00:00 UTC).',
                'warning' => true,
            ],
        );
    }
}
