<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Button;
use Yiisoft\Bootstrap5\ButtonGroup;
use Yiisoft\Bootstrap5\ButtonVariant;
use Yiisoft\Bootstrap5\Carousel;
use Yiisoft\Bootstrap5\CarouselItem;
use Yiisoft\Html\Html as H;

/**
 * inv/guest/calendar -- the same swipeable month Carousel as inv/calendar
 * (Trait\Calendar), scoped to this signed-in guest's own worker-/
 * client-visible invoices (Trait\GuestCalendar). Two differences from the
 * staff version: badges link to inv/guest (filterDateCreatedExact only --
 * guest has no filterCategorySecondaryRun concept), and there's no month-
 * level $monthStart passed in (never was rendered here -- see the staff
 * view's own comment on why its static title was dropped).
 *
 * Deliberately reuses the *same* carousel element id (#inv-calendar-months)
 * as the staff view -- src/typescript/calendar.ts's initCalendar() then
 * beautifies this page for free (chevron icons, keyboard paging, badge
 * magnifier, indicator tooltips/current-month ring, Mobile Preview) with
 * no changes to that file; the guest layout already registers the same
 * asset bundle (InvCdn/InvNm carrying invoice-typescript-iife.js).
 *
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $alert
 * @var string $bootstrap5CalendarAccentColor Settings → Bootstrap5 →
 *      "Calendar Accent Color" (App\ViewInjection\LayoutViewInjection);
 *      one of Bootstrap's variant names, e.g. 'primary'/'success'.
 * @var DateTimeImmutable $prevMonth
 * @var DateTimeImmutable $nextMonth
 * @var DateTimeImmutable $today
 * @psalm-var list<array{monthStart: DateTimeImmutable, month: int, weeks: list<list<DateTimeImmutable>>, active: bool}> $months
 * @psalm-var array<string, array<int, int>> $days
 * @psalm-var array<array-key, string> $categoryNames
 */

// See resources/views/invoice/inv/calendar.php's own comments for the full
// history behind every rule here (mobile grid split, control click-zone/
// z-index fixes, indicator repositioning, badge contrast) -- kept
// byte-identical since this page shares the same #inv-calendar-months
// structure and every one of those live-feedback bugs applies equally here.
echo H::style(
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

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$carouselItems = [];
foreach ($months as $monthData) {
    ob_start();

    echo H::tag(
        'h5',
        $monthData['monthStart']->format('F Y'),
        [
            'class' => 'text-center mb-2',
            'data-current-month' => $monthData['monthStart']->format('Y-m') === $today->format('Y-m')
                ? '1' : '0',
        ]
    );

    echo H::openTag('div', ['class' => 'row row-cols-1 g-2 calendar-week-grid px-4']);
    foreach ($monthData['weeks'] as $week) {
        foreach ($week as $day) {
            $dayKey = $day->format('Y-m-d');
            $inMonth = ((int) $day->format('n')) === $monthData['month'];
            $isToday = $dayKey === $today->format('Y-m-d');
            $runs = $days[$dayKey] ?? [];

            $cellClass = 'card h-100 ' .
                ($inMonth ? 'bg-light' : 'bg-body-tertiary text-body-secondary');
            if ($isToday) {
                $cellClass .= ' border-' . $bootstrap5CalendarAccentColor . ' border-2';
            }

            echo H::openTag('div', ['class' => 'col']);
                echo H::openTag('div', ['class' => trim($cellClass)]);
                    echo H::openTag('div', ['class' => 'card-body p-2']);
                        echo H::tag(
                            'div',
                            $day->format('D'),
                            [
                                'class' => 'small text-uppercase text-muted',
                                'style' => 'letter-spacing: .05em;',
                            ]
                        );
                        echo H::tag(
                            'div',
                            $day->format('j'),
                            ['class' => 'fw-semibold mb-1']
                        );
                        if ($inMonth && $runs !== []) {
                            echo H::openTag('div', ['class' =>
                                'd-flex flex-wrap gap-1']);
                            foreach ($runs as $categorySecondaryId => $count) {
                                $runName = $categoryNames[$categorySecondaryId]
                                    ?? (string) $categorySecondaryId;
                                // inv/guest, not inv/index, and no
                                // filterCategorySecondaryRun -- a guest has
                                // no EDIT_INV permission and no run-filter
                                // concept at all; the exact date alone is
                                // enough since filterGuestDateCreatedExact()
                                // is already scoped to this guest's own
                                // worker-/client-visible invoices.
                                echo H::a(
                                    $runName . ' (' . $count . ')',
                                    $urlGenerator->generate('inv/guest', [], [
                                        'filterDateCreatedExact' => $dayKey,
                                    ]),
                                    [
                                        'class' => 'badge calendar-run-badge text-bg-'
                                            . $bootstrap5CalendarAccentColor . ' text-decoration-none',
                                        'data-bs-toggle' => 'tooltip',
                                        'title' => $runName . ' — ' . $dayKey,
                                    ]
                                );
                            }
                            echo H::closeTag('div');
                        }
                    echo H::closeTag('div');
                echo H::closeTag('div');
            echo H::closeTag('div');
        }
    }
    echo H::closeTag('div');

    $monthHtml = ob_get_clean();
    $carouselItems[] = CarouselItem::to(
        $monthHtml !== false ? $monthHtml : '',
        active: $monthData['active'],
    );
}

echo Carousel::widget()
    ->id('inv-calendar-months')
    ->addAttributes([
        'data-accent' => $bootstrap5CalendarAccentColor,
        'style' => '--calendar-accent: var(--bs-' . $bootstrap5CalendarAccentColor . ');',
    ])
    ->showIndicators(true)
    ->controlPreviousLabel($translator->translate('prev'))
    ->controlNextLabel($translator->translate('next'))
    ->items(...$carouselItems)
    ->render();

echo H::openTag('div', ['class' => 'd-flex justify-content-center mt-3']);
    echo ButtonGroup::widget()
        ->ariaLabel($translator->translate('calendar'))
        ->buttons(
            Button::link(
                '« ' . $translator->translate('prev'),
                $urlGenerator->generate('inv/guest/calendar', [
                    'year' => $prevMonth->format('Y'),
                    'month' => $prevMonth->format('n'),
                ]),
            )->variant(ButtonVariant::OUTLINE_SECONDARY),
            Button::link(
                $translator->translate('today'),
                $urlGenerator->generate('inv/guest/calendar'),
            )->variant(ButtonVariant::OUTLINE_PRIMARY),
            Button::link(
                $translator->translate('next') . ' »',
                $urlGenerator->generate('inv/guest/calendar', [
                    'year' => $nextMonth->format('Y'),
                    'month' => $nextMonth->format('n'),
                ]),
            )->variant(ButtonVariant::OUTLINE_SECONDARY),
        )
        ->render();
echo H::closeTag('div');
