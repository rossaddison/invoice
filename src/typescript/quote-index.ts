import { AmountMagnifier, initGroupBySelect, initGroupCollapsible } from './list-utils.js';

export function initQuoteIndex(): void {
    const setup = (): void => {
        new AmountMagnifier('table-quote');
        initGroupBySelect();

        if (document.querySelector('.group-header') !== null) {
            initGroupCollapsible();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }
}
