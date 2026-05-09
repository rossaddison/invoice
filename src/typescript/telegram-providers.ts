// Moves the Telegram providers modal out of the tab-pane subtree to <body>
// so Bootstrap can render it without being clipped by overflow:hidden ancestors.
export function initTelegramProviderPopup(): void {
    const modal = document.getElementById('telegram-providers');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
}
