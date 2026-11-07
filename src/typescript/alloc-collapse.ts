/**
 * inv/guest and inv/index allocation cells fold a house's still-unallocated sides into one
 * <details class="alloc-collapsible"> (rendered open, so it works with no script and is fully
 * open on a desktop). On a phone-width screen this closes it so each such house takes one line
 * until the lead taps it. Run on load and again after an htmx swap of a cell, which re-renders it
 * open.
 */

/** Bootstrap's md breakpoint: below it the screen is a phone. */
const PHONE = '(max-width: 767.98px)';

export function collapseAllocationSidesOnPhones(root: ParentNode = document): void {
    if (!globalThis.matchMedia?.(PHONE).matches) return;
    root.querySelectorAll<HTMLDetailsElement>('details.alloc-collapsible[open]').forEach(d => {
        d.open = false;
    });
}