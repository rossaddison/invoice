import { initColumnResizer } from './column-resizer.js';
import { initGroupBySelect } from './list-utils.js';

/**
 * salesorder/index and salesorder/guest don't use amount-magnifier
 * (unlike inv/index and quote/index) — confirmed via the views
 * themselves, neither references it. Group-by *is* used on
 * salesorder/index (SalesOrdersListWidget's own $groupBySelect) — it
 * used to be wired via a raw inline 'onchange' attribute instead of
 * this shared, CSP-safe handler, so it silently never fired at all
 * under this app's script-src (no 'unsafe-inline'). initGroupBySelect()
 * is a no-op when its '.group-by-select' target isn't present (e.g. on
 * salesorder/guest, which has no grouping control), so calling it here
 * unconditionally is safe for both pages.
 */
export function initSalesOrderIndex(tableId = 'table-salesorder'): void {
    const setup = (): void => {
        initColumnResizer(tableId);
        initGroupBySelect();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }
}
