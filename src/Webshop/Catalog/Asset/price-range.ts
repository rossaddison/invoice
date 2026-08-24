/**
 * Progressive enhancement for the catalog price filter
 * (resources/views/shop/catalog/index.php) — a dual-handle range slider
 * layered over the same min_price/max_price <input type="number">
 * fields the filter form already submits (those stay the actual
 * submitted values; this only ever writes into them).
 *
 * Two overlapping native <input type="range"> elements do the dragging
 * — free keyboard support, touch support, no hand-rolled pointer-event
 * hit-testing — with a highlighted "fill" div drawn between them from
 * their current values. #price-filter stays `d-none` server-side and is
 * only revealed once this initializes, so a no-JS visit (or a catalog
 * with too narrow a price range to bother, see the DOMContentLoaded
 * guard below) just sees the plain number inputs it always had.
 *
 * A magnifier bubble (large, bold price text) floats above whichever
 * thumb is being dragged — the small min/max labels below the track
 * are easy to misjudge mid-drag, so this echoes the exact value right
 * next to the pointer while it moves, then disappears on release.
 */

import { clamp } from './dom-utils.js';

class PriceRangeSlider {
    private readonly minRange: HTMLInputElement;
    private readonly maxRange: HTMLInputElement;
    private readonly rangeFill: HTMLElement;
    private readonly minLabel: HTMLElement;
    private readonly maxLabel: HTMLElement;
    private readonly bubble: HTMLElement;
    private readonly boundsMin: number;
    private readonly boundsMax: number;

    constructor(
        container: HTMLElement,
        private readonly minNumber: HTMLInputElement,
        private readonly maxNumber: HTMLInputElement,
        boundsMin: number,
        boundsMax: number,
    ) {
        this.boundsMin = boundsMin;
        this.boundsMax = boundsMax;

        this.minRange = container.querySelector<HTMLInputElement>('.js-price-slider-min')!;
        this.maxRange = container.querySelector<HTMLInputElement>('.js-price-slider-max')!;
        this.rangeFill = container.querySelector<HTMLElement>('.js-price-slider-range')!;
        this.minLabel = container.querySelector<HTMLElement>('.js-price-slider-min-label')!;
        this.maxLabel = container.querySelector<HTMLElement>('.js-price-slider-max-label')!;
        this.bubble = container.querySelector<HTMLElement>('.js-price-slider-bubble')!;

        for (const range of [this.minRange, this.maxRange]) {
            range.min = String(boundsMin);
            range.max = String(boundsMax);
            range.step = '0.01';
        }

        this.minRange.value = String(this.parse(minNumber.value, boundsMin));
        this.maxRange.value = String(this.parse(maxNumber.value, boundsMax));

        this.minRange.addEventListener('input', () => this.onRangeInput('min'));
        this.maxRange.addEventListener('input', () => this.onRangeInput('max'));
        // Bumps the dragged thumb above the other one so, once both
        // handles land on the same value, the next drag still grabs the
        // one the pointer is actually on rather than whichever <input>
        // happens to be later in the DOM.
        this.minRange.addEventListener('pointerdown', () => this.bringToFront(this.minRange, this.maxRange));
        this.maxRange.addEventListener('pointerdown', () => this.bringToFront(this.maxRange, this.minRange));
        // 'change' fires once the drag/keypress commits — that's the
        // signal to hide the bubble again ('input' alone never tells us
        // the drag ended).
        this.minRange.addEventListener('change', () => this.hideBubble());
        this.maxRange.addEventListener('change', () => this.hideBubble());
        minNumber.addEventListener('input', () => this.onNumberInput('min'));
        maxNumber.addEventListener('input', () => this.onNumberInput('max'));

        this.render();
    }

    private bringToFront(active: HTMLInputElement, other: HTMLInputElement): void {
        active.style.zIndex = '3';
        other.style.zIndex = '2';
    }

    private parse(value: string, fallback: number): number {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? clamp(parsed, this.boundsMin, this.boundsMax) : fallback;
    }

    private onRangeInput(dragged: 'min' | 'max'): void {
        let min = Number.parseFloat(this.minRange.value);
        let max = Number.parseFloat(this.maxRange.value);

        // Neither handle can be dragged past the other — the native
        // <input>s don't know about each other's value on their own.
        if (dragged === 'min' && min > max) {
            min = max;
            this.minRange.value = String(min);
        } else if (dragged === 'max' && max < min) {
            max = min;
            this.maxRange.value = String(max);
        }

        this.minNumber.value = min.toFixed(2);
        this.maxNumber.value = max.toFixed(2);
        this.showBubble(dragged === 'min' ? min : max);
        this.render();
    }

    private showBubble(value: number): void {
        const span = this.boundsMax - this.boundsMin || 1;
        this.bubble.style.left = `${((value - this.boundsMin) / span) * 100}%`;
        this.bubble.textContent = value.toFixed(2);
        this.bubble.style.display = 'block';
    }

    private hideBubble(): void {
        this.bubble.style.display = 'none';
    }

    private onNumberInput(typed: 'min' | 'max'): void {
        if (typed === 'min') {
            const value = this.parse(this.minNumber.value, this.boundsMin);
            this.minRange.value = String(Math.min(value, Number.parseFloat(this.maxRange.value)));
        } else {
            const value = this.parse(this.maxNumber.value, this.boundsMax);
            this.maxRange.value = String(Math.max(value, Number.parseFloat(this.minRange.value)));
        }
        this.render();
    }

    private render(): void {
        const min = Number.parseFloat(this.minRange.value);
        const max = Number.parseFloat(this.maxRange.value);
        const span = this.boundsMax - this.boundsMin || 1;

        this.rangeFill.style.left = `${((min - this.boundsMin) / span) * 100}%`;
        this.rangeFill.style.right = `${100 - ((max - this.boundsMin) / span) * 100}%`;
        this.minLabel.textContent = min.toFixed(2);
        this.maxLabel.textContent = max.toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('price-filter');
    const minNumber = document.querySelector<HTMLInputElement>('.js-price-min-input');
    const maxNumber = document.querySelector<HTMLInputElement>('.js-price-max-input');
    if (!container || !minNumber || !maxNumber) {
        return;
    }

    const boundsMin = Number.parseFloat(container.dataset.catalogMin ?? '');
    const boundsMax = Number.parseFloat(container.dataset.catalogMax ?? '');
    // A degenerate/absent range (every product the same price, or no
    // products at all) isn't worth a slider over — the plain number
    // inputs stay as they are.
    if (!Number.isFinite(boundsMin) || !Number.isFinite(boundsMax) || boundsMin >= boundsMax) {
        return;
    }

    new PriceRangeSlider(container, minNumber, maxNumber, boundsMin, boundsMax);
    container.classList.remove('d-none');
});
