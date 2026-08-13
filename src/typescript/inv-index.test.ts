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

    describe('ColumnResizer', () => {
        const STORAGE_KEY_0 = 'col-width:table-invoice:0';
        const STORAGE_KEY_1 = 'col-width:table-invoice:1';

        function buildTable(): void {
            document.body.innerHTML += `
                <table id="table-invoice">
                    <colgroup><col /><col /></colgroup>
                    <thead>
                        <tr><th id="th0">A</th><th id="th1">B</th></tr>
                        <tr><td>filter</td><td>filter</td></tr>
                    </thead>
                    <tbody><tr><td>1</td><td>2</td></tr></tbody>
                </table>
            `;
            document.querySelectorAll('#table-invoice thead th').forEach((th, i) => {
                vi.spyOn(th, 'getBoundingClientRect').mockReturnValue(
                    { width: 100 + i * 20 } as DOMRect,
                );
            });
        }

        function handle(index: number): HTMLElement {
            return document.querySelectorAll('.col-resize-handle')[index] as HTMLElement;
        }

        function cols(): HTMLTableColElement[] {
            return Array.from(document.querySelectorAll('#table-invoice colgroup col'));
        }

        beforeEach(() => {
            localStorage.clear();
        });

        it('appends a resize handle to every header cell', () => {
            buildTable();
            initInvIndex();
            expect(document.querySelectorAll('.col-resize-handle').length).toBe(2);
        });

        it('measures each header width onto the matching col and flips to fixed layout', () => {
            buildTable();
            initInvIndex();
            expect(cols()[0].style.width).toBe('100px');
            expect(cols()[1].style.width).toBe('120px');
            expect((document.getElementById('table-invoice') as HTMLTableElement).style.tableLayout)
                .toBe('fixed');
        });

        it('restores a previously saved width instead of the measured one', () => {
            localStorage.setItem(STORAGE_KEY_0, '250');
            buildTable();
            initInvIndex();
            expect(cols()[0].style.width).toBe('250px');
        });

        it('does not add duplicate handles when attach runs again for an unrelated htmx swap', () => {
            buildTable();
            initInvIndex();
            document.body.dispatchEvent(new Event('htmx:afterSwap'));
            expect(document.querySelectorAll('.col-resize-handle').length).toBe(2);
        });

        it('re-attaches handles to a freshly swapped-in table', () => {
            buildTable();
            initInvIndex();
            document.getElementById('table-invoice')?.remove();
            buildTable();
            document.body.dispatchEvent(new Event('htmx:afterSwap'));
            expect(document.querySelectorAll('.col-resize-handle').length).toBe(2);
        });

        it('does not throw when the table is absent', () => {
            expect(() => initInvIndex()).not.toThrow();
        });

        it('does not throw when the header/col counts mismatch', () => {
            document.body.innerHTML += `
                <table id="table-invoice">
                    <colgroup><col /></colgroup>
                    <thead><tr><th>A</th><th>B</th></tr></thead>
                </table>
            `;
            expect(() => initInvIndex()).not.toThrow();
            expect(document.querySelectorAll('.col-resize-handle').length).toBe(0);
        });

        describe('dragging', () => {
            beforeEach(() => {
                buildTable();
                initInvIndex();
            });

            it('drag updates the column width live', () => {
                handle(0).dispatchEvent(new MouseEvent('mousedown', { clientX: 100 }));
                document.dispatchEvent(new MouseEvent('mousemove', { clientX: 150 }));
                expect(cols()[0].style.width).toBe('150px');
            });

            it('clamps the width to a 40px minimum', () => {
                handle(0).dispatchEvent(new MouseEvent('mousedown', { clientX: 100 }));
                document.dispatchEvent(new MouseEvent('mousemove', { clientX: -1000 }));
                expect(cols()[0].style.width).toBe('40px');
            });

            it('adds col-resizing to body while dragging and removes it on mouseup', () => {
                handle(0).dispatchEvent(new MouseEvent('mousedown', { clientX: 100 }));
                expect(document.body.classList.contains('col-resizing')).toBe(true);
                document.dispatchEvent(new MouseEvent('mouseup'));
                expect(document.body.classList.contains('col-resizing')).toBe(false);
            });

            it('persists the final width to localStorage on mouseup', () => {
                handle(0).dispatchEvent(new MouseEvent('mousedown', { clientX: 100 }));
                document.dispatchEvent(new MouseEvent('mousemove', { clientX: 175 }));
                document.dispatchEvent(new MouseEvent('mouseup'));
                expect(localStorage.getItem(STORAGE_KEY_0)).toBe('175');
            });

            it('does not move a different column while dragging column 0', () => {
                handle(0).dispatchEvent(new MouseEvent('mousedown', { clientX: 100 }));
                document.dispatchEvent(new MouseEvent('mousemove', { clientX: 150 }));
                expect(cols()[1].style.width).toBe('120px');
            });

            it('stops responding to mousemove after mouseup', () => {
                handle(0).dispatchEvent(new MouseEvent('mousedown', { clientX: 100 }));
                document.dispatchEvent(new MouseEvent('mouseup'));
                document.dispatchEvent(new MouseEvent('mousemove', { clientX: 500 }));
                expect(cols()[0].style.width).toBe('100px');
            });
        });
    });
});
