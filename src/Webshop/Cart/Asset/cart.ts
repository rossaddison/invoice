/**
 * Progressive enhancement for resources/views/shop/cart/index.php's
 * quantity-update and remove <form>s. Every form still works as a plain
 * POST if this script never loads or fetch() fails — this only
 * intercepts the submit, re-sends it as a fetch() with
 * `Accept: application/json`, and patches the DOM from the JSON
 * CartController::jsonCartResponse() returns instead of reloading the
 * page. See CartController::wantsJson() for the server side of this.
 *
 * subtotalFormatted/totalFormatted arrive pre-formatted (currency
 * symbol and conversion already applied server-side via
 * App\Webshop\Currency\CurrencyContext::format()) rather than as bare
 * numbers this script would format itself — one formatting
 * implementation, not two that could drift apart.
 */

interface CartUpdateResponse {
    productId: number;
    quantity: number | null;
    subtotal: number | null;
    subtotalFormatted: string | null;
    removed: boolean;
    // True when the requested quantity exceeded available stock and was
    // reduced to fit — see CartController::jsonCartResponse()'s own
    // docblock. The quantity input already gets corrected to the real
    // (possibly clamped) value below regardless; this only adds a visible
    // reason so that correction doesn't look like the site just ignored
    // what was typed.
    clamped: boolean;
    total: number;
    totalFormatted: string;
    count: number;
}

class CartInteractivity {
    private readonly root: HTMLElement;
    private readonly csrfToken: string;
    private readonly csrfHeader: string;

    constructor(root: HTMLElement) {
        this.root = root;
        this.csrfToken = root.dataset.csrfToken ?? '';
        this.csrfHeader = root.dataset.csrfHeader ?? 'X-CSRF-Token';

        // submit bubbles, so one listener on the wrapper covers every
        // row's update/remove form without binding each one individually.
        root.addEventListener('submit', (event) => this.handleSubmit(event));
    }

    private handleSubmit(event: SubmitEvent): void {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('.js-cart-update-form, .js-cart-remove-form')) {
            return;
        }

        event.preventDefault();
        void this.submit(form);
    }

    private async submit(form: HTMLFormElement): Promise<void> {
        const headers: Record<string, string> = { Accept: 'application/json' };
        if (this.csrfToken) {
            headers[this.csrfHeader] = this.csrfToken;
        }

        let response: Response;
        try {
            response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body: new FormData(form),
            });
        } catch {
            // Offline/network hiccup — fall back to the real page load
            // the form would have done without this script.
            form.submit();
            return;
        }

        if (!response.ok) {
            form.submit();
            return;
        }

        this.applyUpdate((await response.json()) as CartUpdateResponse);
    }

    private applyUpdate(data: CartUpdateResponse): void {
        const row = this.root.querySelector<HTMLTableRowElement>(`tr[data-product-id="${data.productId}"]`);

        if (data.removed) {
            row?.remove();
        } else if (row !== null && data.subtotalFormatted !== null && data.quantity !== null) {
            const subtotalCell = row.querySelector<HTMLElement>('.js-cart-subtotal');
            if (subtotalCell) {
                subtotalCell.textContent = data.subtotalFormatted;
            }
            const qtyInput = row.querySelector<HTMLInputElement>('.js-cart-qty');
            if (qtyInput) {
                qtyInput.value = String(data.quantity);
                qtyInput.title = data.clamped
                    ? 'Reduced to the quantity actually available in stock.'
                    : '';
                qtyInput.classList.toggle('is-invalid', data.clamped);
            }
        }

        const totalCell = document.querySelector<HTMLElement>('.js-cart-total');
        if (totalCell) {
            totalCell.textContent = data.totalFormatted;
        }

        const badge = document.getElementById('cart-count-badge');
        if (badge) {
            badge.textContent = String(data.count);
        }

        // The empty-cart view (resources/views/shop/cart/index.php's `if
        // ($items === [])` branch) is server-rendered markup this script
        // doesn't duplicate — a reload is the simplest correct way to
        // land on it once the last item is gone.
        if (data.count === 0) {
            window.location.reload();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('cart-table-wrapper');
    if (root) {
        new CartInteractivity(root);
    }
});
