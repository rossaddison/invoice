/**
 * Hover magnifier lens for the product detail page
 * (resources/views/shop/catalog/view.php) — moving the pointer over the
 * product photo shows a square lens that follows it, and a separate
 * pane beside the photo renders a magnified crop of whatever the lens
 * is over. Pointer/mouse only (see the `matchMedia` guard below): a
 * hover-driven lens has no equivalent on touch, so touch visitors just
 * keep the plain photo they always had — nothing to progressively
 * enhance there.
 *
 * The pane is built from the same <img> element as a CSS background,
 * scaled and positioned from the *natural* image dimensions rather
 * than the rendered `object-fit: cover` box — see
 * ProductZoomLens.onMove()'s own comment for why that distinction
 * matters.
 */

import { clamp } from './dom-utils.js';

class ProductZoomLens {
    private readonly lensSize = 140;
    private readonly magnification = 2.5;

    constructor(
        private readonly wrapper: HTMLElement,
        private readonly img: HTMLImageElement,
        private readonly lens: HTMLElement,
        private readonly pane: HTMLElement,
    ) {
        this.pane.style.backgroundImage = `url("${img.src}")`;

        wrapper.addEventListener('pointerenter', (event) => this.onEnter(event));
        wrapper.addEventListener('pointermove', (event) => this.onMove(event));
        wrapper.addEventListener('pointerleave', () => this.hide());
    }

    private onEnter(event: PointerEvent): void {
        this.lens.style.display = 'block';
        this.pane.style.display = 'block';
        this.onMove(event);
    }

    private hide(): void {
        this.lens.style.display = 'none';
        this.pane.style.display = 'none';
    }

    private onMove(event: PointerEvent): void {
        const rect = this.wrapper.getBoundingClientRect();
        const x = clamp(event.clientX - rect.left, 0, rect.width);
        const y = clamp(event.clientY - rect.top, 0, rect.height);

        const half = this.lensSize / 2;
        this.lens.style.left = `${clamp(x - half, 0, rect.width - this.lensSize)}px`;
        this.lens.style.top = `${clamp(y - half, 0, rect.height - this.lensSize)}px`;

        const naturalWidth = this.img.naturalWidth || rect.width;
        const naturalHeight = this.img.naturalHeight || rect.height;

        // `object-fit: cover` scales the natural image up until it
        // covers the rendered box, then crops the overflow evenly on
        // each side (the default centered object-position) — reproduce
        // that same scale/crop math here so the lens tracks the part of
        // the *photo* under the pointer, not the part of the untrimmed
        // natural image.
        const coverScale = Math.max(rect.width / naturalWidth, rect.height / naturalHeight);
        const visibleWidth = rect.width / coverScale;
        const visibleHeight = rect.height / coverScale;
        const naturalX = (naturalWidth - visibleWidth) / 2 + (x / rect.width) * visibleWidth;
        const naturalY = (naturalHeight - visibleHeight) / 2 + (y / rect.height) * visibleHeight;

        const backgroundScale = coverScale * this.magnification;
        this.pane.style.backgroundSize = `${naturalWidth * backgroundScale}px ${naturalHeight * backgroundScale}px`;

        const paneRect = this.pane.getBoundingClientRect();
        this.pane.style.backgroundPosition =
            `${paneRect.width / 2 - naturalX * backgroundScale}px ` +
            `${paneRect.height / 2 - naturalY * backgroundScale}px`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Coarse/no-hover devices (touch) get no equivalent interaction —
    // don't wire up pointer tracking that'll never fire the way it's
    // meant to there.
    if (!globalThis.matchMedia?.('(hover: hover) and (pointer: fine)').matches) {
        return;
    }

    const wrapper = document.querySelector<HTMLElement>('.js-zoom-wrapper');
    const img = document.querySelector<HTMLImageElement>('.js-zoom-image');
    const lens = document.querySelector<HTMLElement>('.js-zoom-lens');
    const pane = document.querySelector<HTMLElement>('.js-zoom-pane');
    if (!wrapper || !img || !lens || !pane) {
        return;
    }

    new ProductZoomLens(wrapper, img, lens, pane);
});
