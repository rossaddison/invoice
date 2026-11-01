<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\CopyCreatedDates;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CopyCreatedDatesTest
{
    public function noDatesFallsBackToToday(): void
    {
        Assert::same(CopyCreatedDates::fromRequest([]), ['']);
    }

    public function keepsEveryValidDate(): void
    {
        Assert::same(
            CopyCreatedDates::fromRequest(['modal_created_dates' => ['2026-11-03', '2026-11-04']]),
            ['2026-11-03', '2026-11-04']
        );
    }

    public function removesDuplicatesAndReindexes(): void
    {
        Assert::same(
            CopyCreatedDates::fromRequest(['modal_created_dates' => ['2026-11-03', '2026-11-03', '2026-11-05']]),
            ['2026-11-03', '2026-11-05']
        );
    }

    public function rejectsRolledOverAndMalformedDates(): void
    {
        Assert::same(
            CopyCreatedDates::fromRequest(['modal_created_dates' => ['2026-02-31', '03/11/2026', 'garbage', '']]),
            ['']
        );
    }

    public function rejectsNonScalarEntries(): void
    {
        Assert::same(
            CopyCreatedDates::fromRequest(['modal_created_dates' => [['2026-11-03'], '2026-11-04']]),
            ['2026-11-04']
        );
    }

    public function legacySingleDateIsHonouredWhenNewParameterAbsent(): void
    {
        Assert::same(CopyCreatedDates::fromRequest(['modal_created_date' => '2026-11-03']), ['2026-11-03']);
    }

    public function newParameterTakesPriorityOverLegacy(): void
    {
        Assert::same(
            CopyCreatedDates::fromRequest([
                'modal_created_dates' => ['2026-11-05'],
                'modal_created_date'  => '2026-11-03',
            ]),
            ['2026-11-05']
        );
    }

    public function withinLimitsAcceptsTypicalRequest(): void
    {
        Assert::true(CopyCreatedDates::withinLimits(3, ['2026-11-03', '2026-11-04'], 2));
    }

    public function noClientsCountsAsOneTarget(): void
    {
        // 500 invoices x 1 date x (0 clients -> 1) is exactly the cap.
        Assert::true(CopyCreatedDates::withinLimits(500, ['2026-11-03'], 0));
        Assert::false(CopyCreatedDates::withinLimits(501, ['2026-11-03'], 0));
    }

    public function tooManyTotalCopiesIsRejected(): void
    {
        Assert::false(CopyCreatedDates::withinLimits(20, array_fill(0, 20, 'x'), 2));
    }

    public function tooManyDatesIsRejected(): void
    {
        Assert::false(CopyCreatedDates::withinLimits(1, array_fill(0, 63, 'x'), 1));
        Assert::true(CopyCreatedDates::withinLimits(1, array_fill(0, 62, 'x'), 1));
    }
}
