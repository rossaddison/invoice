/**
 * Delegated click handling for small, page-agnostic behaviors that used to be
 * inline onclick="..." attributes (this.showPicker(), window.close(),
 * window.print(), toggleCommalistPicker(), confirm-before-submit). Delegating
 * from document means script-src no longer needs 'unsafe-inline' for these.
 */
export function initDataActions(): void {
    document.addEventListener('click', (e: MouseEvent) => {
        const target = e.target as HTMLElement | null;
        if (!target) return;

        const actionEl = target.closest<HTMLElement>('[data-action]');
        if (actionEl) {
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
                default:
                    break;
            }
        }

        const confirmEl = target.closest<HTMLElement>('[data-confirm]');
        if (confirmEl && !globalThis.confirm(confirmEl.dataset['confirm'] ?? '')) {
            e.preventDefault();
        }
    });
}
