/**
 * Restores which Bootstrap tab is active from the URL's hash on page load —
 * generic, not product-specific: any page using the standard
 * `data-bs-toggle="tab"` markup benefits, no-ops elsewhere. Bootstrap's own
 * tab JS never reads `location.hash` itself; without this, a server redirect
 * back to e.g. `product/view/154#product-images` (App\Invoice\Product\
 * ProductAttachmentController::imageAttachment(), App\Invoice\ProductImage\
 * ProductImageController::delete() — both redirect a customer/admin back to
 * whichever tab they were just working in) always lands on the first tab
 * instead, since Bootstrap tabs are pure client-side state with no
 * server-rendered "which tab was active" concept at all.
 */
export function initTabHashRestore(): void {
    const hash = globalThis.location.hash;
    if (hash === '' || hash === '#') return;

    const bs = globalThis.bootstrap;
    if (!bs?.Tab) return;

    // Compared in JS rather than built into a `[href="..."]` selector
    // string — the hash is address-bar-editable input, and this avoids
    // any selector-injection concern entirely rather than trying to
    // escape it correctly.
    const trigger = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]')).find(
        element => element.getAttribute('href') === hash,
    );
    if (!trigger) return;

    try {
        bs.Tab.getOrCreateInstance(trigger).show();
    } catch (error) {
        console.warn('Tab restore from hash failed:', error);
    }
}
