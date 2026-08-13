import { beforeEach, describe, expect, it } from 'vitest';
import { initSalesOrderIndex } from './salesorder-index.js';

describe('initSalesOrderIndex', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
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
});
