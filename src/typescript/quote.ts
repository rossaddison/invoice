import { parsedata, getJson, ApiResponse, RequestParams } from './utils.js';

// Quote-specific interfaces
interface QuoteFormData extends RequestParams {
    client_id?: string;
    quote_group_id?: string;
    quote_password?: string;
    url_key?: string;
    client_po_number?: string;
    client_po_person?: string;
    quote_id?: string;
    group_id?: string;
    password?: string;
    so_group_id?: string;
    po_number?: string;
    po_person?: string;
    create_quote_client_id?: string;
    user_id?: string;
    tax_rate_id?: string;
    include_item_tax?: string;
}

interface ClientNoteData extends RequestParams {
    client_id: string;
    client_note: string;
}

// Helper to get URL parameter from current page URL
function getQuoteIdFromUrl(): string {
    const url = new URL(location.href);
    return url.href.substring(url.href.lastIndexOf('/') + 1);
}

// Helper to get form field value safely
function getFieldValue(id: string): string {
    const element = document.getElementById(id) as HTMLInputElement | HTMLSelectElement | null;
    return element?.value || '';
}

// Helper to set button loading state
function setButtonLoading(button: HTMLElement, isLoading: boolean, originalHtml?: string): void {
    if (isLoading) {
        button.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
        (button as HTMLButtonElement).disabled = true;
    } else {
        button.innerHTML =
            originalHtml || '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
        (button as HTMLButtonElement).disabled = false;
    }
}

// Quote handler class
export class QuoteHandler {
    constructor() {
        this.bindEventListeners();
        this.initializeComponents();
    }

    private bindEventListeners(): void {
        // Main click handler with delegation
        document.addEventListener('click', this.handleClick.bind(this), true);

        // Input handlers
        document.addEventListener('input', this.handleInput.bind(this), true);

        // Focus handlers
        document.addEventListener('focus', this.handleFocus.bind(this), true);

        // Client note save handler
        document.addEventListener('click', this.handleClientNoteSave.bind(this), true);

        // Quote tax submit handler
        document.addEventListener('click', this.handleQuoteTaxSubmit.bind(this), true);
    }

    private handleClick(event: Event): void {
        const target = event.target as HTMLElement;

        // Delete single item
        const deleteBtn = target.closest('.btn_delete_item') as HTMLElement;
        if (deleteBtn) {
            this.handleDeleteItem(deleteBtn);
            return;
        }

        // Delete multiple items
        const delMulti = target.closest('.delete-items-confirm-quote') as HTMLElement;
        if (delMulti) {
            this.handleDeleteMultipleItems(delMulti);
            return;
        }

        // Add row via modal
        const addRowModalBtn = target.closest('.btn_add_row_modal') as HTMLElement;
        if (addRowModalBtn) {
            this.handleAddRowModal();
            return;
        }

        // Add new quote item row
        const btnQuoteItemAddRow = target.closest('.btn_quote_item_add_row') as HTMLElement;
        if (btnQuoteItemAddRow) {
            this.handleAddQuoteItemRow();
            return;
        }

        // Add generic new row
        const addRowBtn = target.closest('.btn_add_row') as HTMLElement;
        if (addRowBtn) {
            this.handleAddGenericRow();
            return;
        }

        // Add client modal
        const addClientBtn = target.closest('.quote_add_client') as HTMLElement;
        if (addClientBtn) {
            this.handleAddClientModal();
            return;
        }

        // Quote create confirm
        const createConfirm = target.closest(
            '#quote_create_confirm, .quote_create_confirm'
        ) as HTMLElement;
        if (createConfirm) {
            this.handleQuoteCreateConfirm();
            return;
        }

        // Quote with purchase order confirm
        const poConfirm = target.closest(
            '#quote_with_purchase_order_number_confirm, .quote_with_purchase_order_number_confirm'
        ) as HTMLElement;
        if (poConfirm) {
            this.handleQuotePurchaseOrderConfirm(poConfirm);
            return;
        }

        // Quote to invoice confirm
        const toInvoice = target.closest(
            '#quote_to_invoice_confirm, .quote_to_invoice_confirm'
        ) as HTMLElement;
        if (toInvoice) {
            this.handleQuoteToInvoiceConfirm(toInvoice);
            return;
        }

        // Quote to sales order confirm
        const toSo = target.closest('#quote_to_so_confirm, .quote_to_so_confirm') as HTMLElement;
        if (toSo) {
            this.handleQuoteToSalesOrderConfirm(toSo);
            return;
        }

        // Quote to quote confirm (copy)
        const toQuote = target.closest(
            '#quote_to_quote_confirm, .quote_to_quote_confirm'
        ) as HTMLElement;
        if (toQuote) {
            this.handleQuoteToQuoteConfirm(toQuote);
            return;
        }

        // PDF generation handlers
        this.handlePdfGeneration(target);
    }

