// Initialises Bootstrap 5 Popovers on the m.php main-menu cards.
// Each card carries data-submenu-items (JSON string[]) and data-menu-title.
// Bootstrap 5 bundle is loaded via CDN before this script runs.

declare const bootstrap: {
    Popover: new (
        element: HTMLElement,
        options: {
            trigger?: string;
            placement?: string;
            html?: boolean;
            content?: string;
            title?: string;
            delay?: { show: number; hide: number };
            customClass?: string;
        }
    ) => void;
};

function esc(s: string): string {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

document.addEventListener('DOMContentLoaded', (): void => {
    document.querySelectorAll<HTMLElement>('[data-submenu-items]').forEach((card): void => {
        let items: string[];
        try {
            items = JSON.parse(card.dataset.submenuItems ?? '[]') as string[];
        } catch {
            return;
        }
        if (items.length === 0) return;

        const listHtml = items
            .map((item): string => `<li>${esc(item)}</li>`)
            .join('');

        new bootstrap.Popover(card, {
            trigger:     'hover focus',
            placement:   'top',
            html:        true,
            title:       esc(card.dataset.menuTitle ?? ''),
            content:     `<ul class="mb-0 ps-3">${listHtml}</ul>`,
            delay:       { show: 220, hide: 80 },
            customClass: 'cli-menu-pop',
        });
    });
});
