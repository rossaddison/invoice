/**
 * BACS Quick-Pay Modal — Clipboard Copy Buttons
 *
 * Initialises ClipboardJS for the '.bacs-copy-btn' buttons rendered by
 * _modal_bacs_quickpay.php. Moved out of an inline <script> in that view
 * (CSP script-src has no 'unsafe-inline') — same migration pattern as
 * payment-adyen.ts / payment-braintree.ts.
 *
 * ClipboardJS itself is loaded globally via NodeModulesClipBoardAsset /
 * ClipBoardCdnAsset (a dependency of InvoiceNodeModulesAsset /
 * InvoiceCdnAsset), not imported here.
 */

declare global {
    interface ClipboardJsEvent {
        trigger: HTMLElement;
        clearSelection(): void;
    }
    interface ClipboardJsInstance {
        on(event: 'success', handler: (e: ClipboardJsEvent) => void): void;
    }
    // The global ClipboardJS() constructor injected by the CDN/node_modules bundle
    const ClipboardJS: new (selector: string) => ClipboardJsInstance;
}

let initialised = false;

export function initBacsQuickPay(): void {
    if (initialised) return;
    if (!document.querySelector('.bacs-copy-btn')) return; // not a page with the BACS modal

    initialised = true;
    const clipboard = new ClipboardJS('.bacs-copy-btn');
    clipboard.on('success', (e) => {
        const button = e.trigger;
        const original = button.innerHTML;
        button.innerHTML = '<i class="bi bi-clipboard-check fs-6 text-success"></i>';
        globalThis.setTimeout(() => {
            button.innerHTML = original;
        }, 1500);
        e.clearSelection();
    });
}
