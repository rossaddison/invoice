import { afterEach, describe, expect, it, vi } from 'vitest';
import { initStickyNavbarOffset } from './sticky-navbar-offset.js';

function setOffsetHeight(el: HTMLElement, height: number): void {
    // jsdom never computes real layout, so offsetHeight is always 0
    // unless stubbed directly — same technique this file's sibling tests
    // (e.g. inv-index.test.ts) already use for layout-dependent reads.
    Object.defineProperty(el, 'offsetHeight', { value: height, configurable: true });
}

describe('initStickyNavbarOffset', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        document.documentElement.style.removeProperty('--sticky-content-top');
        vi.unstubAllGlobals();
    });

    it('does nothing when there is no sticky navbar on the page', () => {
        document.body.innerHTML = '<nav class="navbar"></nav>';

        expect(() => initStickyNavbarOffset()).not.toThrow();
        expect(document.documentElement.style.getPropertyValue('--sticky-content-top')).toBe('');
    });

    it('applies the navbar\'s real rendered height immediately', () => {
        document.body.innerHTML = '<nav class="navbar sticky-top"></nav>';
        const navbar = document.querySelector('nav') as HTMLElement;
        setOffsetHeight(navbar, 84);

        initStickyNavbarOffset();

        expect(document.documentElement.style.getPropertyValue('--sticky-content-top')).toBe('84px');
    });

    it('observes the navbar with ResizeObserver when it is available', () => {
        document.body.innerHTML = '<nav class="navbar sticky-top"></nav>';
        const navbar = document.querySelector('nav') as HTMLElement;
        setOffsetHeight(navbar, 50);

        const observe = vi.fn();
        const disconnect = vi.fn();
        const ResizeObserverMock = vi.fn().mockImplementation(function (this: object) {
            Object.assign(this, { observe, disconnect });
        });
        vi.stubGlobal('ResizeObserver', ResizeObserverMock);

        initStickyNavbarOffset();

        expect(ResizeObserverMock).toHaveBeenCalledTimes(1);
        expect(observe).toHaveBeenCalledTimes(1);
        expect(observe).toHaveBeenCalledWith(navbar);
    });

    it('re-applies the height when the ResizeObserver callback fires', () => {
        document.body.innerHTML = '<nav class="navbar sticky-top"></nav>';
        const navbar = document.querySelector('nav') as HTMLElement;
        setOffsetHeight(navbar, 50);

        let capturedCallback: (() => void) | undefined;
        const ResizeObserverMock = vi.fn().mockImplementation(function (this: object, cb: () => void) {
            capturedCallback = cb;
            Object.assign(this, { observe: vi.fn(), disconnect: vi.fn() });
        });
        vi.stubGlobal('ResizeObserver', ResizeObserverMock);

        initStickyNavbarOffset();
        expect(document.documentElement.style.getPropertyValue('--sticky-content-top')).toBe('50px');

        // Navbar wraps onto a second line — height grows, no viewport resize involved.
        setOffsetHeight(navbar, 96);
        capturedCallback?.();

        expect(document.documentElement.style.getPropertyValue('--sticky-content-top')).toBe('96px');
    });

    it('still applies the initial height and does not throw when ResizeObserver is unavailable', () => {
        document.body.innerHTML = '<nav class="navbar sticky-top"></nav>';
        const navbar = document.querySelector('nav') as HTMLElement;
        setOffsetHeight(navbar, 60);
        vi.stubGlobal('ResizeObserver', undefined);

        expect(() => initStickyNavbarOffset()).not.toThrow();
        expect(document.documentElement.style.getPropertyValue('--sticky-content-top')).toBe('60px');
    });
});
