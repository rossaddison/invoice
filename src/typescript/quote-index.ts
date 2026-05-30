import { AmountMagnifier, initGroupBySelect, initGroupCollapsible } from './list-utils.js';

// Module-level ref keeps the MutationObserver alive after setup()
let magnifier: AmountMagnifier;

export function initQuoteIndex(): void {
    const setup = (): void => {
        magnifier = new AmountMagnifier('table-quote');
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
