import { initColumnResizer } from './column-resizer.js';

/**
 * salesorder/index and salesorder/guest don't use amount-magnifier or
 * group-by-select features (unlike inv/index and quote/index) — confirmed
 * via the views themselves, neither references those elements. Column
 * resizing is the only shared behaviour these two pages need.
 */
export function initSalesOrderIndex(tableId = 'table-salesorder'): void {
    const setup = (): void => {
        initColumnResizer(tableId);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }
}
