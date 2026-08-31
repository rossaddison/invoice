import { afterEach, describe, expect, it, vi } from 'vitest';
import { initTabHashRestore } from './tab-hash-restore.js';

function setHash(hash: string): void {
    // jsdom's location.hash setter is configurable, but stubbing the whole
    // object (same technique page-size.test.ts already uses for
    // location.reload) keeps every test here self-contained regardless.
    Object.defineProperty(globalThis, 'location', {
        value: { ...globalThis.location, hash },
        writable: true,
        configurable: true,
    });
}

describe('initTabHashRestore', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        document.body.innerHTML = '';
        setHash('');
    });

    it('returns early when there is no hash', () => {
        setHash('');
        vi.stubGlobal('bootstrap', { Tab: { getOrCreateInstance: vi.fn() } });
        expect(() => initTabHashRestore()).not.toThrow();
    });

    it('returns early when bootstrap is not on globalThis', () => {
        setHash('#product-images');
        vi.stubGlobal('bootstrap', undefined);
        expect(() => initTabHashRestore()).not.toThrow();
    });

    it('returns early when bootstrap.Tab is missing', () => {
        setHash('#product-images');
        vi.stubGlobal('bootstrap', {});
        expect(() => initTabHashRestore()).not.toThrow();
    });

    it('returns early when no tab trigger matches the hash', () => {
        setHash('#nonexistent-tab');
        const getOrCreateInstance = vi.fn();
        vi.stubGlobal('bootstrap', { Tab: { getOrCreateInstance } });
        document.body.innerHTML = '<a data-bs-toggle="tab" href="#product-details"></a>';
        initTabHashRestore();
        expect(getOrCreateInstance).not.toHaveBeenCalled();
    });

    it('shows the tab whose trigger href matches the hash', () => {
        setHash('#product-images');
        const show = vi.fn();
        const getOrCreateInstance = vi.fn().mockReturnValue({ show });
        vi.stubGlobal('bootstrap', { Tab: { getOrCreateInstance } });
        document.body.innerHTML = `
            <a data-bs-toggle="tab" href="#product-details"></a>
            <a data-bs-toggle="tab" href="#product-images"></a>`;

        initTabHashRestore();

        const [[triggerArg]] = getOrCreateInstance.mock.calls;
        expect((triggerArg as HTMLElement).getAttribute('href')).toBe('#product-images');
        expect(show).toHaveBeenCalledTimes(1);
    });

    it('ignores exceptions thrown while showing the tab', () => {
        setHash('#product-images');
        const getOrCreateInstance = vi.fn().mockImplementation(() => {
            throw new Error('fail');
        });
        vi.stubGlobal('bootstrap', { Tab: { getOrCreateInstance } });
        document.body.innerHTML = '<a data-bs-toggle="tab" href="#product-images"></a>';
        expect(() => initTabHashRestore()).not.toThrow();
    });

    it('shows the tab whose button trigger targets the hash via data-bs-target', () => {
        // Client's tabs use <button data-bs-target="..."> rather than
        // <a href="...">; buttons have no href attribute at all.
        setHash('#pane-address');
        const show = vi.fn();
        const getOrCreateInstance = vi.fn().mockReturnValue({ show });
        vi.stubGlobal('bootstrap', { Tab: { getOrCreateInstance } });
        document.body.innerHTML = `
            <button data-bs-toggle="tab" data-bs-target="#pane-personal"></button>
            <button data-bs-toggle="tab" data-bs-target="#pane-address"></button>`;

        initTabHashRestore();

        const [[triggerArg]] = getOrCreateInstance.mock.calls;
        expect((triggerArg as HTMLElement).dataset.bsTarget).toBe('#pane-address');
        expect(show).toHaveBeenCalledTimes(1);
    });

    it('activates the containing pane and scrolls to a field nested inside it', () => {
        setHash('#postaladdress_field');
        const show = vi.fn();
        const getOrCreateInstance = vi.fn().mockReturnValue({ show });
        vi.stubGlobal('bootstrap', { Tab: { getOrCreateInstance } });
        document.body.innerHTML = `
            <button data-bs-toggle="tab" data-bs-target="#pane-personal"></button>
            <button data-bs-toggle="tab" data-bs-target="#pane-address"></button>
            <div class="tab-pane" id="pane-address">
                <div id="postaladdress_field"></div>
            </div>`;
        const target = document.getElementById('postaladdress_field') as HTMLElement;
        const scrollIntoView = vi.fn();
        target.scrollIntoView = scrollIntoView;

        initTabHashRestore();

        const [[triggerArg]] = getOrCreateInstance.mock.calls;
        expect((triggerArg as HTMLElement).dataset.bsTarget).toBe('#pane-address');
        expect(show).toHaveBeenCalledTimes(1);
        expect(scrollIntoView).toHaveBeenCalledTimes(1);
    });

    it('returns early when the hash matches no element and no trigger', () => {
        setHash('#nowhere');
        const getOrCreateInstance = vi.fn();
        vi.stubGlobal('bootstrap', { Tab: { getOrCreateInstance } });
        document.body.innerHTML = '<button data-bs-toggle="tab" data-bs-target="#pane-address"></button>';

        expect(() => initTabHashRestore()).not.toThrow();
        expect(getOrCreateInstance).not.toHaveBeenCalled();
    });

    it('returns early when the nested element has no enclosing tab-pane', () => {
        setHash('#orphan-field');
        const getOrCreateInstance = vi.fn();
        vi.stubGlobal('bootstrap', { Tab: { getOrCreateInstance } });
        document.body.innerHTML = `
            <button data-bs-toggle="tab" data-bs-target="#pane-address"></button>
            <div id="orphan-field"></div>`;

        expect(() => initTabHashRestore()).not.toThrow();
        expect(getOrCreateInstance).not.toHaveBeenCalled();
    });
});
