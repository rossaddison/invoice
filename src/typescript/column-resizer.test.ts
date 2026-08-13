import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initColumnResizer } from './column-resizer.js';

const STORAGE_KEY_0 = 'col-width:table-invoice:0';
const STORAGE_KEY_1 = 'col-width:table-invoice:1';

// Module scope (not inside describe()) — SonarQube typescript:S7721: a function
// redefined on every describe() invocation is wasted work when, as here, it
// doesn't close over anything test-local.
function buildTable(): void {
    document.body.innerHTML += `
        <button id="btn-autofit-columns"></button>
        <button id="btn-reset-column-widths"></button>
        <table id="table-invoice">
            <colgroup><col /><col /></colgroup>
            <thead>
                <tr><th id="th0">A</th><th id="th1">B</th></tr>
                <tr><td>filter</td><td>filter</td></tr>
            </thead>
            <tbody>
                <tr><td>1</td><td>2</td></tr>
                <tr><td>3</td><td>4</td></tr>
            </tbody>
        </table>
    `;
    document.querySelectorAll('#table-invoice thead th').forEach((th, i) => {
        vi.spyOn(th, 'getBoundingClientRect').mockReturnValue(
            { width: 100 + i * 20 } as DOMRect,
        );
    });
    // Deliberately much narrower than the header widths above, so
    // autoFit tests can prove it fits to this (the data), not those.
    document.querySelectorAll('#table-invoice tbody td').forEach((td, i) => {
        const colIndex = i % 2;
        vi.spyOn(td, 'getBoundingClientRect').mockReturnValue(
            { width: 25 + colIndex * 5 } as DOMRect,
        );
    });
}

function handle(index: number): HTMLElement {
    return document.querySelectorAll('.col-resize-handle')[index] as HTMLElement;
}

function cols(): HTMLTableColElement[] {
    return Array.from(document.querySelectorAll('#table-invoice colgroup col'));
}

