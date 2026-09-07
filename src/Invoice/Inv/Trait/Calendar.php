<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\Inv\InvRepository as IR;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * inv/calendar: a mobile-first Carousel, one slide per month, linking each
 * (category_secondary, exact date) "run" straight into inv/index via
 * filterCategorySecondaryRun + filterDateCreatedExact (see
 * InvIndexFilter/InvCombinedFilterTrait). Populates itself automatically
 * from whatever date_created values invoices already carry — including
 * ones just stamped by inv/copyalltodate — no separate data entry.
 *
 * The carousel preloads a bounded window of months around the requested
 * one (MONTHS_BEFORE back, MONTHS_AFTER forward) so swiping feels instant;
 * the year/month route args (the « Prev / Next » buttons) re-center that
 * window on a different month rather than trying to load the invoice
 * table's entire history into one request.
 */
trait Calendar
{
    private const string FIRST_DAY_OF_MONTH = 'first day of this month';
    private const int MONTHS_BEFORE = 5;
    private const int MONTHS_AFTER = 1;

    public function calendar(
        IR $iR,
        CSR $csR,
        #[RouteArgument('year')] string $year = '',
        #[RouteArgument('month')] string $month = '',
    ): Response {
        $target = $this->calendarResolveTargetMonth($year, $month);
        $windowMonths = $this->calendarWindowMonths($target);
        $windowStart = $this->calendarModify($target, '-' . self::MONTHS_BEFORE . ' months');
        $windowEnd = $this->calendarModify($target, '+' . (self::MONTHS_AFTER + 1) . ' months');
        $days = $this->calendarBucketInvoicesByDay($iR, $windowStart, $windowEnd);
        $categoryNames = $csR->optionsDataCategorySecondaries();

        $months = [];
        foreach ($windowMonths as $monthStart) {
            $months[] = [
                'monthStart' => $monthStart,
                'month'      => (int) $monthStart->format('n'),
                'weeks'      => $this->calendarBuildWeeks($monthStart),
                'active'     => $monthStart->format('Y-m') === $target->format('Y-m'),
            ];
        }

        return $this->webViewRenderer->render('calendar', [
            'alert'          => $this->alert(),
            'monthStart'     => $target,
            'months'         => $months,
            'days'           => $days,
            'categoryNames'  => $categoryNames,
            'prevMonth'      => $this->calendarModify($target, '-1 month'),
            'nextMonth'      => $this->calendarModify($target, '+1 month'),
            'today'          => new \DateTimeImmutable('today'),
            // LayoutViewInjection::resolveBootstrapSettings() only reaches
            // the *layout* template (invoice.php implements
            // LayoutParametersInjectionInterface, not
            // CommonParametersInjectionInterface) -- content views like
            // this one don't inherit it, confirmed live (ErrorException:
            // undefined $bootstrap5CalendarAccentColor). Read directly.
            'bootstrap5CalendarAccentColor' =>
                $this->sR->getSetting('bootstrap5_calendar_accent_color') ?: 'primary',
        ]);
    }

    /**
     * Defaults to the current month; route-supplied year/month (calendar's
     * own prev/next navigation) override it. Invalid input falls back to
     * "now" rather than producing an out-of-range DateTimeImmutable.
     *
     * CodeRabbit (PR #1248), both confirmed live:
     *  - createFromFormat() doesn't return false for a rollover date like
     *    'Y-n-j' 2026-13-1 -- it silently returns 2027-01-01 instead, only
     *    surfacing the problem via getLastErrors()'s warning_count. Without
     *    checking it, an out-of-range month/day in the URL would silently
     *    resolve to some other month rather than falling back to "now".
     *  - the "now" fallback itself carried the current wall-clock time
     *    (new DateTimeImmutable('first day of this month') at 18:00 today
     *    returns "...-01 18:00:00", not midnight) while the route-arg
     *    branch above it already normalized to midnight -- an inconsistency
     *    that mattered once $target feeds $windowStart in calendar():
     *    invoices created earlier the same day, or on days 1 through
     *    today-1 of that month before that exact time, would have been
     *    silently excluded from the oldest visible month.
     */
    private function calendarResolveTargetMonth(string $year, string $month): \DateTimeImmutable
    {
        if ($year !== '' && $month !== '') {
            $candidate = \DateTimeImmutable::createFromFormat(
                'Y-n-j',
                $year . '-' . $month . '-1'
            );
            $errors = \DateTimeImmutable::getLastErrors();
            $hasParseIssue = $errors !== false
                && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
            if ($candidate instanceof \DateTimeImmutable && !$hasParseIssue) {
                return $this->calendarModify($candidate, self::FIRST_DAY_OF_MONTH)
                    ->setTime(0, 0);
            }
        }
        return (new \DateTimeImmutable(self::FIRST_DAY_OF_MONTH))->setTime(0, 0);
    }

