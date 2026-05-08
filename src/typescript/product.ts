import { parsedata, getJson, ApiResponse, RequestParams } from './utils.js';

declare global {
    interface Window {
        TomSelect: new (element: Element, options: Record<string, unknown>) => { destroy(): void };
        productTableFilter: () => void;
    }
}

type ButtonState = 'loading' | 'success' | 'error';

const BUTTON_ICONS: Record<ButtonState, string> = {
    loading: 'fa fa-spin fa-spinner',
    success: 'fa fa-check',
    error: 'fa fa-times',
};

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

// Button state helper — avoids innerHTML for SonarQube S5728 compliance
function setButtonState(buttons: NodeListOf<HTMLElement>, state: ButtonState): void {
    buttons.forEach(button => {
        button.textContent = '';
        const h6 = document.createElement('h6');
        h6.className = 'text-center';
        const i = document.createElement('i');
        i.className = BUTTON_ICONS[state];
        h6.appendChild(i);
        button.appendChild(h6);
    });
}

// Product handler class
export class ProductHandler {
    constructor() {
        this.bindEventListeners();
        this.exposeGlobalFunctions();
        this.initializeComponents();
        this.bindModalEvents();
    }

    private bindModalEvents(): void {
        // Listen for Bootstrap modal shown event to initialize components
        document.addEventListener('shown.bs.modal', (event) => {
            const target = event.target as HTMLElement;
            if (target && target.id === 'modal-choose-items') {
                this.updateButtonStates();
            }
        });
    }

