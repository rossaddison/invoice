<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\User\User;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\Inv\InvGuestAccess;
use App\Invoice\Inv\InvGuestDeps;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * inv/guest/calendar: the same month-Carousel calendar as inv/calendar
 * (Trait\Calendar), scoped to whichever kind of signed-in guest
 * Trait\Guest::resolveGuestAccess() resolves -- a HomeCare field worker
 * (their own worker_id-allocated invoices, possibly across several
 * clients/streets a day) or an ordinary client observer (their own
 * assigned Client(s) only). Reuses Trait\Calendar's month/week-grid math
 * (calendarResolveTargetMonth()/calendarWindowMonths()/calendarBuildWeeks()/
 * calendarBucketInvoices()) verbatim -- it's pure date arithmetic with no
 * query of its own -- and only swaps in a worker-/client-scoped source
 * query (InvGuestTrait::repoWorkerDateRangeQuery()/
 * repoGuestClientsDateRangeQuery()) in place of the staff calendar's
 * unscoped InvRepository::repoDateRangeQuery().
 *
 * Its day-block badges link to inv/guest (filterDateCreatedExact), not
 * inv/index -- a guest has no EDIT_INV permission and no
 * filterCategorySecondaryRun concept at all.
 */
trait GuestCalendar
{
    public function guestCalendar(
        InvGuestDeps $d,
        CSR $csR,
        #[RouteArgument('year')] string $year = '',
        #[RouteArgument('month')] string $month = '',
    ): Response {
        $user = $this->userService->getUser();
        $user_id = ($user instanceof User) ? $user->reqId() : 0;
        if ($user_id <= 0) {
            return $this->webService->getNotFoundResponse();
        }
        $access = $this->resolveGuestAccess($d, $user_id);
        if (null === $access) {
            // Same redirect as guest() itself -- resolveGuestAccess()
            // already flashed why (no UserInv row, inactive, or no Client
            // assigned), see that method's own docblock.
            return $this->webService->getRedirectResponse('shop/catalog/index');
        }

        $target = $this->calendarResolveTargetMonth($year, $month);
        $windowMonths = $this->calendarWindowMonths($target);
        $windowStart = $this->calendarModify($target, '-' . self::MONTHS_BEFORE . ' months');
        $windowEnd = $this->calendarModify($target, '+' . (self::MONTHS_AFTER + 1) . ' months');
        $days = $this->calendarBucketInvoices(
            $this->guestCalendarInvoices($d, $access, $windowStart, $windowEnd)
        );
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

        return $this->webViewRenderer->render('guest_calendar', [
            'alert'          => $this->alert(),
            'months'         => $months,
            'days'           => $days,
            'categoryNames'  => $categoryNames,
            'prevMonth'      => $this->calendarModify($target, '-1 month'),
            'nextMonth'      => $this->calendarModify($target, '+1 month'),
            'today'          => new \DateTimeImmutable('today'),
            // Content views don't inherit LayoutViewInjection -- see
            // Trait\Calendar::calendar()'s own comment (PR #1248).
            'bootstrap5CalendarAccentColor' =>
                $this->sR->getSetting('bootstrap5_calendar_accent_color') ?: 'primary',
        ]);
    }

    private function guestCalendarInvoices(
        InvGuestDeps $d,
        InvGuestAccess $access,
        \DateTimeImmutable $from,
        \DateTimeImmutable $toExclusive,
    ): iterable {
        return null !== $access->worker
            ? $d->iR->repoWorkerDateRangeQuery($from, $toExclusive, $access->worker->reqId())
            : $d->iR->repoGuestClientsDateRangeQuery($from, $toExclusive, $access->clients);
    }
}
