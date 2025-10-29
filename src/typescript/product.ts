import { parsedata, getJson, ApiResponse, RequestParams } from './utils.js';

// Product-specific interfaces
interface ProductSearchData extends RequestParams {
    product_sku: string;
}

interface ProductSearchResponse extends ApiResponse {
    success: 0 | 1;
    message?: string;
}

// Helper to set button loading state
function setButtonLoading(buttons: NodeListOf<HTMLElement>, isLoading: boolean): void {
    buttons.forEach(button => {
        if (isLoading) {
            button.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
        } else {
            button.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
        }
    });
}

function setButtonError(buttons: NodeListOf<HTMLElement>): void {
    buttons.forEach(button => {
        button.innerHTML = '<h6 class="text-center"><i class="fa fa-error"></i></h6>';
    });
}

// Product handler class
export class ProductHandler {
    constructor() {
        this.bindEventListeners();
        this.exposeGlobalFunctions();
    }

    private bindEventListeners(): void {
        document.addEventListener('click', this.handleClick.bind(this), true);
    }

    private handleClick(event: Event): void {
        const target = event.target as HTMLElement;
        const trigger = target.closest('#product_filters_submit');

        if (trigger) {
            this.submitProductFilters(event);
        }
    }

    /**
     * Filter table rows by SKU (mirrors original tableFunction)
     */
    public filterTableBySku(): void {
        const inputEl = document.getElementById('filter_product_sku') as HTMLInputElement;
        if (!inputEl) return;

        const input = inputEl.value || '';
        const filter = input.toUpperCase();
        const table = document.getElementById('table-product') as HTMLTableElement;

        if (!table) return;

        const rows = table.getElementsByTagName('tr');

        // Loop through all table rows, and hide those who don't match the search query
        for (let i = 0; i < rows.length; i++) {
            // product_sku is 3rd column or index 2
            const cell = rows[i].getElementsByTagName('td')[2] as HTMLTableCellElement;

            if (cell) {
                const textValue = cell.textContent || cell.innerText || '';

                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    (rows[i] as HTMLTableRowElement).style.display = '';
                } else {
                    (rows[i] as HTMLTableRowElement).style.display = 'none';
                }
            }
        }
    }

    /**
     * Perform the product search request and update UI
     */
    private async submitProductFilters(event: Event): Promise<void> {
        if (event?.preventDefault) {
            event.preventDefault();
        }

        const url = `${location.origin}/invoice/product/search`;
        const buttons = document.querySelectorAll(
            '.product_filters_submit'
        ) as NodeListOf<HTMLElement>;

        // Show spinner on all matching buttons
        setButtonLoading(buttons, true);

        try {
            const productSkuInput = document.getElementById(
                'filter_product_sku'
            ) as HTMLInputElement;
            const productSku = productSkuInput?.value || '';

            const payload: ProductSearchData = {
                product_sku: productSku,
            };

            const response = await getJson<ProductSearchResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                this.filterTableBySku();
                this.hideSummaryBar();
                setButtonLoading(buttons, false);
            } else {
                setButtonError(buttons);
                if (data.message) {
                    alert(data.message);
                }
            }
        } catch (error) {
            console.error('product search failed', error);
            setButtonError(buttons);
            alert('An error occurred while searching products. See console for details.');
        }
    }

    /**
     * Hide the summary bar after filtering
     */
    private hideSummaryBar(): void {
        const summary = document.querySelector('.mt-3.me-3.summary.text-end') as HTMLElement;
        if (summary) {
            summary.style.visibility = 'hidden';
        }
    }

    /**
     * Expose global functions for compatibility with existing code
     */
    private exposeGlobalFunctions(): void {
        // Export tableFunction to global scope in case other scripts call it
        (window as any).productTableFilter = this.filterTableBySku.bind(this);
    }
}
