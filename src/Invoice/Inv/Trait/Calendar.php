<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\Inv\InvRepository as IR;
use Yiisoft\Html\Html as H;
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
            // Shared with inv/guest/calendar's own guestCalendar() -- see
            // calendarStyleCss()'s own docblock (SonarCloud
            // new_duplicated_lines_density, PR #1252: the two views had
            // this whole block copy-pasted byte-for-byte).
            'calendarStyle' => $this->calendarStyleCss(),
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
     * Shared #inv-calendar-months carousel styling for both inv/calendar
     * (calendar.php) and inv/guest/calendar (guest_calendar.php) --
     * extracted into a method (SonarCloud new_duplicated_lines_density, PR
     * #1252: the two views had copy-pasted this block byte-for-byte) so
     * every one of the live-feedback fixes below lives, and gets fixed, in
     * exactly one place. A view can't call a controller method directly
     * ($this in a view has no such property -- confirmed live via Psalm's
     * own InvalidScope finding when this was first tried as a view
     * partial calling $this->webViewRenderer), so this returns the
     * rendered <style> markup as a plain string, passed into each view as
     * the 'calendarStyle' parameter and simply echoed there.
     *
     * Bootstrap5's row-cols-* utility only ships up to row-cols-md-6 (its
     * $row-cols SCSS variable defaults to 6 -- confirmed against the
     * vendored bootstrap.min.css, which has no row-cols-md-7 class at
     * all), so a plain 7-day week can't be built from that utility alone.
     * row-cols-1 (each view's own grid markup) still covers the
     * mobile-first stacked list this feature was specifically asked for;
     * this scoped block only adds the even 7-up split from md up, the
     * same way guest.php's own Html::style() block supplements Bootstrap
     * for something its utilities don't cover.
     *
     * The carousel's own prev/next controls default to a 15%-wide
     * clickable zone at each edge (Bootstrap's .carousel-control-prev/
     * -next, hardcoded in bootstrap.css -- no CSS variable to override
     * it), which sits almost exactly on top of the first/last day column
     * of a 7-up grid and would intercept clicks meant for that day's own
     * run badges. Narrowed here to 6%.
     *
     * Live feedback: badges in the leftmost (Monday) column were
     * rendering fine but not actually clickable. Root cause: the
     * control's 6%-wide zone is position:absolute with Bootstrap's own
     * z-index:1, so it painted *above* the day-cards -- which had no
     * z-index of their own outside of the magnifier's hover-only one --
     * and silently swallowed clicks meant for their badges. z-index is
     * the fix; it wins regardless of how much (if any) the two visually
     * overlap.
     *
     * CodeRabbit (PR #1248): raising the cards to z-index:2 fixed the
     * badge clicks above, but at that point the *reverse* problem became
     * possible wherever a card geometrically overlaps the control's own
     * zone -- the card would now paint above the control and could
     * swallow clicks meant for the visible prev/next icon instead.
     * Two-part fix: the wide invisible zone (.carousel-control-prev/-next
     * themselves) gets pointer-events:none, so it never captures a click
     * anywhere along its 6% width; only the small visible icon circle
     * gets pointer-events:auto back, at a z-index above the cards, so the
     * icon itself stays clickable exactly where it's visible and nowhere
     * else -- cards and controls no longer compete for the same clicks at
     * any point in the zone.
     *
     * Live feedback: this wasn't enough on its own -- the arrow icons
     * were hiding behind the cards entirely, not just failing to receive
     * clicks. Root cause: .carousel-control-prev/-next is
     * position:absolute with Bootstrap's own z-index:1 (an integer, not
     * auto), which establishes its *own* stacking context -- a child's
     * z-index only wins *inside* that context, it can't escape to beat a
     * sibling-of-the-carousel .card at the outer level, so the whole
     * control (icon included) still lost regardless of the icon's own
     * z-index. The wrapper itself needs to be raised above the cards too,
     * not just the icon inside it.
     *
     * The controls' visual restyle (live feedback: the default flat
     * chevron read as "black on blue" -- carousel-dark's dark icon plus
     * the browser's blue focus ring, and generally dull) replaces
     * Bootstrap's mask-icon entirely with a solid --calendar-accent
     * circle; calendar.ts (initCalendar) fills it with a real
     * bi-chevron-left/-right glyph, since the Carousel widget's
     * control-icon markup isn't something its PHP API can override.
     *
     * Live feedback: the indicators' Bootstrap default (position:
     * absolute; bottom:0) pinned them to the bottom of the *carousel
     * container*, which (with no fixed height) sized itself to the active
     * slide's full multi-week grid -- so instead of sitting under
     * everything, they visually overlapped the first day-row wherever
     * that bottom edge happened to land. Bootstrap's own renderItems()
     * already outputs the indicators <div> before .carousel-inner in the
     * DOM (confirmed in the widget source), so switching them back to
     * normal static flow puts them at the actual top of the carousel,
     * above the grid, with no extra markup/JS needed.
     *
     * The real-world "this month" dot (see each view's own h5
     * data-current-month marker / calendar.ts) gets a ring, not a
     * different fill color, so it layers cleanly with .active's own
     * opacity/scale rather than fighting over what the dot's base color
     * means. Uses --bs-warning rather than --calendar-accent so it stays
     * visually distinct even when this dot is also the active one (ring
     * color != fill color).
     *
     * Badges still use Bootstrap's text-bg-{accent} utility for its
     * background-color, but override its color: this app's own compiled
     * style.css computes color:#000 (not Bootstrap's stock #fff) for
     * text-bg-primary/success/info/warning/danger/light -- confirmed
     * against the vendored, unmodified node_modules/bootstrap build,
     * which does use white. Black text on a solid accent pill was the
     * live "black on blue" complaint. Scoped to just this badge rather
     * than touching that app-wide SCSS/contrast setting. !important is
     * required, not just belt-and-braces: style.css's own
     * text-bg-{accent} rule is itself !important, and a non-!important
     * override can never beat that regardless of selector specificity or
     * source order.
     */
    private function calendarStyleCss(): string
    {
        return (string) H::style(
            '@media (min-width: 768px) {'
            . '.calendar-week-grid > .col { flex: 0 0 calc(100% / 7); max-width: calc(100% / 7); }'
            . '}'
            . '#inv-calendar-months .carousel-control-prev,'
            . '#inv-calendar-months .carousel-control-next { width: 6%; }'
            . '.calendar-week-grid .card { position: relative; z-index: 2; }'
            . '#inv-calendar-months .carousel-control-prev,'
            . '#inv-calendar-months .carousel-control-next { pointer-events: none; z-index: 3; }'
            . '#inv-calendar-months .carousel-control-prev-icon,'
            . '#inv-calendar-months .carousel-control-next-icon {'
            . 'background-image: none; background-color: var(--calendar-accent);'
            . 'width: 2.5rem; height: 2.5rem; border-radius: 50%;'
            . 'display: flex; align-items: center; justify-content: center;'
            . 'color: #fff; font-size: 1.25rem; position: relative;'
            . 'pointer-events: auto;'
            . 'transition: transform .15s ease, box-shadow .15s ease;'
            . '}'
            . '#inv-calendar-months .carousel-control-prev:hover .carousel-control-prev-icon,'
            . '#inv-calendar-months .carousel-control-next:hover .carousel-control-next-icon {'
            . 'transform: scale(1.1);'
            . '}'
            . '#inv-calendar-months .carousel-control-prev:focus-visible .carousel-control-prev-icon,'
            . '#inv-calendar-months .carousel-control-next:focus-visible .carousel-control-next-icon {'
            . 'outline: 2px solid var(--calendar-accent); outline-offset: 2px;'
            . '}'
            . '#inv-calendar-months .carousel-indicators {'
            . 'position: static; margin: 0 0 .75rem; align-items: center;'
            . '}'
            . '#inv-calendar-months .carousel-indicators [data-bs-target] {'
            . 'background-color: var(--calendar-accent); opacity: .4;'
            . 'width: 16px; height: 16px; border-radius: 50%; margin: 0 5px;'
            . 'transition: transform .15s ease, opacity .15s ease;'
            . '}'
            . '#inv-calendar-months .carousel-indicators [data-bs-target]:hover {'
            . 'opacity: .7; transform: scale(1.15);'
            . '}'
            . '#inv-calendar-months .carousel-indicators .active {'
            . 'opacity: 1; transform: scale(1.2);'
            . '}'
            . '#inv-calendar-months .carousel-indicators .calendar-current-month-indicator {'
            . 'opacity: 1; box-shadow: 0 0 0 2px var(--bs-warning);'
            . '}'
            . '#inv-calendar-months .calendar-run-badge { color: #fff !important; }'
        );
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
