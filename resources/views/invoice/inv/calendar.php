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
 * @var string $calendarStyle Rendered <style> markup, shared with
 *      inv/guest/calendar -- see Trait\Calendar::calendarStyleCss().
 * @var DateTimeImmutable $prevMonth
 * @var DateTimeImmutable $nextMonth
 * @var DateTimeImmutable $today
 * @psalm-var list<array{monthStart: DateTimeImmutable, month: int, weeks: list<list<DateTimeImmutable>>, active: bool}> $months
 * @psalm-var array<string, array<int, int>> $days
 * @psalm-var array<array-key, string> $categoryNames
 */

// Shared with inv/guest/calendar's own guest_calendar.php -- see
// Trait\Calendar::calendarStyleCss()'s own docblock for the full
// live-feedback history behind this CSS (SonarCloud
// new_duplicated_lines_density, PR #1252: the two views had this whole
// block copy-pasted byte-for-byte).
echo $calendarStyle;

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