    private bindEventListeners(): void {
        document.addEventListener('click', this.handleClick.bind(this), true);
        document.addEventListener('change', this.handleChange.bind(this), true);
        document.addEventListener('keydown', this.handleKeydown.bind(this), true);
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

        // Handle filter button clicks
        if (target.id === 'filter-button-inv' || target.closest('#filter-button-inv')) {
            event.preventDefault();
            this.filterProducts('inv');
            return;
        }

        if (target.id === 'filter-button-quote' || target.closest('#filter-button-quote')) {
            event.preventDefault();
            this.filterProducts('quote');
            return;
        }

        // Handle reset button clicks
        if (target.id === 'product-reset-button-inv' || target.closest('#product-reset-button-inv')) {
            event.preventDefault();
            this.resetProducts('inv');
            return;
        }

        if (target.id === 'product-reset-button-quote' || target.closest('#product-reset-button-quote')) {
            event.preventDefault();
            this.resetProducts('quote');
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

    private handleChange(event: Event): void {
        const target = event.target as HTMLElement;

        // Handle family dropdown changes
        if (target.id === 'filter_family_inv') {
            this.filterProducts('inv');
        }
        if (target.id === 'filter_family_quote') {
            this.filterProducts('quote');
        }
    }

    private handleKeydown(event: KeyboardEvent): void {
        if (event.key === 'Enter') {
            const target = event.target as HTMLElement;
            
            if (target.id === 'filter_product_inv') {
                event.preventDefault();
                this.filterProducts('inv');
            }
            if (target.id === 'filter_product_quote') {
                event.preventDefault();
                this.filterProducts('quote');
            }
        }
    }

    private initializeComponents(): void {
        // Initialize TomSelect (replaces jQuery select2)
        if (typeof window.TomSelect !== 'undefined') {
            document.querySelectorAll('.simple-select').forEach((el: Element) => {
                const tracked = el as Element & { _tomselect?: boolean };
                if (!tracked._tomselect) {
                    new window.TomSelect(el, {});
                    tracked._tomselect = true;
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
            const cell = rows[i].getElementsByTagName('td')[2];

            if (cell) {
                const textValue = cell.textContent || cell.innerText || '';

                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
    }

    /**
     * Perform the product search request and update UI
     */
    private async submitProductFilters(event: Event): Promise<void> {
        event.preventDefault();

        const url = `${window.location.origin}/invoice/product/search`;
        const buttons = document.querySelectorAll(
            '.product_filters_submit'
        ) as NodeListOf<HTMLElement>;

        setButtonState(buttons, 'loading');

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
                setButtonState(buttons, 'success');
            } else {
                setButtonState(buttons, 'error');
                if (data.message) {
                    alert(data.message);
                }
            }
        } catch (error) {
            console.error('product search failed', error);
            setButtonState(buttons, 'error');
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
            const value = parseInt((input as HTMLInputElement).value, 10);
            if (!isNaN(value)) {
                productIds.push(value);
            }
        });

        // ES2024: Sort product IDs without mutation for consistent URL ordering
        const sortedProductIds = productIds.toSorted((a, b) => a - b);
        const urlParams = new URLSearchParams({ quote_id: quoteId });
        sortedProductIds.forEach(id => urlParams.append('product_ids[]', String(id)));
        const url = `/invoice/product/selection_quote?${urlParams.toString()}`;
        
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
            const value = parseInt((input as HTMLInputElement).value, 10);
            if (!isNaN(value)) {
                productIds.push(value);
            }
        });

        // ES2024: Sort product IDs for consistent processing order
        const sortedProductIds = productIds.toSorted((a, b) => a - b);
        const urlParams = new URLSearchParams({ inv_id: invId });
        sortedProductIds.forEach(id => urlParams.append('product_ids[]', String(id)));
        const url = `/invoice/product/selection_inv?${urlParams.toString()}`;
        
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
        // Process each product - PHP backend handles row creation
        for (const [, product] of Object.entries(products)) {
            // Sanitize remote data before use
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
        window.productTableFilter = this.filterTableBySku.bind(this);
    }

    /**
     * Filter products by family and/or product name
     */
    private async filterProducts(type: 'inv' | 'quote'): Promise<void> {
        const familySelect = document.getElementById(`filter_family_${type}`) as HTMLSelectElement;
        const productInput = document.getElementById(`filter_product_${type}`) as HTMLInputElement;
        const productTable = document.getElementById('product-lookup-table');
        
        if (!productTable) return;
        
        const familyId = familySelect ? familySelect.value : '0';
        const productFilter = productInput ? productInput.value.trim() : '';
        
        // Show loading spinner
        this.setLoadingSpinner(productTable);

        // Build URL with query parameters
        const params = new URLSearchParams();
        if (familyId && familyId !== '0') {
            params.append('ff', familyId);
        }
        if (productFilter) {
            params.append('fp', productFilter);
        }
        const queryString = params.toString();
        const url = queryString ? `/invoice/product/lookup?${queryString}` : '/invoice/product/lookup';

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            const html = await response.text();

            // Secure HTML insertion using DOMParser to prevent XSS
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const fragment = document.createDocumentFragment();
            Array.from(doc.body.children).forEach(child => fragment.appendChild(child));
            productTable.textContent = '';
            productTable.appendChild(fragment);

            this.updateButtonStates();
        } catch (error) {
            console.error('Error filtering products:', error);
            this.setTableError(productTable, 'Error loading products');
        }
    }

    /**
     * Reset product filters and reload all products
     */
    private async resetProducts(type: 'inv' | 'quote'): Promise<void> {
        const familySelect = document.getElementById(`filter_family_${type}`) as HTMLSelectElement;
        const productInput = document.getElementById(`filter_product_${type}`) as HTMLInputElement;
        const productTable = document.getElementById('product-lookup-table');
        
        if (!productTable) return;
        
        // Reset form fields
        if (familySelect) familySelect.value = '0';
        if (productInput) productInput.value = '';
        
        // Show loading spinner
        this.setLoadingSpinner(productTable);

        // Load all products with reset parameter
        try {
            const response = await fetch('/invoice/product/lookup?rt=true', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            const html = await response.text();

            // Secure HTML insertion using DOMParser to prevent XSS
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const fragment = document.createDocumentFragment();
            Array.from(doc.body.children).forEach(child => fragment.appendChild(child));
            productTable.textContent = '';
            productTable.appendChild(fragment);
            this.updateButtonStates();
        } catch (error) {
            console.error('Error resetting products:', error);
            this.setTableError(productTable, 'Error loading products');
        }
    }

    private setLoadingSpinner(container: HTMLElement): void {
        container.textContent = '';
        const h2 = document.createElement('h2');
        h2.className = 'text-center';
        const i = document.createElement('i');
        i.className = 'fa fa-spin fa-spinner';
        h2.appendChild(i);
        container.appendChild(h2);
    }

    private setTableError(container: HTMLElement, message: string): void {
        container.textContent = '';
        const p = document.createElement('p');
        p.className = 'text-danger';
        p.textContent = message;
        container.appendChild(p);
    }
}
