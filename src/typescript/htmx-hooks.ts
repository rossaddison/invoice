/**
 * Delegated replacements for htmx `hx-on::after-request` attributes, which
 * htmx implements internally via `new Function(...)` — an eval-equivalent
 * that requires CSP script-src 'unsafe-eval'. Delegating from document lets
 * script-src drop 'unsafe-eval' entirely; also disables htmx's eval path
 * (`allowEval`) as defense in depth now that nothing in the app relies on it.
 */

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

        // Was: hx-on::after-request="if(event.detail.successful) this.reset()"
        if (target.matches('[data-hx-reset-on-success]') && e.detail.successful) {
            (target as HTMLFormElement).reset();
        }
    }) as EventListener);
}

function pageSizeRefresh(btn: HTMLElement): void {
    document.querySelectorAll('#page-size-btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    fetch(globalThis.location.href)
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('main-area');
            const current = document.getElementById('main-area');
            if (fresh && current) {
                current.replaceWith(fresh);
                globalThis.htmx?.process(fresh);
            }
        })
        .catch(err => console.error('Page size refresh failed:', err));
}
