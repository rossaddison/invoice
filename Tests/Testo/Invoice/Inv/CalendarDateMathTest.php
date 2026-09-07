<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvController;
use Mockery as m;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers Trait\Calendar's pure date/bucketing logic -- the part of
 * inv/calendar (PR #1248-#1250) and inv/guest/calendar (PR #1252) that
 * shipped with no dedicated coverage at all (SonarCloud new_coverage,
 * merged past with --admin three times running). None of these methods
 * touch any InvController property, so newInstanceWithoutConstructor()
 * needs no reflective property injection at all -- the same technique
 * HomeCareRunContextResolutionTest.php already uses for a different
 * private method on this same class.
 */
#[Test]
final class CalendarDateMathTest
{
    private function invoke(string $method, array $args): mixed
    {
        $reflectionClass = new ReflectionClass(InvController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();
        $reflectionMethod = $reflectionClass->getMethod($method);
        return $reflectionMethod->invoke($controller, ...$args);
    }

    /**
     * A real @return docblock (rather than an inline @var right before a
     * computed array-offset expression, which Psalm doesn't reliably
     * propagate through) is what actually narrows calendarBuildWeeks()'s
     * mixed-typed reflection result back down to something the rest of a
     * test method can safely index.
     * @return list<list<\DateTimeImmutable>>
     */
    private function invokeWeeks(\DateTimeImmutable $target): array
    {
        /** @var list<list<\DateTimeImmutable>> */
        return $this->invoke('calendarBuildWeeks', [$target]);
    }

    /** @return list<\DateTimeImmutable> */
    private function invokeWindowMonths(\DateTimeImmutable $target): array
    {
        /** @var list<\DateTimeImmutable> */
        return $this->invoke('calendarWindowMonths', [$target]);
    }

    /** @return array<string, array<int, int>> */
    private function invokeBucket(iterable $invoices): array
    {
        /** @var array<string, array<int, int>> */
        return $this->invoke('calendarBucketInvoices', [$invoices]);
    }

    /**
     * Bounds a "resolves to the current month" assertion with "now"
     * captured both before and after the production call, so a real
     * midnight rollover landing between the two can't flake the test
     * (CodeRabbit, PR #1253).
     */
    private function assertMatchesCurrentMonth(
        \DateTimeImmutable $target,
        \DateTimeImmutable $before,
        \DateTimeImmutable $after,
    ): void {
        Assert::true(
            $target->format('Y-m') === $before->format('Y-m')
                || $target->format('Y-m') === $after->format('Y-m'),
            sprintf(
                'expected target month %s to match "now" captured before (%s) or after (%s) the call',
                $target->format('Y-m'),
                $before->format('Y-m'),
                $after->format('Y-m'),
            ),
        );
    }

    // -- calendarResolveTargetMonth() ---------------------------------

    public function emptyYearAndMonthResolveToTheCurrentMonthAtMidnight(): void
    {
        $before = new \DateTimeImmutable('today');
        /** @var \DateTimeImmutable $target */
        $target = $this->invoke('calendarResolveTargetMonth', ['', '']);
        $after = new \DateTimeImmutable('today');

        $this->assertMatchesCurrentMonth($target, $before, $after);
        Assert::same($target->format('d'), '01');
        Assert::same($target->format('H:i:s'), '00:00:00');
    }

    public function aValidYearAndMonthResolveExactly(): void
    {
        /** @var \DateTimeImmutable $target */
        $target = $this->invoke('calendarResolveTargetMonth', ['2026', '3']);

        Assert::same('2026-03-01 00:00:00', $target->format('Y-m-d H:i:s'));
    }

    public function aRolloverMonthIsRejectedRatherThanSilentlyNormalized(): void
    {
        // createFromFormat('Y-n-j', '2026-13-1') doesn't fail -- it
        // silently normalizes to 2027-01-01, only surfacing via
        // getLastErrors()'s warning_count (confirmed live, PR #1248's
        // CodeRabbit pass). This locks in the fix: falls back to "now"
        // instead of accepting the rolled-over date.
        $before = new \DateTimeImmutable('today');
        /** @var \DateTimeImmutable $target */
        $target = $this->invoke('calendarResolveTargetMonth', ['2026', '13']);
        $after = new \DateTimeImmutable('today');

        Assert::notSame('2027-01', $target->format('Y-m'));
        $this->assertMatchesCurrentMonth($target, $before, $after);
    }

    public function onlyOneOfYearOrMonthPresentFallsBackToTheCurrentMonth(): void
    {
        $before = new \DateTimeImmutable('today');
        /** @var \DateTimeImmutable $target */
        $target = $this->invoke('calendarResolveTargetMonth', ['2026', '']);
        $after = new \DateTimeImmutable('today');

        $this->assertMatchesCurrentMonth($target, $before, $after);
    }

    // -- calendarWindowMonths() ----------------------------------------

    public function windowSpansFiveMonthsBackThroughOneMonthForwardOldestFirst(): void
    {
        $target = new \DateTimeImmutable('2026-09-01');

        $months = $this->invokeWindowMonths($target);

        Assert::same(7, count($months));
        Assert::same('2026-04-01', $months[0]->format('Y-m-d'));
        Assert::same('2026-09-01', $months[5]->format('Y-m-d'));
        Assert::same('2026-10-01', $months[6]->format('Y-m-d'));
    }

    public function windowMonthsAreConsecutiveWithNoGapOrRepeat(): void
    {
        $target = new \DateTimeImmutable('2026-01-01');

        $months = $this->invokeWindowMonths($target);

        for ($i = 1; $i < count($months); $i++) {
            $expected = $months[$i - 1]->modify('+1 month')->format('Y-m');
            Assert::same($expected, $months[$i]->format('Y-m'));
        }
    }

    // -- calendarBuildWeeks() -------------------------------------------

    public function everyWeekHasExactlySevenConsecutiveDays(): void
    {
        $target = new \DateTimeImmutable('2026-09-01');

        $weeks = $this->invokeWeeks($target);

        foreach ($weeks as $week) {
            Assert::same(7, count($week));
            for ($i = 1; $i < 7; $i++) {
                $expected = $week[$i - 1]->modify('+1 day')->format('Y-m-d');
                Assert::same($expected, $week[$i]->format('Y-m-d'));
            }
        }
    }

    public function gridStartsOnAMondayAndEndsOnASunday(): void
    {
        $target = new \DateTimeImmutable('2026-09-01');

        $weeks = $this->invokeWeeks($target);

        Assert::same('1', $weeks[0][0]->format('N'));
        $lastWeekIndex = array_key_last($weeks);
        Assert::notNull($lastWeekIndex);
        Assert::same('7', $weeks[$lastWeekIndex][6]->format('N'));
    }

    public function everyDayOfTheTargetMonthIsPresentInTheGrid(): void
    {
        $target = new \DateTimeImmutable('2026-09-01');

        $weeks = $this->invokeWeeks($target);

        $gridDays = [];
        foreach ($weeks as $week) {
            foreach ($week as $day) {
                $gridDays[] = $day->format('Y-m-d');
            }
        }

        for ($day = 1; $day <= 30; $day++) {
            Assert::true(in_array(sprintf('2026-09-%02d', $day), $gridDays, true));
        }
    }

    public function februaryInALeapYearBuildsTwentyNineDaysWithoutError(): void
    {
        $target = new \DateTimeImmutable('2028-02-01');

        $weeks = $this->invokeWeeks($target);

        $gridDays = [];
        foreach ($weeks as $week) {
            foreach ($week as $day) {
                $gridDays[] = $day->format('Y-m-d');
            }
        }

        Assert::true(in_array('2028-02-29', $gridDays, true));
        Assert::false(in_array('2028-03-01', array_slice($gridDays, 0, count($gridDays) - 7), true));
    }

    // -- calendarBucketInvoices() ---------------------------------------

    private function makeInv(string $dateCreated, ?int $categorySecondaryId): Inv&m\MockInterface
    {
        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('getFirstItemCategorySecondaryId')->andReturn($categorySecondaryId);
        if ($categorySecondaryId !== null) {
            $inv->shouldReceive('getDateCreated')->andReturn(new \DateTimeImmutable($dateCreated));
        }
        return $inv;
    }

    public function invoicesAreBucketedByDayAndCategorySecondaryIdWithCounts(): void
    {
        $invoices = [
            $this->makeInv('2026-09-03 10:00:00', 7),
            $this->makeInv('2026-09-03 14:00:00', 7),
            $this->makeInv('2026-09-04 09:00:00', 12),
        ];

        $days = $this->invokeBucket($invoices);

        Assert::same(2, $days['2026-09-03'][7]);
        Assert::same(1, $days['2026-09-04'][12]);
        Assert::false(isset($days['2026-09-04'][7]));
    }

    public function invoicesWithNoCategorySecondaryAreSkipped(): void
    {
        $invoices = [
            $this->makeInv('2026-09-03', null),
            $this->makeInv('2026-09-03 10:00:00', 7),
        ];

        $days = $this->invokeBucket($invoices);

        Assert::same(1, count($days));
        Assert::same(1, $days['2026-09-03'][7]);
    }

    public function noInvoicesProducesAnEmptyBucketArray(): void
    {
        $days = $this->invokeBucket([]);

        Assert::same(0, count($days));
    }

    // -- calendarModify() -------------------------------------------------

    public function modifySucceedsWithAKnownGoodModifier(): void
    {
        $date = new \DateTimeImmutable('2026-09-30');

        /** @var \DateTimeImmutable $result */
        $result = $this->invoke('calendarModify', [$date, '+1 day']);

        Assert::same('2026-10-01', $result->format('Y-m-d'));
    }

    // -- calendarStyleCss() -------------------------------------------------

    public function styleCssRendersARealStyleTagTargetingTheCarousel(): void
    {
        /** @var string $css */
        $css = $this->invoke('calendarStyleCss', []);

        Assert::true(str_contains($css, '<style'));
        Assert::true(str_contains($css, '#inv-calendar-months'));
        Assert::true(str_contains($css, '--calendar-accent'));
    }
}
