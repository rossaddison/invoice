<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation\GatewayStatus;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusFilter;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRows;
use Testo\Assert;
use Testo\Test;

/**
 * Covers GatewayStatusRows::filter() -- three independent array_filter()
 * passes that must AND together (each filter that's actually set narrows
 * the result further, none silently replaces an earlier one), the same
 * discipline InvCombinedFilterTrait exists to guarantee for inv/index's
 * own multi-filter query. This exercises it against the flat
 * list<GatewayStatus> /gateway-status actually works with.
 */
#[Test]
final class GatewayStatusRowsTest
{
    /**
     * @param list<string> $regions
     */
    private function makeGateway(
        string $key,
        array $regions,
        ?string $sandboxStatus,
        string $lastUpdated,
        ?string $liveTestedAt,
    ): GatewayStatus {
        $entity = new GatewayStatus();
        $entity->setGatewayKey($key);
        $entity->setRegions(implode(',', $regions));
        $entity->setSandboxStatus($sandboxStatus);
        $entity->setLastUpdated($lastUpdated);
        $entity->setLiveTestedAt($liveTestedAt);
        return $entity;
    }

    /**
     * @return list<GatewayStatus>
     */
    private function fixtureRows(): array
    {
        return [
            // Asia, passing sandbox, up to date.
            $this->makeGateway('robokassa', ['asia', 'europe'], 'pass', '2026-08-04', '2026-08-04'),
            // Europe, passing sandbox, needs retest (never live tested).
            $this->makeGateway('gocardless', ['europe', 'north america'], 'pass', '2026-09-05', null),
            // Asia, failing sandbox, up to date.
            $this->makeGateway('razorpay', ['asia'], 'fail', '2026-07-01', '2026-07-01'),
        ];
    }

    public function noFilterValuesSetReturnsEveryRowUnchanged(): void
    {
        $filter = new GatewayStatusFilter();
        $result = GatewayStatusRows::filter($this->fixtureRows(), $filter);
        Assert::same(3, count($result));
    }

    public function regionAloneNarrowsToMatchingRows(): void
    {
        $filter = new GatewayStatusFilter();
        $filter->filterRegion = 'asia';
        $result = GatewayStatusRows::filter($this->fixtureRows(), $filter);
        Assert::same(2, count($result));
        Assert::same('robokassa', $result[0]->getGatewayKey());
        Assert::same('razorpay', $result[1]->getGatewayKey());
    }

    public function sandboxStatusAloneNarrowsToMatchingRows(): void
    {
        $filter = new GatewayStatusFilter();
        $filter->filterSandboxStatus = 'pass';
        $result = GatewayStatusRows::filter($this->fixtureRows(), $filter);
        Assert::same(2, count($result));
        Assert::same('robokassa', $result[0]->getGatewayKey());
        Assert::same('gocardless', $result[1]->getGatewayKey());
    }

    public function needsRetestAloneNarrowsToMatchingRows(): void
    {
        $filter = new GatewayStatusFilter();
        $filter->filterNeedsRetest = 'yes';
        $result = GatewayStatusRows::filter($this->fixtureRows(), $filter);
        Assert::same(1, count($result));
        Assert::same('gocardless', $result[0]->getGatewayKey());
    }

    public function needsRetestNoNarrowsToUpToDateRows(): void
    {
        $filter = new GatewayStatusFilter();
        $filter->filterNeedsRetest = 'no';
        $result = GatewayStatusRows::filter($this->fixtureRows(), $filter);
        Assert::same(2, count($result));
    }

    public function allThreeFiltersCombineByAndingNotByOverwriting(): void
    {
        // Only gocardless is europe AND pass AND needs-retest -- confirms
        // this doesn't silently apply only the last filter set.
        $filter = new GatewayStatusFilter();
        $filter->filterRegion = 'europe';
        $filter->filterSandboxStatus = 'pass';
        $filter->filterNeedsRetest = 'yes';
        $result = GatewayStatusRows::filter($this->fixtureRows(), $filter);
        Assert::same(1, count($result));
        Assert::same('gocardless', $result[0]->getGatewayKey());
    }

    public function combinedFiltersThatMatchNothingReturnAnEmptyList(): void
    {
        $filter = new GatewayStatusFilter();
        $filter->filterRegion = 'asia';
        $filter->filterSandboxStatus = 'pass';
        $filter->filterNeedsRetest = 'yes';
        $result = GatewayStatusRows::filter($this->fixtureRows(), $filter);
        Assert::same(0, count($result));
    }

    public function anUnrecognizedNeedsRetestValueIsIgnored(): void
    {
        $filter = new GatewayStatusFilter();
        $filter->filterNeedsRetest = 'garbage';
        $result = GatewayStatusRows::filter($this->fixtureRows(), $filter);
        Assert::same(3, count($result));
    }
}
