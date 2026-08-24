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
});
