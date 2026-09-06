<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Button;
use Yiisoft\Bootstrap5\ButtonGroup;
use Yiisoft\Bootstrap5\ButtonVariant;
use Yiisoft\Bootstrap5\Carousel;
use Yiisoft\Bootstrap5\CarouselItem;
use Yiisoft\Html\Html as H;

/**
 * inv/calendar -- a swipeable Carousel, one slide per MONTH (a bounded
 * window of them, see Trait\Calendar::MONTHS_BEFORE/MONTHS_AFTER), each
 * slide holding that whole month's day-grid. Each badge inside a day-card
 * is a "run" (category_secondary + that exact date) linking straight into
 * inv/index, pre-filtered via filterCategorySecondaryRun +
 * filterDateCreatedExact.
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

// Bootstrap5's row-cols-* utility only ships up to row-cols-md-6 (its
// $row-cols SCSS variable defaults to 6 -- confirmed against the vendored
// bootstrap.min.css, which has no row-cols-md-7 class at all), so a plain
// 7-day week can't be built from that utility alone. row-cols-1 below still
// covers the mobile-first stacked list this feature was specifically asked
// for; this scoped block only adds the even 7-up split from md up, the
// same way guest.php's own Html::style() block supplements Bootstrap for
// something its utilities don't cover.
// The carousel's own prev/next controls default to a 15%-wide clickable
// zone at each edge (Bootstrap's .carousel-control-prev/-next, hardcoded
// in bootstrap.css -- no CSS variable to override it), which sits almost
// exactly on top of the first/last day column of a 7-up grid and would
// intercept clicks meant for that day's own run badges. Narrowed here to
// 6%, with matching padding on the week row so no day-card sits under it.
//
// The controls' visual restyle (live feedback: the default flat chevron
// read as "black on blue" -- carousel-dark's dark icon plus the browser's
// blue focus ring, and generally dull) replaces Bootstrap's mask-icon
// entirely with a solid --calendar-accent circle; calendar.ts (initCalendar)
// fills it with a real bi-chevron-left/-right glyph, since the Carousel
// widget's control-icon markup isn't something its PHP API can override.
// Indicators are restyled the same way -- without carousel-dark, their
// default white dots would be invisible on this page's white background.
echo H::style(
    '@media (min-width: 768px) {'
    . '.calendar-week-grid > .col { flex: 0 0 calc(100% / 7); max-width: calc(100% / 7); }'
    . '}'
    . '#inv-calendar-months .carousel-control-prev,'
    . '#inv-calendar-months .carousel-control-next { width: 6%; }'
    // Live feedback: badges in the leftmost (Monday) column were
    // rendering fine but not actually clickable. Root cause: the
    // control's 6%-wide zone above (still wider than the px-4 padding
    // inset on the row, at most viewport widths) is position:absolute
    // with Bootstrap's own z-index:1, so it painted *above* the day-cards
    // -- which had no z-index of their own outside of the magnifier's
    // hover-only one -- and silently swallowed clicks meant for their
    // badges. Padding narrows the overlap but is a fixed px value against
    // a percentage-of-container-width zone, so it can't reliably clear it
    // at every viewport size; z-index is the actual fix; it wins
    // regardless of how much (if any) the two visually overlap.
    . '.calendar-week-grid .card { position: relative; z-index: 2; }'
    . '#inv-calendar-months .carousel-control-prev-icon,'
    . '#inv-calendar-months .carousel-control-next-icon {'
    . 'background-image: none; background-color: var(--calendar-accent);'
    . 'width: 2.5rem; height: 2.5rem; border-radius: 50%;'
    . 'display: flex; align-items: center; justify-content: center;'
    . 'color: #fff; font-size: 1.25rem;'
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
    // Live feedback: the indicators' Bootstrap default (position:absolute;
    // bottom:0) pinned them to the bottom of the *carousel container*,
    // which (with no fixed height) sized itself to the active slide's
    // full multi-week grid -- so instead of sitting under everything,
    // they visually overlapped the first day-row wherever that bottom
    // edge happened to land. Bootstrap's own renderItems() already
    // outputs the indicators <div> before .carousel-inner in the DOM
    // (confirmed in the widget source), so switching them back to normal
    // static flow puts them at the actual top of the carousel, above the
    // grid, with no extra markup/JS needed.
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
    // The real-world "this month" dot (see the h5 data-current-month
    // marker/calendar.ts above) -- a ring, not a different fill color, so
    // it layers cleanly with .active's own opacity/scale rather than
    // fighting over what the dot's base color means. Uses --bs-warning
    // rather than --calendar-accent so it stays visually distinct even
    // when this dot is also the active one (ring color != fill color).
    . '#inv-calendar-months .carousel-indicators .calendar-current-month-indicator {'
    . 'opacity: 1; box-shadow: 0 0 0 2px var(--bs-warning);'
    . '}'
    // Badges still use Bootstrap's text-bg-{accent} utility for its
    // background-color, but override its color: this app's own compiled
    // style.css computes color:#000 (not Bootstrap's stock #fff) for
    // text-bg-primary/success/info/warning/danger/light -- confirmed
    // against the vendored, unmodified node_modules/bootstrap build, which
    // does use white. Black text on a solid accent pill was the live
    // "black on blue" complaint. Scoped to just this badge rather than
    // touching that app-wide SCSS/contrast setting. !important is required,
    // not just belt-and-braces: style.css's own text-bg-{accent} rule is
    // itself !important, and a non-!important override can never beat that
    // regardless of selector specificity or source order.
    . '#inv-calendar-months .calendar-run-badge { color: #fff !important; }'
);

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

// Live feedback: a static "$monthStart" H4 used to sit here, but it only
// ever reflected the originally-requested month and never updated while
// swiping the carousel -- once the indicator dots moved to the top (right
// above this spot), a stale title visibly clashed/overlapped with the
// dots' own hover tooltips and with whichever month the carousel was
// actually showing. Each slide already labels itself via its own <h5>
// (below), which does update correctly as you swipe, so the static
// duplicate was dropped rather than kept in sync.

// One CarouselItem per month in the preloaded window -- each slide holds
// that whole month's day-grid (row-cols-1 mobile / calendar-week-grid
// md+, wrapping every 7 cells into its own week line), built into a
// string via output buffering so it can be handed to CarouselItem::to()
// as raw content instead of echoed directly.
$carouselItems = [];
foreach ($months as $monthData) {
    ob_start();

    // data-current-month marks the real-world "this month" slide (not to
    // be confused with the .active slide, which is whichever one is
    // currently being viewed/swiped to) -- calendar.ts reads it to color
    // that one indicator dot differently. Same reason as the h5-reading
    // trick above: CarouselItem::attributes() never reaches the rendered
    // indicator/slide markup, so it has to be something inside content
    // this view already fully controls.
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

            // The app's own card component (see client/_form.php's
            // identical 'card border ... rounded-3' combo) -- not a bare
            // border+rounded utility combo, which this theme's compiled
            // CSS (public/assets/.../invoice/css/style.css) renders as a
            // pill/stadium shape instead of a rectangular box at this
            // cell's width:height ratio. bg-light (in-month) gives every
            // day box a slight grey tint instead of blending flat into
            // the page's white background; out-of-month days keep their
            // own, slightly different bg-body-tertiary muted look.
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
                                echo H::a(
                                    $runName . ' (' . $count . ')',
                                    $urlGenerator->generate('inv/index', [], [
                                        'filterCategorySecondaryRun' => (string) $categorySecondaryId,
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

// ->theme('dark') (data-bs-theme="dark") looked right from the Bootstrap
// docs' dark-variant example, but that attribute is Bootstrap's *global*
// color-mode switch -- it cascades dark-mode values for every Bootstrap
// CSS variable (--bs-card-bg, --bs-body-color, --bs-border-color, ...) to
// every descendant, not just the carousel controls. Confirmed live: it
// turned the day-cards themselves black. 'carousel-dark' (Bootstrap's
// other, narrower dark-variant class) was tried next, but the controls
// are now fully restyled above (solid --calendar-accent circle, not just
// a recolored mask-icon), so neither mechanism is needed any more --
// --calendar-accent (read by the CSS above) is set here instead, from the
// admin's Settings → Bootstrap5 → "Calendar Accent Color" choice.
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

// Month navigation (changes month/re-centers the preloaded window --
// distinct from the carousel's own prev/next, which page between the
// already-loaded months) moved below the grid, swapped with the
// indicator dots above: live feedback asked for dots-at-top/
// buttons-at-bottom instead of the original buttons-at-top/dots-
// overlapping-the-grid arrangement.
echo H::openTag('div', ['class' => 'd-flex justify-content-center mt-3']);
    echo ButtonGroup::widget()
        ->ariaLabel($translator->translate('calendar'))
        ->buttons(
            Button::link(
                '« ' . $translator->translate('prev'),
                $urlGenerator->generate('inv/calendar', [
                    'year' => $prevMonth->format('Y'),
                    'month' => $prevMonth->format('n'),
                ]),
            )->variant(ButtonVariant::OUTLINE_SECONDARY),
            Button::link(
                $translator->translate('today'),
                $urlGenerator->generate('inv/calendar'),
            )->variant(ButtonVariant::OUTLINE_PRIMARY),
            Button::link(
                $translator->translate('next') . ' »',
                $urlGenerator->generate('inv/calendar', [
                    'year' => $nextMonth->format('Y'),
                    'month' => $nextMonth->format('n'),
                ]),
            )->variant(ButtonVariant::OUTLINE_SECONDARY),
        )
        ->render();
echo H::closeTag('div');
