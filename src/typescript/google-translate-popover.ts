/**
 * google-translate-popover.ts
 *
 * Initialises Bootstrap popovers on elements marked with [data-popover-steps].
 * The data-bs-content attribute may contain step-by-step instructions formatted
 * as "---Step--N: description" lines separated by \r\n or \n.
 * These are converted into a styled <ol> so each step is clearly aligned.
 */

function formatStepContent(raw: string): string {
    const lines = raw.split(/\r?\n/);
    let html = '';
    let inList = false;

    for (const line of lines) {
        const trimmed = line.trim();
        if (!trimmed) continue;

        const stepMatch = /^---Step--(\d+):\s*(.*)/.exec(trimmed);
        if (stepMatch) {
            if (!inList) {
                html += '<ol class="mb-1 ps-3 small">';
                inList = true;
            }
            html += `<li><strong>Step ${stepMatch[1]}:</strong> ${stepMatch[2]}</li>`;
        } else {
            if (inList) {
                html += '</ol>';
                inList = false;
            }
            html += `<p class="mb-1 small">${trimmed}</p>`;
        }
    }

    if (inList) {
        html += '</ol>';
    }

    return html;
}

export function initStepPopovers(): void {
    const bs = (globalThis as any).bootstrap;
    if (!bs?.Popover) return;

    document.querySelectorAll<HTMLElement>('[data-popover-steps]').forEach(el => {
        const rawContent = el.dataset.bsContent ?? '';
        // Remove the data attribute so Bootstrap doesn't try to use it as-is
        delete el.dataset.bsContent;
        // Remove data-bs-trigger so the JS option below is the sole source of truth
        delete el.dataset.bsTrigger;

        try {
            bs.Popover.getOrCreateInstance(el, {
                html: true,
                content: formatStepContent(rawContent),
                trigger: 'hover focus',
                placement: el.dataset.bsPlacement ?? 'right',
                customClass: 'popover-steps',
                // Append to body so the popover is never clipped by the
                // dropdown-menu's overflow context.
                container: 'body',
                delay: { show: 150, hide: 300 },
            });
        } catch (e) {
            console.warn('Step popover init failed:', e);
        }
    });
}
