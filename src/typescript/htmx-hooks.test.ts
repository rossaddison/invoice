import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { initHtmxHooks } from './htmx-hooks.js';

type Ext = Record<string, unknown>;

/** The htmx events the allocation-cell hooks listen for, fired the way htmx fires them. */
function fire(type: string, target: Element, detail: Record<string, unknown> = {}): void {
    target.dispatchEvent(new CustomEvent(type, { bubbles: true, detail: { target, ...detail } }));
}

describe('inv/guest htmx hooks', () => {
    beforeAll(() => {
        (globalThis as Ext)['htmx'] = { config: {}, process: vi.fn() };
        initHtmxHooks();
    });

    afterEach(() => {
        document.body.innerHTML = '';
        delete (globalThis as Ext)['bootstrap'];
        vi.restoreAllMocks();
    });

    it('disposes the tooltips and dropdowns of an allocation cell before it is swapped out', () => {
        document.body.innerHTML =
            '<div id="alloc-12"><button data-bs-toggle="tooltip"></button>' +
            '<button class="dropdown-toggle"></button></div>';
        const tipDispose = vi.fn();
        const ddDispose = vi.fn();
        (globalThis as Ext)['bootstrap'] = {
            Tooltip: { getInstance: () => ({ dispose: tipDispose }) },
            Dropdown: { getInstance: () => ({ dispose: ddDispose }) },
        };

        fire('htmx:beforeSwap', document.getElementById('alloc-12')!);

        expect(tipDispose).toHaveBeenCalledTimes(1);
        expect(ddDispose).toHaveBeenCalledTimes(1);
    });

    it('leaves a swap of any other element alone', () => {
        document.body.innerHTML = '<div id="other"><button data-bs-toggle="tooltip"></button></div>';
        const dispose = vi.fn();
        (globalThis as Ext)['bootstrap'] = { Tooltip: { getInstance: () => ({ dispose }) } };

        fire('htmx:beforeSwap', document.getElementById('other')!);

        expect(dispose).not.toHaveBeenCalled();
    });

    it('initialises the new tooltips after a cell is swapped in', () => {
        document.body.innerHTML = '<div id="alloc-12"><button data-bs-toggle="tooltip"></button></div>';
        const getOrCreateInstance = vi.fn();
        (globalThis as Ext)['bootstrap'] = { Tooltip: { getOrCreateInstance } };

        fire('htmx:afterSwap', document.getElementById('alloc-12')!);

        expect(getOrCreateInstance).toHaveBeenCalledTimes(1);
    });

    it('reloads when an allocation post fails, so a stale cell is never left looking right', () => {
        document.body.innerHTML = '<div id="alloc-12"></div>';
        const reload = vi.fn();
        vi.spyOn(globalThis, 'location', 'get').mockReturnValue({ ...globalThis.location, reload } as Location);

        fire('htmx:responseError', document.getElementById('alloc-12')!);
        fire('htmx:sendError', document.getElementById('alloc-12')!);

        expect(reload).toHaveBeenCalledTimes(2);
    });

    it('does not reload for a failed request that is not an allocation cell', () => {
        document.body.innerHTML = '<div id="elsewhere"></div>';
        const reload = vi.fn();
        vi.spyOn(globalThis, 'location', 'get').mockReturnValue({ ...globalThis.location, reload } as Location);

        fire('htmx:responseError', document.getElementById('elsewhere')!);

        expect(reload).not.toHaveBeenCalled();
    });

    it('refreshes only #main-area after the crew form is saved, with no page reload', async () => {
        document.body.innerHTML =
            '<div id="main-area"><form data-hx-refresh-main="1" id="crew"></form></div>';
        const fresh = '<html><body><div id="main-area" data-fresh="1"></div></body></html>';
        const fetchMock = vi.fn().mockResolvedValue({ text: () => Promise.resolve(fresh) });
        vi.stubGlobal('fetch', fetchMock);
        const reload = vi.fn();
        vi.spyOn(globalThis, 'location', 'get').mockReturnValue({ ...globalThis.location, reload } as Location);

        document
            .getElementById('crew')!
            .dispatchEvent(new CustomEvent('htmx:afterRequest', { bubbles: true, detail: { successful: true } }));
        await vi.waitFor(() => expect(document.getElementById('main-area')?.dataset['fresh']).toBe('1'));

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(reload).not.toHaveBeenCalled();
        vi.unstubAllGlobals();
    });

    it('does not refresh when the crew save failed', () => {
        document.body.innerHTML = '<div id="main-area"><form data-hx-refresh-main="1" id="crew"></form></div>';
        const fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);

        document
            .getElementById('crew')!
            .dispatchEvent(new CustomEvent('htmx:afterRequest', { bubbles: true, detail: { successful: false } }));

        expect(fetchMock).not.toHaveBeenCalled();
        vi.unstubAllGlobals();
    });
});
