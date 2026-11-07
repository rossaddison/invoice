/**
 * Delegated replacements for htmx `hx-on::after-request` attributes, which
 * htmx implements internally via `new Function(...)` — an eval-equivalent
 * that requires CSP script-src 'unsafe-eval'. Delegating from document lets
 * script-src drop 'unsafe-eval' entirely; also disables htmx's eval path
 * (`allowEval`) as defense in depth now that nothing in the app relies on it.
 */

import { initMultiDatePicker } from './multi-date-picker.js';
import { initClientAddresses } from './client-addresses.js';
import { initTooltips } from './scripts.js';
import { collapseAllocationSidesOnPhones } from './alloc-collapse.js';
import { initAllocationRequestQueue } from './alloc-queue.js';

interface HtmxAfterRequestDetail {
    successful: boolean;
}

export function initHtmxHooks(): void {
    if (globalThis.htmx) {
        globalThis.htmx.config.allowEval = false;
    }

    document.body.addEventListener('htmx:afterRequest', ((e: CustomEvent<HtmxAfterRequestDetail>) => {
        const target = e.target as HTMLElement | null;
        if (!target) return;

        // Was: hx-on::after-request="iPageSizeRefresh(this);" on each page-size link
        if (target.closest('#page-size-btn-group')) {
            pageSizeRefresh(target);
        }

        // The lead's "Today's crew" form (inv/guest): saved in the background, then the grid is
        // refreshed in place -- every row's chips change, so one cell's swap is not enough.
        if (target.matches('[data-hx-refresh-main]') && e.detail.successful) {
            refreshMainArea();
        }

        // Was: hx-on::after-request="if(event.detail.successful) this.reset()"
        if (target.matches('[data-hx-reset-on-success]') && e.detail.successful) {
            (target as HTMLFormElement).reset();
        }

        // grid-sticky-header-toggle / navbar-sticky-toggle (invoice.php's
        // gear-dropdown checkboxes): unlike every other hx-swap="none"
        // toggle in this dropdown (e.g. the page-size buttons above,
        // handled via pageSizeRefresh()), the checkbox's own native
        // "checked" flip is not itself visible feedback here -- the
        // actual effect (position: sticky on the navbar / grid header) is
        // baked into this page's already-rendered HTML and only changes
        // on the *next* full render. Confirmed live: toggling either
        // checkbox visibly checks/unchecks it but the sticky behaviour
        // itself never appears until a manual refresh. A full reload,
        // not a #main-area partial swap, is needed specifically for the
        // navbar checkbox -- the affected <nav> lives in <header>, above
        // and outside #main-area, so a partial swap could never reach it
        // even if grid-sticky-header's own effect (inside #main-area)
        // technically could; using the same full-reload fix for both
        // keeps their behaviour identical rather than one refreshing
        // fully and the other only partially.
        if (
            (target.id === 'grid-sticky-header-toggle' || target.id === 'navbar-sticky-toggle') &&
            e.detail.successful
        ) {
            globalThis.location.reload();
        }
    }) as EventListener);

    initAllocationCellHooks();
}

/**
 * inv/guest allocation cells (id "alloc-<inv id>"): each form posts with htmx and the server's reply
 * replaces just that cell, so the lead's taps never reload the page.
 */
function initAllocationCellHooks(): void {
    const isCell = (el: unknown): el is HTMLElement => el instanceof HTMLElement && el.id.startsWith('alloc-');

    // taps on one cell go to the server one at a time, in the order tapped
    initAllocationRequestQueue();

    // a tooltip open on the tapped button would be orphaned when its button is replaced
    document.body.addEventListener('htmx:beforeSwap', ((e: CustomEvent<{ target?: unknown }>) => {
        const cell = e.detail.target;
        if (!isCell(cell)) return;
        const bs = (globalThis as any).bootstrap;
        cell.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => bs?.Tooltip?.getInstance(el)?.dispose());
        cell.querySelectorAll('.dropdown-toggle').forEach(el => bs?.Dropdown?.getInstance(el)?.dispose());
    }) as EventListener);

    // the new cell's tooltips
    document.body.addEventListener('htmx:afterSwap', ((e: CustomEvent<{ target?: unknown }>) => {
        const cell = e.detail.target;
        if (!isCell(cell)) return;
        initTooltips();
        // only the swapped cell: a house the lead expanded elsewhere must stay open
        collapseAllocationSidesOnPhones(document.getElementById(cell.id) ?? document);
    }) as EventListener);

    // a failed post (expired session / CSRF, server or network error) must not leave a stale cell
    // looking right: reload so the real state, and any message, show
    const reloadOnFailure = ((e: CustomEvent<{ target?: unknown }>) => {
        if (isCell(e.detail.target)) globalThis.location.reload();
    }) as EventListener;
    document.body.addEventListener('htmx:responseError', reloadOnFailure);
    document.body.addEventListener('htmx:sendError', reloadOnFailure);
}

function pageSizeRefresh(btn: HTMLElement): void {
    document.querySelectorAll('#page-size-btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    refreshMainArea();
}

/**
 * Re-fetch this page and swap only #main-area (not htmx.ajax with `select`, which does not
 * apply it reliably).
 */
function refreshMainArea(): void {
    fetch(globalThis.location.href)
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('main-area');
            const current = document.getElementById('main-area');
            if (fresh && current) {
                current.replaceWith(fresh);
                globalThis.htmx?.process(fresh);
                initMultiDatePicker(); // the copy modal's calendar lives in the swapped #main-area
                initClientAddresses();
                initTooltips();
                collapseAllocationSidesOnPhones(fresh);
            }
        })
        .catch(err => console.error('Grid refresh failed:', err));
}