describe('ColumnResizer', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        localStorage.clear();
    });

    it('appends a resize handle to every header cell', () => {
        buildTable();
        initColumnResizer('table-invoice');
        expect(document.querySelectorAll('.col-resize-handle').length).toBe(2);
    });

    it('measures each header width onto the matching col and flips to fixed layout', () => {
        buildTable();
        initColumnResizer('table-invoice');
        expect(cols()[0].style.width).toBe('100px');
        expect(cols()[1].style.width).toBe('120px');
        expect((document.getElementById('table-invoice') as HTMLTableElement).style.tableLayout)
            .toBe('fixed');
    });

    it('restores a previously saved width instead of the measured one', () => {
        localStorage.setItem(STORAGE_KEY_0, '250');
        buildTable();
        initColumnResizer('table-invoice');
        expect(cols()[0].style.width).toBe('250px');
    });

    it('does not add duplicate handles when attach runs again for an unrelated htmx swap', () => {
        buildTable();
        initColumnResizer('table-invoice');
        document.body.dispatchEvent(new Event('htmx:afterSwap'));
        expect(document.querySelectorAll('.col-resize-handle').length).toBe(2);
    });

    it('re-attaches handles to a freshly swapped-in table', () => {
        buildTable();
        initColumnResizer('table-invoice');
        document.getElementById('table-invoice')?.remove();
        buildTable();
        document.body.dispatchEvent(new Event('htmx:afterSwap'));
        expect(document.querySelectorAll('.col-resize-handle').length).toBe(2);
    });

    it('does not throw when the table is absent', () => {
        expect(() => initColumnResizer('table-invoice')).not.toThrow();
    });

    it('does not throw when the header/col counts mismatch', () => {
        document.body.innerHTML += `
            <table id="table-invoice">
                <colgroup><col /></colgroup>
                <thead><tr><th>A</th><th>B</th></tr></thead>
            </table>
        `;
        expect(() => initColumnResizer('table-invoice')).not.toThrow();
        expect(document.querySelectorAll('.col-resize-handle').length).toBe(0);
    });

    describe('dragging', () => {
        beforeEach(() => {
            buildTable();
            initColumnResizer('table-invoice');
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

    describe('autoFit (📐 toolbar button)', () => {
        it('fits to the data, not the (much wider) header — overriding a previously saved width', () => {
            localStorage.setItem(STORAGE_KEY_0, '300');
            buildTable();
            initColumnResizer('table-invoice');
            expect(cols()[0].style.width).toBe('300px');

            document.getElementById('btn-autofit-columns')?.click();

            // buildTable()'s th mock is 100px; the td mock (what a real column's
            // body content usually is) is only 25px — proving autoFit reads the
            // data, not the header, which stays untouched at 100px regardless.
            expect(cols()[0].style.width).toBe('25px');
            expect(localStorage.getItem(STORAGE_KEY_0)).toBe('25');
        });

        it('grows a column whose data is wider than its header, rather than clamping to the header', () => {
            buildTable();
            const wideCell = document.querySelector('#table-invoice tbody tr:first-child td') as HTMLElement;
            vi.spyOn(wideCell, 'getBoundingClientRect').mockReturnValue({ width: 250 } as DOMRect);
            initColumnResizer('table-invoice');

            document.getElementById('btn-autofit-columns')?.click();

            // Header (buildTable()'s th mock) is only 100px — a real column
            // with an interactive control or long value (e.g. a <select>, a
            // status badge, a full name) legitimately needs more room than
            // its short header label, and autoFit lets it.
            expect(cols()[0].style.width).toBe('250px');
        });

        it('leaves the table on fixed layout after fitting', () => {
            buildTable();
            initColumnResizer('table-invoice');
            document.getElementById('btn-autofit-columns')?.click();
            expect((document.getElementById('table-invoice') as HTMLTableElement).style.tableLayout)
                .toBe('fixed');
        });

        it('re-measures every column, not just the first', () => {
            localStorage.setItem(STORAGE_KEY_0, '999');
            localStorage.setItem(STORAGE_KEY_1, '999');
            buildTable();
            initColumnResizer('table-invoice');
            document.getElementById('btn-autofit-columns')?.click();
            expect(cols()[0].style.width).toBe('25px');
            expect(cols()[1].style.width).toBe('30px');
        });

        it('uses the widest row when body rows disagree', () => {
            buildTable();
            const secondRowCell = document.querySelectorAll('#table-invoice tbody tr')[1]
                .children[0] as HTMLElement;
            vi.spyOn(secondRowCell, 'getBoundingClientRect').mockReturnValue({ width: 60 } as DOMRect);
            initColumnResizer('table-invoice');

            document.getElementById('btn-autofit-columns')?.click();

            expect(cols()[0].style.width).toBe('60px');
        });

        it('skips a column with no body rows at all rather than zeroing its width', () => {
            document.body.innerHTML += `
                <button id="btn-autofit-columns"></button>
                <table id="table-invoice">
                    <colgroup><col /></colgroup>
                    <thead><tr><th>A</th></tr></thead>
                    <tbody></tbody>
                </table>
            `;
            initColumnResizer('table-invoice');
            const col = document.querySelector('#table-invoice colgroup col') as HTMLTableColElement;
            col.style.width = '80px';

            document.getElementById('btn-autofit-columns')?.click();

            expect(col.style.width).toBe('');
        });

        it('does not throw when the button exists but the table is absent', () => {
            document.body.innerHTML += '<button id="btn-autofit-columns"></button>';
            initColumnResizer('table-invoice');
            expect(() => document.getElementById('btn-autofit-columns')?.click()).not.toThrow();
        });

        it('does nothing when the button is absent', () => {
            expect(() => initColumnResizer('table-invoice')).not.toThrow();
        });
    });

    describe('reset (🔄 toolbar button)', () => {
        it('clears the col width and the saved localStorage entry', () => {
            buildTable();
            initColumnResizer('table-invoice');
            expect(cols()[0].style.width).toBe('100px');
            expect(localStorage.getItem(STORAGE_KEY_0)).toBeNull();

            document.getElementById('btn-autofit-columns')?.click();
            expect(localStorage.getItem(STORAGE_KEY_0)).toBe('25');

            document.getElementById('btn-reset-column-widths')?.click();

            expect(cols()[0].style.width).toBe('');
            expect(localStorage.getItem(STORAGE_KEY_0)).toBeNull();
        });

        it('clears every column, not just the first', () => {
            buildTable();
            initColumnResizer('table-invoice');
            document.getElementById('btn-reset-column-widths')?.click();
            expect(cols()[0].style.width).toBe('');
            expect(cols()[1].style.width).toBe('');
            expect(localStorage.getItem(STORAGE_KEY_1)).toBeNull();
        });

        it('reverts the table to auto layout', () => {
            buildTable();
            initColumnResizer('table-invoice');
            document.getElementById('btn-reset-column-widths')?.click();
            expect((document.getElementById('table-invoice') as HTMLTableElement).style.tableLayout)
                .toBe('auto');
        });

        it('a subsequent attach() (e.g. next HTMX swap) re-measures fresh, ignoring the old saved width', () => {
            localStorage.setItem(STORAGE_KEY_0, '777');
            buildTable();
            initColumnResizer('table-invoice');
            document.getElementById('btn-reset-column-widths')?.click();

            document.body.dispatchEvent(new Event('htmx:afterSwap'));

            expect(cols()[0].style.width).toBe('100px');
        });

        it('does not throw when the button exists but the table is absent', () => {
            document.body.innerHTML += '<button id="btn-reset-column-widths"></button>';
            initColumnResizer('table-invoice');
            expect(() => document.getElementById('btn-reset-column-widths')?.click()).not.toThrow();
        });
    });

    describe('multiple tables on one page', () => {
        it('uses independent localStorage keys per table id, via distinct button ids', () => {
            document.body.innerHTML = `
                <button id="btn-autofit-a"></button>
                <table id="table-a">
                    <colgroup><col /></colgroup>
                    <thead><tr><th>A</th></tr></thead>
                    <tbody><tr><td>x</td></tr></tbody>
                </table>
                <button id="btn-autofit-b"></button>
                <table id="table-b">
                    <colgroup><col /></colgroup>
                    <thead><tr><th>B</th></tr></thead>
                    <tbody><tr><td>y</td></tr></tbody>
                </table>
            `;
            document.querySelectorAll('#table-a tbody td').forEach((td) => {
                vi.spyOn(td, 'getBoundingClientRect').mockReturnValue({ width: 40 } as DOMRect);
            });
            document.querySelectorAll('#table-b tbody td').forEach((td) => {
                vi.spyOn(td, 'getBoundingClientRect').mockReturnValue({ width: 90 } as DOMRect);
            });

            initColumnResizer('table-a', { autoFitButtonId: 'btn-autofit-a' });
            initColumnResizer('table-b', { autoFitButtonId: 'btn-autofit-b' });

            document.getElementById('btn-autofit-a')?.click();

            expect(localStorage.getItem('col-width:table-a:0')).toBe('40');
            expect(localStorage.getItem('col-width:table-b:0')).toBeNull();
        });
    });
});
