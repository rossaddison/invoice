import { AmountMagnifier, initGroupBySelect, initGroupCollapsible } from './list-utils.js';
import { initColumnResizer } from './column-resizer.js';
import { MobilePreviewToggle } from './mobile-preview-toggle.js';

// Module-level refs keep the MutationObserver and setInterval alive after setup()
let magnifier: AmountMagnifier;
let mobilePreview: MobilePreviewToggle;

export function initInvIndex(tableId = 'table-invoice', configElId = 'inv-filter-config'): void {
    const setup = (): void => {
        const configEl = document.getElementById(configElId);
        const labels = configEl
            ? (JSON.parse(configEl.textContent ?? '{}') as Record<string, string>)
            : {};

        magnifier = new AmountMagnifier(tableId);
        initGroupBySelect();

        Object.entries(labels).forEach(([id, label]) => {
            const sel = document.getElementById(id) as HTMLSelectElement | null;
            if (sel !== null && sel.options.length > 0) sel.options[0].text = label;
        });

        if (document.querySelector('.group-header') !== null) {
            initGroupCollapsible();
        }

        mobilePreview = new MobilePreviewToggle();

        initColumnResizer(tableId);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }
}
