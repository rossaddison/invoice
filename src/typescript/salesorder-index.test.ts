import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { initSalesOrderIndex } from './salesorder-index.js';

describe('initSalesOrderIndex', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
        vi.stubGlobal('location', { href: 'http://localhost/' });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('runs setup immediately when document is ready', () => {
        expect(() => initSalesOrderIndex()).not.toThrow();
    });

    it('defers setup until DOMContentLoaded when document is loading', () => {
        Object.defineProperty(document, 'readyState', { value: 'loading', configurable: true });
        initSalesOrderIndex();
        expect(() => document.dispatchEvent(new Event('DOMContentLoaded'))).not.toThrow();
    });

    it('wires the column resizer against the default table-salesorder id', () => {
        document.body.innerHTML = '<table id="table-salesorder"><colgroup><col /></colgroup>' +
            '<thead><tr><th>A</th></tr></thead></table>';
        initSalesOrderIndex();
        expect(document.querySelectorAll('#table-salesorder .col-resize-handle')).toHaveLength(1);
    });

    it('wires the column resizer against a custom table id, for reuse on salesorder/guest', () => {
        document.body.innerHTML = '<table id="table-salesorder-guest"><colgroup><col /></colgroup>' +
            '<thead><tr><th>A</th></tr></thead></table>';
        initSalesOrderIndex('table-salesorder-guest');
        expect(document.querySelectorAll('#table-salesorder-guest .col-resize-handle')).toHaveLength(1);
    });

    it('wires the group-by select (was a CSP-blocked inline onchange, never fired at all)', () => {
        document.body.innerHTML =
            '<select class="group-by-select" data-base-url="/salesorders">' +
            '<option value="none">None</option><option value="status">Status</option></select>';
        initSalesOrderIndex();
        const select = document.querySelector('select') as HTMLSelectElement;
        select.value = 'status';
        select.dispatchEvent(new Event('change'));
        expect((globalThis.location as { href: string }).href).toBe('/salesorders?groupBy=status');
    });

    it('does not throw on salesorder/guest, which has no group-by select at all', () => {
        document.body.innerHTML = '';
        expect(() => initSalesOrderIndex('table-salesorder-guest')).not.toThrow();
    });
});
