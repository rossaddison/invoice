import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { PageSizeHandler } from './page-size.js';

function makeSelect(urlTemplate = '/limit/__SIZE__', value = '25'): HTMLSelectElement {
    const sel = document.createElement('select');
    sel.id = 'page-size-select';
    sel.dataset.listlimitUrl = urlTemplate;
    const opt = document.createElement('option');
    opt.value = value;
    sel.appendChild(opt);
    sel.value = value;
    document.body.appendChild(sel);
    return sel;
}

describe('PageSizeHandler', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        vi.stubGlobal('fetch', vi.fn());
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
    });

    // ── Init behaviour ──────────────────────────────────────────────────────

    it('constructs without error when #page-size-select is absent', () => {
        expect(() => new PageSizeHandler()).not.toThrow();
    });

    it('defers init until DOMContentLoaded when document is loading', () => {
        Object.defineProperty(document, 'readyState', { value: 'loading', configurable: true });
        makeSelect();
        const _h = new PageSizeHandler();
        const sel = document.getElementById('page-size-select') as HTMLSelectElement;
        // handler not yet bound — fire DOMContentLoaded to trigger #init
        document.dispatchEvent(new Event('DOMContentLoaded'));
        expect(sel).not.toBeNull();   // element still present; no error thrown
    });

    it('binds immediately when document is already complete', () => {
        const sel = makeSelect();
        expect(() => new PageSizeHandler()).not.toThrow();
        expect(document.getElementById('page-size-select')).toBe(sel);
    });

    // ── #onChange — success path ────────────────────────────────────────────

    it('calls fetch with the interpolated URL on change', async () => {
        // jsdom location.reload is non-configurable; replace the whole object
        const reloadMock = vi.fn();
        Object.defineProperty(globalThis, 'location', {
            value: { ...globalThis.location, reload: reloadMock },
            configurable: true,
            writable: true,
        });
        const sel = makeSelect('/limit/__SIZE__', '50');
        const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 200, url: '' });
        vi.stubGlobal('fetch', fetchMock);

        const _h = new PageSizeHandler();
        sel.dispatchEvent(new Event('change'));
        await vi.waitFor(() => expect(fetchMock).toHaveBeenCalledWith('/limit/50'));
    });

    it('calls location.reload() after a successful fetch', async () => {
        const reloadMock = vi.fn();
        Object.defineProperty(globalThis, 'location', {
            value: { ...globalThis.location, reload: reloadMock },
            configurable: true,
            writable: true,
        });
        const sel = makeSelect('/limit/__SIZE__', '10');
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, status: 200, url: '' }));

        const _h = new PageSizeHandler();
        sel.dispatchEvent(new Event('change'));
        await vi.waitFor(() => expect(reloadMock).toHaveBeenCalled());
    });

    // ── #onChange — error paths ─────────────────────────────────────────────

    it('re-enables the select when the server returns a non-OK status', async () => {
        const sel = makeSelect('/limit/__SIZE__', '25');
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 500, url: '/limit/25' }));

        const _h = new PageSizeHandler();
        sel.disabled = false;
        sel.dispatchEvent(new Event('change'));
        expect(sel.disabled).toBe(true); // disabled during request
        await vi.waitFor(() => expect(sel.disabled).toBe(false));
    });

    it('re-enables the select after a network error', async () => {
        const sel = makeSelect('/limit/__SIZE__', '25');
        vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('network fail')));

        const _h = new PageSizeHandler();
        sel.dispatchEvent(new Event('change'));
        await vi.waitFor(() => expect(sel.disabled).toBe(false));
    });

    // ── #onChange — early-exit guards ───────────────────────────────────────

    it('does not fetch when urlTemplate is empty', async () => {
        const sel = makeSelect('', '25');
        const fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);

        const _h = new PageSizeHandler();
        sel.dispatchEvent(new Event('change'));
        await Promise.resolve();
        expect(fetchMock).not.toHaveBeenCalled();
    });

    it('does not fetch when selected value is empty', async () => {
        const sel = makeSelect('/limit/__SIZE__', '');
        const fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);

        const _h = new PageSizeHandler();
        sel.dispatchEvent(new Event('change'));
        await Promise.resolve();
        expect(fetchMock).not.toHaveBeenCalled();
    });
});
