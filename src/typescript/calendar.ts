import type { OriginalStyles } from './types.js';
import { MobilePreviewToggle } from './mobile-preview-toggle.js';

/**
 * Beautifies inv/calendar's month Carousel (resources/views/invoice/inv/
 * calendar.php) — no-ops on every other page. Several things here that
 * plain PHP/CSS genuinely can't do:
 *
 *  1. Yiisoft\Bootstrap5\Carousel::renderControlPrevious()/
 *     renderControlNext() are private and always render Bootstrap's stock
 *     empty `<span class="carousel-control-prev-icon">` (a CSS
 *     background-image mask) — there's no widget API to put a different
 *     icon in there. The calendar.php view's own <style> block already
 *     recolors that span into a solid --calendar-accent circle; this file
 *     fills it with a real `bi bi-chevron-left/-right` glyph, the same
 *     icon font used everywhere else in this app (`new I()->class('bi
 *     bi-...')`).
 *  2. Left/Right arrow-key paging while the carousel has focus, via
 *     Bootstrap's own Carousel JS API.
 *  3. A hover magnifier on each day-card's run badge/hyperlink, the same
 *     idea as AmountMagnifier (list-utils.ts) already applies to
 *     inv/index's amount badges — not reused directly since that class
 *     only targets badges whose text looks like a formatted number
 *     (isAmount()); the calendar's badges are "name (count)" run labels.
 *  4. A tooltip naming each month on the carousel's indicator dots, and a
 *     highlight ring on whichever one is the real-world current month
 *     (not to be confused with Bootstrap's own .active, which is
 *     whichever slide is currently being viewed). CarouselItem::
 *     attributes() exists on the PHP side but Carousel's own renderItem()
 *     never actually applies it to the rendered .carousel-item div
 *     (confirmed by reading the widget source) — so both the month label
 *     and the current-month flag are read here from each slide's own
 *     <h5> heading instead, which calendar.php already renders as the
 *     first thing inside every slide.
 *  5. The same "📱 Mobile Preview" popup-window toggle inv/index has
 *     (MobilePreviewToggle, extracted from inv-index.ts into its own
 *     module so this page can reuse it without initInvIndex()'s
 *     grid-specific setup, which doesn't apply here) — important on this
 *     page in particular since it was explicitly built mobile-first.
 */

const CAROUSEL_ID = 'inv-calendar-months';

export function initCalendar(): void {
    const carousel = document.getElementById(CAROUSEL_ID);
    if (!carousel) return;

    replaceControlIcons(carousel);
    initKeyboardPaging(carousel);
    initBadgeMagnifier(carousel);
    initIndicatorTooltips(carousel);
    markCurrentMonthIndicator(carousel);
    // Guarded, unlike inv-index.ts's own unconditional `new
    // MobilePreviewToggle()` — that file's setup() only ever runs once
    // (behind its own DOMContentLoaded/readyState gate), whereas
    // initCalendar() has no such gate of its own and is safe to call
    // more than once (matches every other init* function here). Not
    // stored (CodeRabbit, PR #1248: unused variable) -- watchPopup()'s
    // own setInterval closure over `this` is what keeps the instance
    // alive, not an external reference to it.
    if (document.querySelector('.mp-btn') === null) {
        new MobilePreviewToggle(); // NOSONAR typescript:S1848 — constructor binds DOM event listeners; instantiation is the side effect
    }
}

function replaceControlIcons(carousel: HTMLElement): void {
    const prevIcon = carousel.querySelector('.carousel-control-prev-icon');
    const nextIcon = carousel.querySelector('.carousel-control-next-icon');

    if (prevIcon?.querySelector('i.bi') === null) {
        prevIcon.innerHTML = '';
        prevIcon.appendChild(chevron('bi-chevron-left'));
    }
    if (nextIcon?.querySelector('i.bi') === null) {
        nextIcon.innerHTML = '';
        nextIcon.appendChild(chevron('bi-chevron-right'));
    }
}

function chevron(iconClass: string): HTMLElement {
    const icon = document.createElement('i');
    icon.className = `bi ${iconClass}`;
    icon.setAttribute('aria-hidden', 'true');
    return icon;
}

function initKeyboardPaging(carousel: HTMLElement): void {
    carousel.addEventListener('keydown', (event: KeyboardEvent) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;

        const bs = globalThis.bootstrap;
        if (!bs?.Carousel) return;

        const instance = bs.Carousel.getOrCreateInstance(carousel);
        event.preventDefault();
        if (event.key === 'ArrowLeft') {
            instance.prev();
        } else {
            instance.next();
        }
    });
}

