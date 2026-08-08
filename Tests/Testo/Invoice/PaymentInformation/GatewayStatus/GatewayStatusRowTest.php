<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation\GatewayStatus;

use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRow;
use Testo\Assert;
use Testo\Test;

/**
 * Covers GatewayStatusRow::isExpired() — the date-comparison logic
 * CheckGatewaySandboxesCommand's Telegram notification relies on to decide
 * which gateways to report as expired. See that command's own docblock and
 * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md.
 */
#[Test]
final class GatewayStatusRowTest
{
    private function makeRow(?string $expiryDate): GatewayStatusRow
    {
        return new GatewayStatusRow(
            key: 'stripe',
            name: 'Stripe',
            composerPackage: 'stripe/stripe-php',
            sdkVersion: 'v21.1.1',
            lastUpdated: '2026-08-04',
            sandboxEnvVars: [],
            sandboxTestedAt: null,
            sandboxStatus: null,
            sandboxLastError: null,
            liveTestedAt: null,
            expiryDate: $expiryDate,
            regions: [],
            notes: null,
        );
    }

    public function isExpiredReturnsFalseWhenNoExpiryDateSet(): void
    {
        $row = $this->makeRow(null);
        Assert::false($row->isExpired('2026-08-08'));
    }

    public function isExpiredReturnsFalseWhenExpiryDateIsInTheFuture(): void
    {
        $row = $this->makeRow('2026-08-09');
        Assert::false($row->isExpired('2026-08-08'));
    }

    public function isExpiredReturnsTrueWhenExpiryDateIsToday(): void
    {
        $row = $this->makeRow('2026-08-08');
        Assert::true($row->isExpired('2026-08-08'));
    }

    public function isExpiredReturnsTrueWhenExpiryDateIsInThePast(): void
    {
        $row = $this->makeRow('2026-01-01');
        Assert::true($row->isExpired('2026-08-08'));
    }
}
