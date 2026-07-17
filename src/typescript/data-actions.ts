/**
 * Delegated click handling for small, page-agnostic behaviors that used to be
 * inline onclick="..." attributes (this.showPicker(), window.close(),
 * window.print(), toggleCommalistPicker(), confirm-before-submit,
 * window.history.back(), toggleAllGroups()), plus two generic reusable
 * primitives: toggle-panel (show/hide an element by selector) and
 * copy-to-clipboard (copy another element's text content). Delegating from
 * document means script-src no longer needs 'unsafe-inline' for these.
 */
function copyToClipboard(actionEl: HTMLElement): void {
    const selector = actionEl.dataset['copyTarget'];
    const source = selector ? document.querySelector<HTMLElement>(selector) : null;
    if (source && navigator.clipboard?.writeText) {
        void navigator.clipboard.writeText(source.textContent ?? '').then(() => {
            const original = actionEl.textContent;
            actionEl.textContent = 'Copied!';
            setTimeout(() => {
                actionEl.textContent = original;
            }, 1500);
        });
    }
}

function handleDataAction(actionEl: HTMLElement): void {
    switch (actionEl.dataset['action']) {
        case 'show-picker':
            (actionEl as HTMLInputElement).showPicker?.();
            break;
        case 'window-close':
            globalThis.close();
            break;
        case 'window-print':
            globalThis.print();
            break;
        case 'toggle-commalist-picker':
            globalThis.toggleCommalistPicker?.();
            break;
        case 'history-back':
            globalThis.history.back();
            break;
        case 'toggle-all-groups': {
            const toggleAll = (globalThis as unknown as Record<string, unknown>)['toggleAllGroups'] as
                | ((expand: boolean) => void)
                | undefined;
            toggleAll?.(actionEl.dataset['expand'] === 'true');
            break;
        }
        case 'toggle-panel': {
            const selector = actionEl.dataset['target'];
            const panel = selector ? document.querySelector<HTMLElement>(selector) : null;
            panel?.classList.toggle('d-none');
            break;
        }
        case 'copy-to-clipboard':
            copyToClipboard(actionEl);
            break;
        default:
            break;
    }
}

export function initDataActions(): void {
    document.addEventListener('click', (e: MouseEvent) => {
        const target = e.target as HTMLElement | null;
        if (!target) return;

        const actionEl = target.closest<HTMLElement>('[data-action]');
        if (actionEl) {
            handleDataAction(actionEl);
        }

        const confirmEl = target.closest<HTMLElement>('[data-confirm]');
        if (confirmEl && !globalThis.confirm(confirmEl.dataset['confirm'] ?? '')) {
            e.preventDefault();
        }
    });

    // Dropdown filters (Yiisoft\Yii\DataView DropdownFilter, class "native-reset")
    // used to auto-submit via an inline onChange="this.form.submit()" attribute
    // that the vendor widget renders directly — CSP script-src 'self' blocks it
    // since it's outside this app's own inline-script sweep. Submit on their
    // behalf via a delegated change listener instead.
    document.addEventListener('change', (e: Event) => {
        const target = e.target as HTMLElement | null;
        if (target instanceof HTMLSelectElement && target.classList.contains('native-reset')) {
            target.form?.submit();
        }
    });
}