const BADGE_MAGNIFICATION = 1.35;
const BADGE_SCALE = 1.15;

/**
 * Hover-magnifies each run badge — click still navigates normally (unlike
 * AmountMagnifier's click-to-toggle, which fits its non-link amount
 * badges but would fight real navigation here), so only mouseenter/
 * mouseleave are wired up.
 */
function initBadgeMagnifier(carousel: HTMLElement): void {
    carousel.querySelectorAll<HTMLElement>('.calendar-run-badge').forEach(badge => {
        if (badge.dataset['magnifierInitialized']) return;
        badge.dataset['magnifierInitialized'] = 'true';

        const cs = globalThis.getComputedStyle(badge);
        const orig: OriginalStyles = {
            fontSize: cs.fontSize,
            fontWeight: cs.fontWeight,
            backgroundColor: cs.backgroundColor,
            border: cs.border,
            borderRadius: cs.borderRadius,
            padding: cs.padding,
            zIndex: cs.zIndex,
            position: cs.position,
            transform: cs.transform,
            boxShadow: cs.boxShadow,
        };

        badge.style.transition = 'transform .15s ease-in-out, box-shadow .15s ease-in-out,'
            + ' font-size .15s ease-in-out';

        let magnified = false;
        const magnify = (): void => {
            const newSize = Number.parseFloat(orig.fontSize) * BADGE_MAGNIFICATION;
            badge.style.fontSize = `${newSize}px`;
            badge.style.transform = `scale(${BADGE_SCALE})`;
            badge.style.boxShadow = '0 4px 10px rgba(0, 0, 0, .3)';
            badge.style.position = 'relative';
            badge.style.zIndex = '1000';
        };
        const restore = (): void => {
            const style = badge.style as unknown as Record<string, string>;
            (Object.keys(orig) as Array<keyof OriginalStyles>).forEach(key => {
                style[key] = orig[key];
            });
        };

        badge.addEventListener('mouseenter', () => {
            if (!magnified) { magnified = true; magnify(); }
        });
        badge.addEventListener('mouseleave', () => {
            if (magnified) { magnified = false; restore(); }
        });
        badge.addEventListener('focus', () => {
            if (!magnified) { magnified = true; magnify(); }
        });
        badge.addEventListener('blur', () => {
            if (magnified) { magnified = false; restore(); }
        });
    });
}

function initIndicatorTooltips(carousel: HTMLElement): void {
    const bs = globalThis.bootstrap;
    if (!bs?.Tooltip) return;

    const monthLabels = Array.from(carousel.querySelectorAll<HTMLElement>('.carousel-item h5'))
        .map(heading => heading.textContent?.trim() ?? '');
    const indicators = carousel.querySelectorAll<HTMLElement>('.carousel-indicators [data-bs-slide-to]');

    indicators.forEach((indicator, index) => {
        const label = monthLabels[index];
        if (!label || indicator.dataset['tooltipInitialized']) return;
        indicator.dataset['tooltipInitialized'] = 'true';
        indicator.setAttribute('title', label);
        indicator.dataset['bsToggle'] = 'tooltip';
        // Bottom, not Bootstrap's default top: the dots sit at the very
        // top of the page content (right below the flash-alert area), so
        // a top-placed tooltip has nothing but page chrome to open into
        // and would clip/overlap it instead.
        indicator.dataset['bsPlacement'] = 'bottom';
        try {
            bs.Tooltip.getOrCreateInstance(indicator);
        } catch (error) {
            console.warn('Calendar indicator tooltip init failed:', error);
        }
    });
}

const CURRENT_MONTH_INDICATOR_CLASS = 'calendar-current-month-indicator';

/**
 * Rings whichever indicator dot corresponds to the real-world current
 * month, so it stays identifiable even after swiping away to a different
 * slide (Bootstrap's own .active only marks whichever slide is currently
 * *viewed*, which need not be this one).
 */
function markCurrentMonthIndicator(carousel: HTMLElement): void {
    const headings = Array.from(carousel.querySelectorAll<HTMLElement>('.carousel-item h5'));
    const currentIndex = headings.findIndex(heading => heading.dataset['currentMonth'] === '1');
    if (currentIndex === -1) return;

    const indicators = carousel.querySelectorAll<HTMLElement>('.carousel-indicators [data-bs-slide-to]');
    indicators[currentIndex]?.classList.add(CURRENT_MONTH_INDICATOR_CLASS);
}