    private async handleDeleteItem(deleteBtn: HTMLElement): Promise<void> {
        const id = deleteBtn.getAttribute('data-id');

        if (!id) {
            // Remove from DOM if no ID
            const parentItem = deleteBtn.closest('.item') as HTMLElement;
            parentItem?.remove();
            return;
        }

        try {
            const url = `${location.origin}/invoice/quote/delete_item/${encodeURIComponent(id)}`;
            const response = await getJson<ApiResponse>(url, { id });
            const data = parsedata(response);

            if (data.success === 1) {
                location.reload();
                const parentItem = deleteBtn.closest('.item') as HTMLElement;
                parentItem?.remove();
                alert('Deleted');
            } else {
                console.warn('delete_item failed', data);
            }
        } catch (error) {
            console.error('delete_item error', error);
            alert('An error occurred while deleting item. See console for details.');
        }
    }

    private async handleDeleteMultipleItems(delMulti: HTMLElement): Promise<void> {
        const originalHtml = delMulti.innerHTML;
        setButtonLoading(delMulti, true);

        try {
            const itemCheckboxes = document.querySelectorAll(
                "input[name='item_ids[]']:checked"
            ) as NodeListOf<HTMLInputElement>;
            const item_ids = Array.from(itemCheckboxes)
                .map(input => parseInt(input.value, 10))
                .filter(Boolean);

            const response = await getJson<ApiResponse>('/invoice/quoteitem/multiple', {
                item_ids,
            });
            const data = parsedata(response);

            if (data.success === 1) {
                delMulti.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                location.reload();
            } else {
                console.warn('quoteitem/multiple failed', data);
                setButtonLoading(delMulti, false, originalHtml);
            }
        } catch (error) {
            console.error('quoteitem/multiple error', error);
            setButtonLoading(delMulti, false, originalHtml);
            alert('An error occurred while deleting items. See console for details.');
        }
    }

    private async handleAddRowModal(): Promise<void> {
        const quoteId = getQuoteIdFromUrl();
        const url = `${location.origin}/invoice/quoteitem/add/${encodeURIComponent(quoteId)}`;
        const placeholder = document.getElementById('modal-placeholder-quoteitem');

        if (!placeholder) return;

        try {
            placeholder.innerHTML =
                '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
            const html = await response.text();
            placeholder.innerHTML = html;
        } catch (error) {
            console.error('Failed to load quoteitem modal', error);
        }
    }

    private handleAddQuoteItemRow(): void {
        const template = document.getElementById('new_quote_item_row') as HTMLElement;
        const table = document.getElementById('item_table') as HTMLElement;

        if (template && table) {
            const clone = template.cloneNode(true) as HTMLElement;
            clone.removeAttribute('id');
            clone.classList.add('item');
            clone.style.display = '';
            table.appendChild(clone);
        }
    }

    private handleAddGenericRow(): void {
        const template = document.getElementById('new_row') as HTMLElement;
        const table = document.getElementById('item_table') as HTMLElement;

        if (template && table) {
            const clone = template.cloneNode(true) as HTMLElement;
            clone.removeAttribute('id');
            clone.classList.add('item');
            clone.style.display = '';
            table.appendChild(clone);
        }
    }

