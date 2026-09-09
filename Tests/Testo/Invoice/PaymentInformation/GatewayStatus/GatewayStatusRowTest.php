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
    private function makeRow(?string $sandboxExpiryDate): GatewayStatusRow
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
            sandboxExpiryDate: $sandboxExpiryDate,
            regions: [],
            notes: null,
        );
    }

    public function isExpiredReturnsFalseWhenNoSandboxExpiryDateSet(): void
    {
        $row = $this->makeRow(null);
        Assert::false($row->isExpired('2026-08-08'));
    }

    public function isExpiredReturnsFalseWhenSandboxExpiryDateIsInTheFuture(): void
    {
        $row = $this->makeRow('2026-08-09');
        Assert::false($row->isExpired('2026-08-08'));
    }

    public function isExpiredReturnsTrueWhenSandboxExpiryDateIsToday(): void
    {
        $row = $this->makeRow('2026-08-08');
        Assert::true($row->isExpired('2026-08-08'));
    }

    public function isExpiredReturnsTrueWhenSandboxExpiryDateIsInThePast(): void
    {
        $row = $this->makeRow('2026-01-01');
        Assert::true($row->isExpired('2026-08-08'));
    }

    private function makeRowWithLiveTest(string $lastUpdated, ?string $liveTestedAt): GatewayStatusRow
    {
        return new GatewayStatusRow(
            key: 'gocardless',
            name: 'GoCardless',
            composerPackage: 'gocardless/gocardless-pro',
            sdkVersion: '8.1.1',
            lastUpdated: $lastUpdated,
            sandboxEnvVars: [],
            sandboxTestedAt: null,
            sandboxStatus: null,
            sandboxLastError: null,
            liveTestedAt: $liveTestedAt,
            sandboxExpiryDate: null,
            regions: [],
            notes: null,
        );
    }

    public function needsRetestSinceUpdateReturnsTrueWhenNeverLiveTested(): void
    {
        $row = $this->makeRowWithLiveTest('2026-09-05', null);
        Assert::true($row->needsRetestSinceUpdate());
    }

    public function needsRetestSinceUpdateReturnsTrueWhenLiveTestPredatesTheUpdate(): void
    {
        $row = $this->makeRowWithLiveTest('2026-09-05', '2026-08-16');
        Assert::true($row->needsRetestSinceUpdate());
    }

    public function needsRetestSinceUpdateReturnsFalseWhenLiveTestedTheSameDayAsTheUpdate(): void
    {
        $row = $this->makeRowWithLiveTest('2026-09-05', '2026-09-05');
        Assert::false($row->needsRetestSinceUpdate());
    }

    public function needsRetestSinceUpdateReturnsFalseWhenLiveTestedAfterTheUpdate(): void
    {
        $row = $this->makeRowWithLiveTest('2026-09-05', '2026-09-07');
        Assert::false($row->needsRetestSinceUpdate());
    }

    /**
     * withSdkVersion()/withSandboxResult() reconstruct a new instance via
     * positional new self(...) -- confirms feePercent/feeSummary are
     * threaded through both rather than silently reset to null (the exact
     * bug this test would have caught when those two fields were added).
     */
    public function withSdkVersionPreservesFeePercentAndFeeSummary(): void
    {
        $row = new GatewayStatusRow(
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
            sandboxExpiryDate: null,
            regions: [],
            notes: null,
            feePercent: 1.5,
            feeSummary: '1.5% + 20p (UK cards)',
        );

        $updated = $row->withSdkVersion('v21.2.0', '2026-09-09');

        Assert::same('v21.2.0', $updated->sdkVersion);
        Assert::same(1.5, $updated->feePercent);
        Assert::same('1.5% + 20p (UK cards)', $updated->feeSummary);
    }

    public function withSandboxResultPreservesFeePercentAndFeeSummary(): void
    {
        $row = new GatewayStatusRow(
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
            sandboxExpiryDate: null,
            regions: [],
            notes: null,
            feePercent: 1.5,
            feeSummary: '1.5% + 20p (UK cards)',
        );

        $updated = $row->withSandboxResult('2026-09-09', 'pass', null);

        Assert::same('pass', $updated->sandboxStatus);
        Assert::same(1.5, $updated->feePercent);
        Assert::same('1.5% + 20p (UK cards)', $updated->feeSummary);
    }
}
