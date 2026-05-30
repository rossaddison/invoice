export class PageSizeHandler {
    constructor() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.#init());
        } else {
            this.#init();
        }
    }

    #init(): void {
        const select = document.getElementById('page-size-select') as HTMLSelectElement | null;
        if (!select) return;
        select.addEventListener('change', () => void this.#onChange(select));
    }

    async #onChange(select: HTMLSelectElement): Promise<void> {
        const urlTemplate = select.dataset.listlimitUrl ?? '';
        const value = select.value;
        if (!urlTemplate || !value) return;
        const url = urlTemplate.replace('__SIZE__', value);
        select.disabled = true;
        try {
            const response = await fetch(url);
            if (response.ok) {
                globalThis.location.reload();
            } else {
                console.error('Page size save failed: HTTP', response.status, response.url);
                select.disabled = false;
            }
        } catch (err) {
            console.error('Page size update failed:', err);
            select.disabled = false;
        }
    }
}
