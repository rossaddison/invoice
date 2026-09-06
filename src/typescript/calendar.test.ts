import { afterEach, describe, expect, it, vi } from 'vitest';
import { initCalendar } from './calendar.js';

function carouselMarkup(): string {
    return `
        <div id="inv-calendar-months" class="carousel slide" data-accent="primary">
            <div class="carousel-indicators">
                <button data-bs-slide-to="0" class="active"></button>
                <button data-bs-slide-to="1"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <h5 data-current-month="0">September 2026</h5>
                    <a class="badge calendar-run-badge" href="/inv">Ross Addison Secondary (1)</a>
                </div>
                <div class="carousel-item">
                    <h5 data-current-month="1">October 2026</h5>
                </div>
            </div>
            <button class="carousel-control-prev" type="button">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>`;
}

describe('initCalendar', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        document.body.innerHTML = '';
        document.head.innerHTML = '';
    });

    it('does nothing when the calendar carousel is not on the page', () => {
        expect(() => initCalendar()).not.toThrow();
    });

    it('replaces the default control icons with bi-chevron glyphs', () => {
        document.body.innerHTML = carouselMarkup();

        initCalendar();

        const prevIcon = document.querySelector('.carousel-control-prev-icon i.bi');
        const nextIcon = document.querySelector('.carousel-control-next-icon i.bi');
        expect(prevIcon?.classList.contains('bi-chevron-left')).toBe(true);
        expect(nextIcon?.classList.contains('bi-chevron-right')).toBe(true);
    });

    it('does not double-inject an icon when called twice', () => {
        document.body.innerHTML = carouselMarkup();

        initCalendar();
        initCalendar();

        expect(document.querySelectorAll('.carousel-control-prev-icon i.bi')).toHaveLength(1);
    });

    it('calls Carousel.prev()/next() on ArrowLeft/ArrowRight while the carousel has focus', () => {
        document.body.innerHTML = carouselMarkup();
        const prev = vi.fn();
        const next = vi.fn();
        const getOrCreateInstance = vi.fn().mockReturnValue({ prev, next });
        vi.stubGlobal('bootstrap', { Carousel: { getOrCreateInstance } });

        initCalendar();
        const carousel = document.getElementById('inv-calendar-months') as HTMLElement;
        carousel.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));
        carousel.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));

        expect(prev).toHaveBeenCalledTimes(1);
        expect(next).toHaveBeenCalledTimes(1);
    });

    it('ignores keys other than ArrowLeft/ArrowRight', () => {
        document.body.innerHTML = carouselMarkup();
        const getOrCreateInstance = vi.fn();
        vi.stubGlobal('bootstrap', { Carousel: { getOrCreateInstance } });

        initCalendar();
        const carousel = document.getElementById('inv-calendar-months') as HTMLElement;
        carousel.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));

        expect(getOrCreateInstance).not.toHaveBeenCalled();
    });

    it('does not throw when bootstrap.Carousel is unavailable', () => {
        document.body.innerHTML = carouselMarkup();
        vi.stubGlobal('bootstrap', undefined);

        initCalendar();
        const carousel = document.getElementById('inv-calendar-months') as HTMLElement;
        expect(() =>
            carousel.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true })),
        ).not.toThrow();
    });

    it('magnifies a run badge on hover/focus and restores it on mouseleave/blur', () => {
        document.body.innerHTML = carouselMarkup();
        initCalendar();
        const badge = document.querySelector('.calendar-run-badge') as HTMLElement;
        const originalFontSize = globalThis.getComputedStyle(badge).fontSize;

        badge.dispatchEvent(new MouseEvent('mouseenter'));
        expect(badge.style.transform).toContain('scale(');
        expect(badge.style.fontSize).not.toBe(originalFontSize);

        badge.dispatchEvent(new MouseEvent('mouseleave'));
        expect(badge.style.fontSize).toBe(originalFontSize);
    });

    it('does not attach a second magnifier to the same badge on repeat init', () => {
        document.body.innerHTML = carouselMarkup();
        initCalendar();
        initCalendar();
        const badge = document.querySelector('.calendar-run-badge') as HTMLElement;

        badge.dispatchEvent(new MouseEvent('mouseenter'));
        const scaledOnce = badge.style.transform;
        badge.dispatchEvent(new MouseEvent('mouseenter'));

        expect(badge.style.transform).toBe(scaledOnce);
    });

    it('gives each indicator a tooltip named after its slide\'s month heading', () => {
        document.body.innerHTML = carouselMarkup();
        const getOrCreateInstance = vi.fn();
        vi.stubGlobal('bootstrap', { Tooltip: { getOrCreateInstance } });

        initCalendar();

        const indicators = document.querySelectorAll<HTMLElement>('.carousel-indicators [data-bs-slide-to]');
        expect(indicators[0]?.getAttribute('title')).toBe('September 2026');
        expect(indicators[1]?.getAttribute('title')).toBe('October 2026');
        expect(getOrCreateInstance).toHaveBeenCalledTimes(2);
        // Bottom, not Bootstrap's default top -- the dots sit at the very
        // top of the page content, so a top-placed tooltip would open
        // into page chrome instead of the grid below.
        expect(indicators[0]?.dataset['bsPlacement']).toBe('bottom');
    });

    it('does not throw when bootstrap.Tooltip is unavailable', () => {
        document.body.innerHTML = carouselMarkup();
        vi.stubGlobal('bootstrap', undefined);

        expect(() => initCalendar()).not.toThrow();
        expect(document.querySelector('[data-bs-slide-to="0"]')?.hasAttribute('title')).toBe(false);
    });

    it('adds the Mobile Preview toggle button, same as inv/index', () => {
        document.body.innerHTML = carouselMarkup();

        initCalendar();

        expect(document.querySelector('.mp-btn')).not.toBeNull();
        expect(document.querySelector('.mp-btn span')?.textContent).toBe('📱 Mobile Preview');
    });

    it('does not add a second Mobile Preview button on repeat init', () => {
        document.body.innerHTML = carouselMarkup();

        initCalendar();
        initCalendar();

        expect(document.querySelectorAll('.mp-btn')).toHaveLength(1);
    });

    it('rings the indicator for the real-world current month, not the active slide', () => {
        document.body.innerHTML = carouselMarkup();

        initCalendar();

        const indicators = document.querySelectorAll('.carousel-indicators [data-bs-slide-to]');
        // The fixture marks October (index 1) as data-current-month="1"
        // while September (index 0) is the .active slide -- confirms the
        // two are tracked independently.
        expect(indicators[0]?.classList.contains('calendar-current-month-indicator')).toBe(false);
        expect(indicators[1]?.classList.contains('calendar-current-month-indicator')).toBe(true);
    });

    it('does not throw when no slide is marked as the current month', () => {
        document.body.innerHTML = carouselMarkup().replace(/data-current-month="1"/, 'data-current-month="0"');

        expect(() => initCalendar()).not.toThrow();
        const indicators = document.querySelectorAll('.carousel-indicators [data-bs-slide-to]');
        expect(document.querySelectorAll('.calendar-current-month-indicator')).toHaveLength(0);
        expect(indicators).toHaveLength(2);
    });
});