    private async handleAddClientModal(): Promise<void> {
        const url = `${location.origin}/invoice/add-a-client`;
        const placeholder = document.getElementById('modal-placeholder-client');

        if (!placeholder) return;

        try {
            placeholder.innerHTML =
                '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
            const html = await response.text();
            placeholder.innerHTML = html;
        } catch (error) {
            console.error('Failed to load add-a-client modal', error);
        }
    }

    private async handleQuoteCreateConfirm(): Promise<void> {
        const url = `${location.origin}/invoice/quote/create_confirm`;
        const btn = document.querySelector('.quote_create_confirm') as HTMLElement;
        const originalHtml = btn?.innerHTML || '';

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const payload: QuoteFormData = {
                client_id: getFieldValue('create_quote_client_id'),
                quote_group_id: getFieldValue('quote_group_id'),
                quote_password: getFieldValue('quote_password'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.href = currentUrl.href;
                window.location.reload();
            } else if (data.success === 0) {
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
                window.location.href = currentUrl.href;
                window.location.reload();
                if (data.message) alert(data.message);
            }
        } catch (error) {
            console.error('create_confirm error', error);
            if (btn) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred while creating quote. See console for details.');
        }
    }

    private async handleQuotePurchaseOrderConfirm(poConfirm: HTMLElement): Promise<void> {
        const url = `${location.origin}/invoice/quote/approve`;
        const btn =
            (document.querySelector('.quote_with_purchase_order_number_confirm') as HTMLElement) ||
            poConfirm;

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const payload: QuoteFormData = {
                url_key: getFieldValue('url_key'),
                client_po_number: getFieldValue('quote_with_purchase_order_number'),
                client_po_person: getFieldValue('quote_with_purchase_order_person'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.href = currentUrl.href;
                window.location.reload();
            }
        } catch (error) {
            console.error('approve error', error);
            if (btn) {
                setButtonLoading(btn, false);
            }
            alert('An error occurred while approving quote. See console for details.');
        }
    }

