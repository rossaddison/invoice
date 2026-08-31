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
 *
 * Two trigger shapes, both real in this codebase:
 *  1. `<a data-bs-toggle="tab" href="#pane-x">` — Product's tabs.
 *  2. `<button data-bs-toggle="tab" data-bs-target="#pane-x">` — Client's
 *     tabs. Buttons have no `href` at all, so matching only `href` (as this
 *     used to) always missed these.
 *
 * The hash may also name a specific field *inside* a pane rather than the
 * pane itself — e.g. `client/edit/{id}#postaladdress_field`
 * (App\Invoice\Inv\Trait\Peppol::friendlyPeppolExceptionMessage(), linking
 * straight to the Client Postal Address section from a Peppol validation
 * error). The browser's own scroll-to-fragment happens before this runs and
 * silently fails when that element starts out hidden inside a non-active
 * pane, so once the right tab is shown, that element is scrolled into view
 * explicitly rather than relying on the browser to retry.
 */
export function initTabHashRestore(): void {
    const hash = globalThis.location.hash;
    if (hash === '' || hash === '#') return;

    const bs = globalThis.bootstrap;
    if (!bs?.Tab) return;

    const triggers = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]'));
    const triggerTargeting = (paneHash: string): Element | undefined =>
        // Compared in JS rather than built into a `[href="..."]` selector
        // string — the hash is address-bar-editable input, and this avoids
        // any selector-injection concern entirely rather than trying to
        // escape it correctly.
        triggers.find(
            element => element.getAttribute('href') === paneHash
                || (element instanceof HTMLElement && element.dataset.bsTarget === paneHash),
        );

    let trigger = triggerTargeting(hash);
    let scrollTarget: Element | null = null;

    if (!trigger) {
        // Hash may name a field nested inside a pane rather than the pane
        // itself: find that element, then the trigger targeting its
        // nearest `.tab-pane` ancestor.
        const target = document.getElementById(hash.slice(1));
        const pane = target?.closest('.tab-pane[id]');
        if (!pane) return;
        trigger = triggerTargeting(`#${pane.id}`);
        if (!trigger) return;
        scrollTarget = target;
    }

    try {
        bs.Tab.getOrCreateInstance(trigger).show();
        scrollTarget?.scrollIntoView();
    } catch (error) {
        console.warn('Tab restore from hash failed:', error);
    }
}
