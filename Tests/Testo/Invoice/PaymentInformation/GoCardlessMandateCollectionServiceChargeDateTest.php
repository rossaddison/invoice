<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\GoCardlessMandateCollectionService;
use DateTimeImmutable;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers GoCardlessMandateCollectionService::resolveChargeDate() — GoCardless
 * enforces its own minimum charge-date lead time per payment scheme and
 * rejects an earlier date outright, so this clamps forward to a
 * conservative default (5 days) whenever date_due is sooner than that,
 * and otherwise uses date_due as-is.
 */
#[Test]
final class GoCardlessMandateCollectionServiceChargeDateTest
{
    private function resolveChargeDate(DateTimeImmutable $dateDue): DateTimeImmutable
    {
        $reflectionClass = new ReflectionClass(GoCardlessMandateCollectionService::class);
        $service = $reflectionClass->newInstanceWithoutConstructor();
        $method = $reflectionClass->getMethod('resolveChargeDate');

        /** @var DateTimeImmutable */
        return $method->invoke($service, $dateDue);
    }

    public function usesDateDueWhenAlreadyBeyondTheMinimumLeadTime(): void
    {
        $farFuture = new DateTimeImmutable('+30 days');

        Assert::same($farFuture->format('Y-m-d'), $this->resolveChargeDate($farFuture)->format('Y-m-d'));
    }

    public function clampsForwardWhenDateDueIsTooSoon(): void
    {
        $tomorrow = new DateTimeImmutable('+1 day');

        $result = $this->resolveChargeDate($tomorrow);

        Assert::true($result > $tomorrow);
    }

    public function clampsForwardWhenDateDueIsInThePast(): void
    {
        $lastWeek = new DateTimeImmutable('-7 days');

        $result = $this->resolveChargeDate($lastWeek);

        Assert::true($result > new DateTimeImmutable());
    }
}
