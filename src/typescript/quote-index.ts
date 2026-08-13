import { AmountMagnifier, initGroupBySelect, initGroupCollapsible } from './list-utils.js';
import { initColumnResizer } from './column-resizer.js';

// Module-level ref keeps the MutationObserver alive after setup()
let magnifier: AmountMagnifier;

export function initQuoteIndex(tableId = 'table-quote'): void {
    const setup = (): void => {
        magnifier = new AmountMagnifier(tableId);
        initGroupBySelect();

        if (document.querySelector('.group-header') !== null) {
            initGroupCollapsible();
        }

        initColumnResizer(tableId);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }
}