    private async handleQuoteToInvoiceConfirm(toInvoice: HTMLElement): Promise<void> {
        const url = `${location.origin}/invoice/quote/quote_to_invoice_confirm`;
        const btn =
            (document.querySelector('.quote_to_invoice_confirm') as HTMLElement) || toInvoice;
        const originalHtml = btn?.innerHTML || '';

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const quoteId = getQuoteIdFromUrl();
            const payload: QuoteFormData = {
                quote_id: quoteId,
                client_id: getFieldValue('client_id'),
                group_id: getFieldValue('group_id'),
                password: getFieldValue('password'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
            
            // Redirect to the created invoice if successful
            if (data.success && data.new_invoice_id) {
                window.location.href = `${location.origin}/invoice/inv/view/${data.new_invoice_id}`;
            } else {
                // Fallback to reload if no invoice ID is provided
                const currentUrl = new URL(location.href);
                window.location.href = currentUrl.href;
                window.location.reload();
            }
            
            if (data.flash_message) alert(data.flash_message);
        } catch (error) {
            console.error('quote_to_invoice_confirm error', error);
            if (btn) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred while converting quote to invoice. See console for details.');
        }
    }

    private async handleQuoteToSalesOrderConfirm(toSo: HTMLElement): Promise<void> {
        const url = `${location.origin}/invoice/quote/quote_to_so_confirm`;
        const btn = (document.querySelector('.quote_to_so_confirm') as HTMLElement) || toSo;
        const originalHtml = btn?.innerHTML || '';

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const quoteId = getQuoteIdFromUrl();
            const payload: QuoteFormData = {
                quote_id: quoteId,
                client_id: getFieldValue('client_id'),
                group_id: getFieldValue('so_group_id'),
                po_number: getFieldValue('po_number'),
                po_person: getFieldValue('po_person'),
                password: getFieldValue('password'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);

            if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
            window.location.href = currentUrl.href;
            window.location.reload();
            if (data.flash_message) alert(data.flash_message);
        } catch (error) {
            console.error('quote_to_so_confirm error', error);
            if (btn) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred while converting quote to SO. See console for details.');
        }
    }

    private async handleQuoteToQuoteConfirm(toQuote: HTMLElement): Promise<void> {
        const url = `${location.origin}/invoice/quote/quote_to_quote_confirm`;
        const btn = (document.querySelector('.quote_to_quote_confirm') as HTMLElement) || toQuote;
        const originalHtml = btn?.innerHTML || '';

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const quoteId = getQuoteIdFromUrl();
            const payload: QuoteFormData = {
                quote_id: quoteId,
                client_id: getFieldValue('create_quote_client_id'),
                user_id: getFieldValue('user_id'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.href = currentUrl.href;
                window.location.reload();
                if (data.flash_message) alert(data.flash_message);
            }
        } catch (error) {
            console.error('quote_to_quote_confirm error', error);
            if (btn) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred while copying quote. See console for details.');
        }
    }

    private handlePdfGeneration(target: HTMLElement): void {
        // PDF with custom fields
        if (target.closest('#quote_to_pdf_confirm_with_custom_fields')) {
            const url = `${location.origin}/invoice/quote/pdf/1`;
            window.open(url, '_blank');
            return;
        }

        // PDF without custom fields
        if (target.closest('#quote_to_pdf_confirm_without_custom_fields')) {
            const url = `${location.origin}/invoice/quote/pdf/0`;
            window.open(url, '_blank');
            return;
        }
    }

    private async handleClientNoteSave(event: Event): Promise<void> {
        const target = event.target as HTMLElement;
        const saveBtn = target.closest('#save_client_note');
        if (!saveBtn) return;

        const url = `${location.origin}/invoice/client/save_client_note`;
        const loadUrl = `${location.origin}/invoice/client/load_client_notes`;

        try {
            const payload: ClientNoteData = {
                client_id: getFieldValue('client_id'),
                client_note: getFieldValue('client_note'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                // Remove error classes
                document.querySelectorAll('.control-group').forEach(group => {
                    group.classList.remove('error');
                });

                // Clear note field
                const noteEl = document.getElementById('client_note') as HTMLInputElement;
                if (noteEl) noteEl.value = '';

                // Reload notes list
                const notesList = document.getElementById('notes_list');
                if (notesList) {
                    const loadUrlWithParams = `${loadUrl}?client_id=${encodeURIComponent(payload.client_id)}`;
                    const notesResponse = await fetch(loadUrlWithParams, {
                        cache: 'no-store',
                        credentials: 'same-origin',
                    });
                    const html = await notesResponse.text();
                    notesList.innerHTML = html;
                }
            } else {
                // Show validation errors
                document.querySelectorAll('.control-group').forEach(group => {
                    group.classList.remove('error');
                });

                if (data.validation_errors) {
                    Object.keys(data.validation_errors).forEach(key => {
                        const elm = document.getElementById(key);
                        if (elm?.parentElement) {
                            elm.parentElement.classList.add('has-error');
                        }
                    });
                }
            }
        } catch (error) {
            console.error('save_client_note error', error);
            alert('Status: error An error occurred');
        }
    }

    private async handleQuoteTaxSubmit(event: Event): Promise<void> {
        const target = event.target as HTMLElement;
        const submit = target.closest('#quote_tax_submit');
        if (!submit) return;

        const url = `${location.origin}/invoice/quote/save_quote_tax_rate`;
        const btn =
            (document.querySelector('.quote_tax_submit') as HTMLElement) || (submit as HTMLElement);

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const quoteId = getQuoteIdFromUrl();
            const payload: QuoteFormData = {
                quote_id: quoteId,
                tax_rate_id: getFieldValue('tax_rate_id'),
                include_item_tax: getFieldValue('include_item_tax'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);

            window.location.href = currentUrl.href;
            window.location.reload();
            if (data.flash_message) alert(data.flash_message);
        } catch (error) {
            console.error('save_quote_tax_rate error', error);
            alert('An error occurred while saving quote tax rate. See console for details.');
        }
    }

    private handleInput(event: Event): void {
        const target = event.target as HTMLInputElement;

        // Discount amount input
        if (target.id === 'quote_discount_amount') {
            const percentEl = document.getElementById('quote_discount_percent') as HTMLInputElement;
            if (target.value.length > 0) {
                if (percentEl) {
                    percentEl.value = '0.00';
                    percentEl.disabled = true;
                }
            } else {
                if (percentEl) percentEl.disabled = false;
            }
        }

        // Discount percent input
        if (target.id === 'quote_discount_percent') {
            const amountEl = document.getElementById('quote_discount_amount') as HTMLInputElement;
            if (target.value.length > 0) {
                if (amountEl) {
                    amountEl.value = '0.00';
                    amountEl.disabled = true;
                }
            } else {
                if (amountEl) amountEl.disabled = false;
            }
        }
    }

    private handleFocus(event: Event): void {
        const target = event.target as HTMLElement;

        // Datepicker initialization
        if (target.id === 'datepicker') {
            this.initializeDatepicker(target);
        }

        if (target.classList?.contains('datepicker')) {
            this.initializeDatepicker(target);
        }

        // Taggable focus tracking
        if (target.classList?.contains('taggable')) {
            (window as any).lastTaggableClicked = target;
        }
    }

    private initializeDatepicker(element: HTMLElement): void {
        if ((window as any).jQuery?.fn?.datepicker) {
            if (element.id === 'datepicker') {
                (window as any).jQuery(element).datepicker({
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    dateFormat: 'dd-mm-yy',
                });
            } else {
                (window as any).jQuery(element).datepicker({
                    beforeShow: () => {
                        setTimeout(() => {
                            document.querySelectorAll('.datepicker').forEach(d => {
                                (d as HTMLElement).style.zIndex = '9999';
                            });
                        }, 0);
                    },
                });
            }
        }
    }

    private initializeComponents(): void {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeTooltips();
            this.initializeTagSelect();
        });
    }

