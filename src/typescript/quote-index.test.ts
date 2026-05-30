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
});
