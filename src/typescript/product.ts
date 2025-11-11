import { parsedata, getJson, ApiResponse, RequestParams } from './utils.js';

// Product-specific interfaces
interface ProductSearchData extends RequestParams {
    product_sku: string;
}

interface ProductSearchResponse extends ApiResponse {
    success: 0 | 1;
    message?: string;
}

interface Product {
    id: string;
    product_name: string;
    product_description: string;
    product_price: string;
    tax_rate_id: string;
    unit_id: string;
}

interface ProductSelectionResponse {
    [key: string]: Product;
}

interface Task {
    id: string;
    name: string;
    description: string;
    price: string;
    tax_rate_id: string;
}

interface TaskSelectionResponse {
    [key: string]: Task;
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
        this.initializeComponents();
    }

    private bindEventListeners(): void {
        document.addEventListener('click', this.handleClick.bind(this), true);
    }

    private handleClick(event: Event): void {
        const target = event.target as HTMLElement;

        if (target.closest('#product_filters_submit')) {
            this.submitProductFilters(event);
            return;
        }

        if (target.closest('.select-items-confirm-quote')) {
            event.preventDefault();
            this.handleQuoteConfirm();
            return;
        }

        if (target.closest('.select-items-confirm-inv')) {
            event.preventDefault();
            this.handleInvoiceConfirm();
            return;
        }

        // Handle product row clicks (for checkbox toggling)
        const productRow = target.closest('.product');
        if (productRow && target.tagName !== 'INPUT') {
            const checkbox = productRow.querySelector('input[type="checkbox"]') as HTMLInputElement;
            if (checkbox) {
                checkbox.click();
            }
            return;
        }

        // Handle checkbox state changes
        if (target.matches("input[name='product_ids[]']")) {
            this.updateButtonStates();
        }
    }

    private initializeComponents(): void {
        // Initialize TomSelect (replaces jQuery select2)
        if (typeof (window as any).TomSelect !== 'undefined') {
            document.querySelectorAll('.simple-select').forEach((el: Element) => {
                if (!(el as any)._tomselect) {
                    new (window as any).TomSelect(el, {});
                    (el as any)._tomselect = true;
                }
            });
        }
        this.updateButtonStates();
    }

    private updateButtonStates(): void {
        const checkedInputs = document.querySelectorAll("input[name='product_ids[]']:checked");
        const hasChecked = checkedInputs.length > 0;
        
        const quoteBtn = document.querySelector('.select-items-confirm-quote') as HTMLButtonElement;
        const invBtn = document.querySelector('.select-items-confirm-inv') as HTMLButtonElement;
        
        if (quoteBtn) {
            quoteBtn.disabled = !hasChecked;
        }
        if (invBtn) {
            invBtn.disabled = !hasChecked;
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

    private async handleQuoteConfirm(): Promise<void> {
        const absoluteUrl = new URL(window.location.href);
        const btn = document.querySelector('.select-items-confirm-quote') as HTMLElement;
        this.setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-spin fa-spinner');
        
        const productIds: number[] = [];
        const quoteId = (absoluteUrl.pathname.split('/').at(-1) || '').replace(/[^0-9]/g, '');
        
        document.querySelectorAll("input[name='product_ids[]']:checked").forEach((input: Element) => {
            const value = parseInt((input as HTMLInputElement).value);
            if (!isNaN(value)) {
                productIds.push(value);
            }
        });

        // ES2024: Sort product IDs without mutation for consistent URL ordering
        const sortedProductIds = productIds.toSorted((a, b) => a - b);
        console.log('Processing products in sorted order:', sortedProductIds);

        let url = `/invoice/product/selection_quote?quote_id=${quoteId}`;
        sortedProductIds.forEach(id => {
            url += `&product_ids[]=${id}`;
        });
        
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json() as ProductSelectionResponse;
            this.processProducts(data);
            this.setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-check');
            window.location.reload();
        } catch (error) {
            console.error('Error:', error);
            this.setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-times');
        }
    }

    private async handleInvoiceConfirm(): Promise<void> {
        const absoluteUrl = new URL(window.location.href);
        const btn = document.querySelector('.select-items-confirm-inv') as HTMLElement;
        this.setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-spin fa-spinner');
        
        const productIds: number[] = [];
        const invId = absoluteUrl.pathname.split('/').at(-1) || '';
        
        document.querySelectorAll("input[name='product_ids[]']:checked").forEach((input: Element) => {
            const value = parseInt((input as HTMLInputElement).value);
            if (!isNaN(value)) {
                productIds.push(value);
            }
        });

        // ES2024: Sort product IDs for consistent processing order
        const sortedProductIds = productIds.toSorted((a, b) => a - b);

        let url = `/invoice/product/selection_inv?inv_id=${invId}`;
        sortedProductIds.forEach(id => {
            url += `&product_ids[]=${id}`;
        });
        
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json() as ProductSelectionResponse;
            this.processProducts(data);
            this.setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-check');
            window.location.reload();
        } catch (error) {
            console.error('Error:', error);
            this.setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-times');
        }
    }

    private processProducts(products: ProductSelectionResponse): void {
        console.log('Processing', Object.keys(products).length, 'products');
        
        // ES2024: Group products by tax rate for analytics
        const productsByTaxRate = Object.groupBy(
            Object.entries(products).map(([key, product]) => ({ key, ...product })),
            (product) => product.tax_rate_id || 'default'
        );
        console.log('Products grouped by tax rate:', Object.keys(productsByTaxRate));
        
        // Process each product - PHP backend handles row creation
        for (const key in products) {
            console.log('Processing product key:', key);
            // Sanitize remote data before use
            const product = products[key];
            if (!product || typeof product !== 'object') continue;
            
            const currentTaxRateId = product.tax_rate_id;
            let productDefaultTaxRateId: string;
            
            if (!currentTaxRateId) {
                const defaultTaxRateEl = document.getElementById('default_item_tax_rate') as HTMLInputElement;
                productDefaultTaxRateId = defaultTaxRateEl ? defaultTaxRateEl.getAttribute('value') || '' : '';
            } else {
                productDefaultTaxRateId = currentTaxRateId;
            }
            
            // Get the last tbody element (matches original JavaScript exactly)
            const lastItemRow = document.querySelector('#item_table tbody:last-of-type') as HTMLElement;
            
            if (lastItemRow) {
                const itemName = lastItemRow.querySelector('input[name=item_name]') as HTMLInputElement;
                if (itemName) itemName.value = product.product_name;
                
                const itemDesc = lastItemRow.querySelector('textarea[name=item_description]') as HTMLTextAreaElement;
                if (itemDesc) itemDesc.value = product.product_description;
                
                const itemPrice = lastItemRow.querySelector('input[name=item_price]') as HTMLInputElement;
                if (itemPrice) itemPrice.value = product.product_price;
                
                const itemQty = lastItemRow.querySelector('input[name=item_quantity]') as HTMLInputElement;
                if (itemQty) itemQty.value = '1';
                
                const itemTaxRate = lastItemRow.querySelector('select[name=item_tax_rate_id]') as HTMLSelectElement;
                if (itemTaxRate) itemTaxRate.value = productDefaultTaxRateId;
                
                const itemProductId = lastItemRow.querySelector('input[name=item_product_id]') as HTMLInputElement;
                if (itemProductId) itemProductId.value = product.id;
                
                const itemUnitId = lastItemRow.querySelector('select[name=item_product_unit_id]') as HTMLSelectElement;
                if (itemUnitId) itemUnitId.value = product.unit_id;
            }
        }
    }

    private setSecureButtonContent(button: HTMLElement, tagName: string, className: string, iconClass: string): void {
        if (!button) return;
        
        // Clear existing content safely
        while (button.firstChild) {
            button.removeChild(button.firstChild);
        }
        
        // Create elements securely
        const element = document.createElement(tagName);
        if (className) element.className = className;
        
        const icon = document.createElement('i');
        if (iconClass) icon.className = iconClass;
        
        element.appendChild(icon);
        button.appendChild(element);
    }

    /**
     * Expose global functions for compatibility with existing code
     */
    private exposeGlobalFunctions(): void {
        // Export tableFunction to global scope in case other scripts call it
        (window as any).productTableFilter = this.filterTableBySku.bind(this);
    }
}