    private initializeTooltips(): void {
        if (typeof (window as any).bootstrap?.Tooltip !== 'undefined') {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
                try {
                    new (window as any).bootstrap.Tooltip(element);
                } catch (error) {
                    // Ignore tooltip initialization errors
                }
            });
        }
    }

    private initializeTagSelect(): void {
        document.querySelectorAll('.tag-select').forEach(select => {
            const selectElement = select as HTMLSelectElement;
            selectElement.addEventListener('change', event => {
                const currentTarget = event.currentTarget as HTMLSelectElement;

                if ((window as any).lastTaggableClicked) {
                    this.insertAtCaret((window as any).lastTaggableClicked.id, currentTarget.value);
                }

                // Reset select value
                if ((currentTarget as any)._tomselect?.clear) {
                    (currentTarget as any)._tomselect.clear();
                } else if ((currentTarget as any).tomselect?.clear) {
                    (currentTarget as any).tomselect.clear();
                } else if (currentTarget.multiple) {
                    Array.from(currentTarget.options).forEach(option => {
                        option.selected = false;
                    });
                } else {
                    currentTarget.value = '';
                }

                event.preventDefault();
                return false;
            });
        });
    }

    private insertAtCaret(elementId: string, text: string): void {
        const element = document.getElementById(elementId) as
            | HTMLInputElement
            | HTMLTextAreaElement;
        if (!element) return;

        const startPos = element.selectionStart || 0;
        const endPos = element.selectionEnd || 0;
        const value = element.value;

        element.value = value.substring(0, startPos) + text + value.substring(endPos);
        element.setSelectionRange(startPos + text.length, startPos + text.length);
        element.focus();
    }
}
