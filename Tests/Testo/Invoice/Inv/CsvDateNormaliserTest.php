<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\CsvDateNormaliser;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CsvDateNormaliserTest
{
    public function emptyStringReturnsEmpty(): void
    {
        Assert::same(CsvDateNormaliser::normalise(''), '');
    }

    public function isoDatePassesThrough(): void
    {
        Assert::same(CsvDateNormaliser::normalise('2026-06-05'), '2026-06-05');
    }

    public function ukShortYearFormat(): void
    {
        Assert::same(CsvDateNormaliser::normalise('05/06/26'), '2026-06-05');
    }

    public function ukFullYearFormat(): void
    {
        Assert::same(CsvDateNormaliser::normalise('05/06/2026'), '2026-06-05');
    }

    public function usFormatOnlyMatchesWhenDayExceedsTwelve(): void
    {
        // 12/15/2026 — month=12, day=15; d/m/Y fails (month 15 is invalid) so m/d/Y wins
        Assert::same(CsvDateNormaliser::normalise('12/15/2026'), '2026-12-15');
    }

    public function dashSeparatedFormat(): void
    {
        Assert::same(CsvDateNormaliser::normalise('05-06-2026'), '2026-06-05');
    }

    public function dotSeparatedFormat(): void
    {
        Assert::same(CsvDateNormaliser::normalise('05.06.2026'), '2026-06-05');
    }

    public function unrecognisedFormatReturnsEmpty(): void
    {
        Assert::same(CsvDateNormaliser::normalise('not-a-date'), '');
    }

    public function paymentDateEmptyAllowed(): void
    {
        Assert::same(CsvDateNormaliser::normalise(''), '');
    }

    public function endOfMonthDatePreserved(): void
    {
        Assert::same(CsvDateNormaliser::normalise('31/12/2026'), '2026-12-31');
    }
}
