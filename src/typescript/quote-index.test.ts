import { beforeEach, describe, expect, it } from 'vitest';
import { initQuoteIndex } from './quote-index.js';

describe('initQuoteIndex', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    });

    it('runs setup immediately when document is ready', () => {
        expect(() => initQuoteIndex()).not.toThrow();
    });

    it('defers setup until DOMContentLoaded when document is loading', () => {
        Object.defineProperty(document, 'readyState', { value: 'loading', configurable: true });
        initQuoteIndex();
        expect(() => document.dispatchEvent(new Event('DOMContentLoaded'))).not.toThrow();
    });

    it('exercises the initGroupCollapsible branch when a group-header row is present', () => {
        document.body.innerHTML = '<tr class="group-header"></tr>';
        expect(() => initQuoteIndex()).not.toThrow();
    });

    it('skips initGroupCollapsible when no group-header is present', () => {
        expect(() => initQuoteIndex()).not.toThrow();
    });

    it('wires the column resizer against the default table-quote id', () => {
        document.body.innerHTML = '<table id="table-quote"><colgroup><col /></colgroup>' +
            '<thead><tr><th>A</th></tr></thead></table>';
        initQuoteIndex();
        expect(document.querySelectorAll('#table-quote .col-resize-handle').length).toBe(1);
    });

    it('wires the column resizer against a custom table id, for reuse on quote/guest', () => {
        document.body.innerHTML = '<table id="table-quote-guest"><colgroup><col /></colgroup>' +
            '<thead><tr><th>A</th></tr></thead></table>';
        initQuoteIndex('table-quote-guest');
        expect(document.querySelectorAll('#table-quote-guest .col-resize-handle').length).toBe(1);
    });
});