    /**
     * MONTHS_BEFORE months back through MONTHS_AFTER months forward,
     * oldest first, $target included.
     * @return list<\DateTimeImmutable>
     */
    private function calendarWindowMonths(\DateTimeImmutable $target): array
    {
        $months = [];
        for ($offset = -self::MONTHS_BEFORE; $offset <= self::MONTHS_AFTER; $offset++) {
            $months[] = $this->calendarModify($target, $offset . ' months');
        }
        return $months;
    }

    private function calendarBucketInvoicesByDay(
        IR $iR,
        \DateTimeImmutable $from,
        \DateTimeImmutable $toExclusive,
    ): array {
        return $this->calendarBucketInvoices($iR->repoDateRangeQuery($from, $toExclusive));
    }

    /**
     * Pure bucketing, no query of its own -- split out (originally inline
     * here) so Trait\GuestCalendar can reuse it against its own
     * worker-/client-scoped queries (InvGuestTrait::repoWorkerDateRangeQuery()/
     * repoGuestClientsDateRangeQuery()) instead of the unscoped
     * repoDateRangeQuery() above, which would leak every client's invoices
     * to a signed-in guest.
     * @return array<string, array<int, int>> [day 'Y-m-d' => [categorySecondaryId => invoice count]]
     */
    private function calendarBucketInvoices(iterable $invoices): array
    {
        $days = [];
        /** @var Inv $inv */
        foreach ($invoices as $inv) {
            $categorySecondaryId = $inv->getFirstItemCategorySecondaryId();
            if ($categorySecondaryId === null) {
                continue;
            }
            $day = $inv->getDateCreated()->format('Y-m-d');
            $days[$day][$categorySecondaryId] = ($days[$day][$categorySecondaryId] ?? 0) + 1;
        }
        return $days;
    }

    /**
     * Monday-first weeks covering the target month, padded with the
     * leading/trailing days of adjacent months so every week has 7 cells --
     * kept as plain DateTimeImmutable, the view decides how to grey out the
     * out-of-month padding.
     * @return list<list<\DateTimeImmutable>>
     */
    private function calendarBuildWeeks(\DateTimeImmutable $target): array
    {
        $firstOfMonth = $this->calendarModify($target, self::FIRST_DAY_OF_MONTH)->setTime(0, 0);
        $leadingBlanks = ((int) $firstOfMonth->format('N')) - 1;
        $gridStart = $this->calendarModify($firstOfMonth, '-' . $leadingBlanks . ' days');

        $lastOfMonth = $this->calendarModify($target, 'last day of this month')->setTime(0, 0);
        $trailingBlanks = 7 - ((int) $lastOfMonth->format('N'));
        $gridEnd = $this->calendarModify($lastOfMonth, '+' . $trailingBlanks . ' days');

        $weeks = [];
        $week = [];
        $cursor = $gridStart;
        while ($cursor <= $gridEnd) {
            $week[] = $cursor;
            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
            $cursor = $this->calendarModify($cursor, '+1 day');
        }
        return $weeks;
    }

    /**
     * DateTimeImmutable::modify() is typed to return `static|false`, but
     * every modifier string this trait ever passes it is a fixed, known-
     * valid relative expression -- never user input -- so the `false`
     * branch is unreachable in practice. Narrowing it here once keeps
     * every call site above typed as plain DateTimeImmutable instead of
     * repeating an instanceof check (or a @psalm-suppress, which this
     * project avoids) at every ->modify() call.
     */
    private function calendarModify(\DateTimeImmutable $date, string $modifier): \DateTimeImmutable
    {
        $modified = $date->modify($modifier);
        return $modified instanceof \DateTimeImmutable ? $modified : $date;
    }
}
