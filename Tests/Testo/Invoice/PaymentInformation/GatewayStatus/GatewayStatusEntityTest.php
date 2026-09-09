<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation\GatewayStatus;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use Testo\Assert;
use Testo\Test;

/**
 * Covers GatewayStatus::getNeedsRetestSinceUpdate() -- the entity-side
 * mirror of GatewayStatusRow::needsRetestSinceUpdate() that
 * SiteController::gatewayStatus() actually calls (it queries GatewayStatus
 * entities via GatewayStatusRepository, not GatewayStatusRow -- that class
 * is only ever built from gateways.json by GatewayStatusService). Same
 * boundary cases as GatewayStatusRowTest's own coverage of the JSON-side
 * twin.
 */
#[Test]
final class GatewayStatusEntityTest
{
    private function makeEntity(string $lastUpdated, ?string $liveTestedAt): GatewayStatus
    {
        $entity = new GatewayStatus();
        $entity->setLastUpdated($lastUpdated);
        $entity->setLiveTestedAt($liveTestedAt);
        return $entity;
    }

    public function needsRetestSinceUpdateReturnsTrueWhenNeverLiveTested(): void
    {
        $entity = $this->makeEntity('2026-09-05', null);
        Assert::true($entity->getNeedsRetestSinceUpdate());
    }

    public function needsRetestSinceUpdateReturnsTrueWhenLiveTestPredatesTheUpdate(): void
    {
        $entity = $this->makeEntity('2026-09-05', '2026-08-16');
        Assert::true($entity->getNeedsRetestSinceUpdate());
    }

    public function needsRetestSinceUpdateReturnsFalseWhenLiveTestedTheSameDayAsTheUpdate(): void
    {
        $entity = $this->makeEntity('2026-09-05', '2026-09-05');
        Assert::false($entity->getNeedsRetestSinceUpdate());
    }

    public function needsRetestSinceUpdateReturnsFalseWhenLiveTestedAfterTheUpdate(): void
    {
        $entity = $this->makeEntity('2026-09-05', '2026-09-07');
        Assert::false($entity->getNeedsRetestSinceUpdate());
    }

    public function feePercentAndFeeSummaryDefaultToNull(): void
    {
        $entity = new GatewayStatus();
        Assert::null($entity->getFeePercent());
        Assert::null($entity->getFeeSummary());
    }

    public function feePercentAndFeeSummaryRoundTripThroughTheirSetters(): void
    {
        $entity = new GatewayStatus();
        $entity->setFeePercent(1.5);
        $entity->setFeeSummary('1.5% + 20p (UK cards)');

        Assert::same(1.5, $entity->getFeePercent());
        Assert::same('1.5% + 20p (UK cards)', $entity->getFeeSummary());
    }
}
