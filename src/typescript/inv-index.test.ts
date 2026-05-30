import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { initInvIndex } from './inv-index.js';

const MOBILE_TEXT = '📱 Mobile Preview';
const DESKTOP_TEXT = '🖥️ Close Preview';
const CLASS_MP_ON = 'mp-on';
const CLASS_MP_VISIBLE = 'mp-visible';

function toggleBtn(): HTMLButtonElement {
    return document.querySelector<HTMLButtonElement>('.mp-btn')!;
}

function sideTab(): HTMLButtonElement {
    return document.querySelector<HTMLButtonElement>('.mp-side-tab')!;
}

function dismissBtn(): HTMLButtonElement {
    return document.querySelector<HTMLButtonElement>('.mp-dismiss')!;
}

describe('initInvIndex', () => {
    let mockWin: { closed: boolean; close: ReturnType<typeof vi.fn> };

    beforeEach(() => {
        vi.useFakeTimers();
        document.body.innerHTML = '';
        document.head.innerHTML = '';
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
        mockWin = { closed: false, close: vi.fn() };
        vi.spyOn(globalThis, 'open').mockReturnValue(mockWin as unknown as WindowProxy);
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('runs setup immediately when document is ready', () => {
        initInvIndex();
        expect(toggleBtn()).not.toBeNull();
    });

    it('defers setup until DOMContentLoaded when document is loading', () => {
        Object.defineProperty(document, 'readyState', { value: 'loading', configurable: true });
        initInvIndex();
        expect(toggleBtn()).toBeNull();
        document.dispatchEvent(new Event('DOMContentLoaded'));
        expect(toggleBtn()).not.toBeNull();
    });

    it('injects mp-styles into document head', () => {
        initInvIndex();
        expect(document.getElementById('mp-styles')).not.toBeNull();
    });

    it('does not inject mp-styles a second time when already present', () => {
        initInvIndex();
        document.body.innerHTML = '';
        initInvIndex();
        expect(document.querySelectorAll('#mp-styles').length).toBe(1);
    });

    it('creates toggle button with label span and dismiss button', () => {
        initInvIndex();
        expect(toggleBtn().querySelector('span')?.textContent).toBe(MOBILE_TEXT);
        expect(dismissBtn()).not.toBeNull();
    });

    it('creates side tab button', () => {
        initInvIndex();
        expect(sideTab()).not.toBeNull();
    });

    it('updates first option text from inv-filter-config labels', () => {
        document.body.innerHTML = `
            <script id="inv-filter-config" type="application/json">{"status-filter":"All Statuses"}</script>
            <select id="status-filter"><option value="">--</option></select>
        `;
        initInvIndex();
        const sel = document.getElementById('status-filter') as HTMLSelectElement;
        expect(sel.options[0].text).toBe('All Statuses');
    });

    it('skips option update when labelled select does not exist', () => {
        document.body.innerHTML = `
            <script id="inv-filter-config" type="application/json">{"no-such-id":"Label"}</script>
        `;
        expect(() => initInvIndex()).not.toThrow();
    });

    it('falls back gracefully when inv-filter-config is absent', () => {
        expect(() => initInvIndex()).not.toThrow();
    });

    it('exercises the initGroupCollapsible branch when a group-header row is present', () => {
        document.body.innerHTML = '<tr class="group-header"></tr>';
        expect(() => initInvIndex()).not.toThrow();
    });

    it('skips initGroupCollapsible when no group-header is present', () => {
        expect(() => initInvIndex()).not.toThrow();
    });

    describe('toggle button — activate / deactivate', () => {
        beforeEach(() => {
            initInvIndex();
        });

        it('activate opens popup with 390 px width features', () => {
            toggleBtn().click();
            expect(globalThis.open).toHaveBeenCalledWith(
                globalThis.location.href,
                'mp-preview',
                expect.stringContaining('width=390'),
            );
        });

        it('activate adds mp-on class and changes span text', () => {
            toggleBtn().click();
            expect(toggleBtn().classList.contains(CLASS_MP_ON)).toBe(true);
            expect(toggleBtn().querySelector('span')?.textContent).toBe(DESKTOP_TEXT);
        });

        it('deactivate calls close on the popup window', () => {
            toggleBtn().click();
            toggleBtn().click();
            expect(mockWin.close).toHaveBeenCalledOnce();
        });

        it('deactivate removes mp-on class and resets span text', () => {
            toggleBtn().click();
            toggleBtn().click();
            expect(toggleBtn().classList.contains(CLASS_MP_ON)).toBe(false);
            expect(toggleBtn().querySelector('span')?.textContent).toBe(MOBILE_TEXT);
        });

        it('deactivate skips close when popup is already closed by the user', () => {
            toggleBtn().click();
            mockWin.closed = true;
            toggleBtn().click();
            expect(mockWin.close).not.toHaveBeenCalled();
        });
    });

    describe('collapse and restore', () => {
        beforeEach(() => {
            initInvIndex();
        });

        it('collapse hides toggle button and reveals side tab', () => {
            dismissBtn().click();
            expect(toggleBtn().style.display).toBe('none');
            expect(sideTab().classList.contains(CLASS_MP_VISIBLE)).toBe(true);
        });

        it('collapse while active also deactivates first', () => {
            toggleBtn().click();
            dismissBtn().click();
            expect(toggleBtn().classList.contains(CLASS_MP_ON)).toBe(false);
        });

        it('restore shows toggle button and hides side tab', () => {
            dismissBtn().click();
            sideTab().click();
            expect(toggleBtn().style.display).toBe('');
            expect(sideTab().classList.contains(CLASS_MP_VISIBLE)).toBe(false);
        });
    });

    describe('watchPopup interval', () => {
        beforeEach(() => {
            initInvIndex();
        });

        it('resets state when the popup window is closed externally', () => {
            toggleBtn().click();
            mockWin.closed = true;
            vi.advanceTimersByTime(800);
            expect(toggleBtn().classList.contains(CLASS_MP_ON)).toBe(false);
            expect(toggleBtn().querySelector('span')?.textContent).toBe(MOBILE_TEXT);
        });

        it('does nothing when the toggle was never activated', () => {
            vi.advanceTimersByTime(800);
            expect(toggleBtn().classList.contains(CLASS_MP_ON)).toBe(false);
        });
    });
});
