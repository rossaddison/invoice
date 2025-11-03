/**
 * generate-cron-key.ts
 *
 * Attaches a click handler to #btn_generate_cron_key that generates a secure random cron key,
 * fills the input with id "settings[cron_key]" (and class "cron_key"), attempts to copy it to the
 * clipboard, and provides simple visual feedback on the button while working.
 *
 * Usage:
 * - Compile to JS via your normal build pipeline (tsc, webpack, etc.).
 * - Include the resulting bundle on pages that contain the button and input:
 *     <button id="btn_generate_cron_key" type="button" class="btn_generate_cron_key btn btn-primary btn-block">
 *         <i class="fa fa-recycle fa-margin"></i>
 *     </button>
 *
 * - The input the script updates is expected to exist with id exactly "settings[cron_key]":
 *     <input type="text" name="settings[cron_key]" id="settings[cron_key]" class="cron_key form-control" ...>
 */

function generateSecureHex(bytes = 24): string {
    // Generate `bytes` random bytes using the Web Crypto API and convert to hex
    const arr = new Uint8Array(bytes);
    crypto.getRandomValues(arr);
    // Convert to hex
    return Array.from(arr)
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('');
}

function escapeIdForQuerySelector(id: string): string {
    // For querySelector, characters like [ and ] must be escaped.
    // We can use CSS.escape if available.
    // Fallback to escaping brackets.
    // Note: we only need this if using querySelector with the id that contains brackets.
    // We'll use getElementById where possible (no escaping required).
    if ((window as any).CSS && typeof (window as any).CSS.escape === 'function') {
        return (window as any).CSS.escape(id);
    }
    return id.replace(/([\[\]#;.])/g, '\\$1');
}

function setButtonWorkingState(button: HTMLElement, working: boolean) {
    if (working) {
        // Replace content with spinner
        // Store the original HTML markup on a property, not as an attribute to avoid XSS risk
        (button as any).__originalHTML = button.innerHTML;
        button.setAttribute('aria-busy', 'true');
        button.setAttribute('disabled', 'true');
        button.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';
    } else {
        const original = (button as any).__originalHTML;
        if (original) {
            button.innerHTML = original;
            delete (button as any).__originalHTML;
        }
        button.removeAttribute('aria-busy');
        button.removeAttribute('disabled');
    }
}

async function handleGenerateClick(button: HTMLElement) {
    try {
        setButtonWorkingState(button, true);

        // Generate a 48-character hex string (24 bytes) by default.
        const newKey = generateSecureHex(24);

        // Find the input by id (no need to escape)
        const input = document.getElementById('settings[cron_key]') as HTMLInputElement | null;

        if (input) {
            input.value = newKey;

            // Try to copy to clipboard; don't fail if clipboard is unavailable
            try {
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    await navigator.clipboard.writeText(newKey);
                    // show a success checkmark briefly
                    button.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';
                } else {
                    // fallback: select the input text and attempt execCommand (legacy)
                    input.select();
                    const ok = document.execCommand && document.execCommand('copy');
                    if (ok) {
                        button.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';
                    } else {
                        // keep the spinner for a moment, then restore
                        button.innerHTML = '<i class="fa fa-recycle fa-margin" aria-hidden="true"></i>';
                    }
                }
            } catch (e) {
                // Clipboard copy failed; leave the key in the field and restore button
                console.warn('Copy to clipboard failed', e);
                button.innerHTML = '<i class="fa fa-recycle fa-margin" aria-hidden="true"></i>';
            }
        } else {
            console.warn('Cron key input not found: #settings[cron_key]');
        }
    } finally {
        // restore after a short delay so the user sees feedback
        setTimeout(() => setButtonWorkingState(button, false), 700);
    }
}

export function initGenerateCronKey(): void {
    // Wait for DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGenerateCronKey);
        return;
    }

    const btn = document.getElementById('btn_generate_cron_key') as HTMLElement | null;
    if (!btn) {
        // Button not present on the page; nothing to do.
        return;
    }

    // Prevent double-binding if init is called multiple times
    (btn as any).__generateCronKeyBound = (btn as any).__generateCronKeyBound || false;
    if ((btn as any).__generateCronKeyBound) {
        return;
    }
    (btn as any).__generateCronKeyBound = true;

    btn.addEventListener('click', (e: MouseEvent) => {
        e.preventDefault();
        // Defensive: if button is disabled, do nothing
        if ((btn as HTMLButtonElement).disabled) return;
        void handleGenerateClick(btn);
    });
}

// If this script is included as a simple script tag (not module), auto-init
// @ts-ignore-next-line: allow global check
if (typeof window !== 'undefined' && !(window as any).module) {
    // Delay slightly to allow other scripts to wire up or to ensure DOM is ready
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initGenerateCronKey();
    } else {
        document.addEventListener('DOMContentLoaded', initGenerateCronKey);
    }
}