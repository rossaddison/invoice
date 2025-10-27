import { parsedata, getJson } from './utils.js';
// Helper to get URL parameter from current page URL
function getQuoteIdFromUrl() {
    const url = new URL(location.href);
    return url.href.substring(url.href.lastIndexOf('/') + 1);
}
// Helper to get form field value safely
function getFieldValue(id) {
    const element = document.getElementById(id);
    return element?.value || '';
}
// Helper to set button loading state
function setButtonLoading(button, isLoading, originalHtml) {
    if (isLoading) {
        button.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
        button.disabled = true;
    }
    else {
        button.innerHTML = originalHtml || '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
        button.disabled = false;
    }
}
// Quote handler class
export class QuoteHandler {
    constructor() {
        this.bindEventListeners();
        this.initializeComponents();
    }
    bindEventListeners() {
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
    handleClick(event) {
        const target = event.target;
        // Delete single item
        const deleteBtn = target.closest('.btn_delete_item');
        if (deleteBtn) {
            this.handleDeleteItem(deleteBtn);
            return;
        }
        // Delete multiple items
        const delMulti = target.closest('.delete-items-confirm-quote');
        if (delMulti) {
            this.handleDeleteMultipleItems(delMulti);
            return;
        }
        // Add row via modal
        const addRowModalBtn = target.closest('.btn_add_row_modal');
        if (addRowModalBtn) {
            this.handleAddRowModal();
            return;
        }
        // Add new quote item row
        const btnQuoteItemAddRow = target.closest('.btn_quote_item_add_row');
        if (btnQuoteItemAddRow) {
            this.handleAddQuoteItemRow();
            return;
        }
        // Add generic new row
        const addRowBtn = target.closest('.btn_add_row');
        if (addRowBtn) {
            this.handleAddGenericRow();
            return;
        }
        // Add client modal
        const addClientBtn = target.closest('.quote_add_client');
        if (addClientBtn) {
            this.handleAddClientModal();
            return;
        }
        // Quote create confirm
        const createConfirm = target.closest('#quote_create_confirm, .quote_create_confirm');
        if (createConfirm) {
            this.handleQuoteCreateConfirm();
            return;
        }
        // Quote with purchase order confirm
        const poConfirm = target.closest('#quote_with_purchase_order_number_confirm, .quote_with_purchase_order_number_confirm');
        if (poConfirm) {
            this.handleQuotePurchaseOrderConfirm(poConfirm);
            return;
        }
        // Quote to invoice confirm
        const toInvoice = target.closest('#quote_to_invoice_confirm, .quote_to_invoice_confirm');
        if (toInvoice) {
            this.handleQuoteToInvoiceConfirm(toInvoice);
            return;
        }
        // Quote to sales order confirm
        const toSo = target.closest('#quote_to_so_confirm, .quote_to_so_confirm');
        if (toSo) {
            this.handleQuoteToSalesOrderConfirm(toSo);
            return;
        }
        // Quote to quote confirm (copy)
        const toQuote = target.closest('#quote_to_quote_confirm, .quote_to_quote_confirm');
        if (toQuote) {
            this.handleQuoteToQuoteConfirm(toQuote);
            return;
        }
        // PDF generation handlers
        this.handlePdfGeneration(target);
    }
    async handleDeleteItem(deleteBtn) {
        const id = deleteBtn.getAttribute('data-id');
        if (!id) {
            // Remove from DOM if no ID
            const parentItem = deleteBtn.closest('.item');
            parentItem?.remove();
            return;
        }
        try {
            const url = `${location.origin}/invoice/quote/delete_item/${encodeURIComponent(id)}`;
            const response = await getJson(url, { id });
            const data = parsedata(response);
            if (data.success === 1) {
                location.reload();
                const parentItem = deleteBtn.closest('.item');
                parentItem?.remove();
                alert("Deleted");
            }
            else {
                console.warn('delete_item failed', data);
            }
        }
        catch (error) {
            console.error('delete_item error', error);
            alert('An error occurred while deleting item. See console for details.');
        }
    }
    async handleDeleteMultipleItems(delMulti) {
        const originalHtml = delMulti.innerHTML;
        setButtonLoading(delMulti, true);
        try {
            const itemCheckboxes = document.querySelectorAll("input[name='item_ids[]']:checked");
            const item_ids = Array.from(itemCheckboxes)
                .map(input => parseInt(input.value, 10))
                .filter(Boolean);
            const response = await getJson('/invoice/quoteitem/multiple', { item_ids });
            const data = parsedata(response);
            if (data.success === 1) {
                delMulti.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                location.reload();
            }
            else {
                console.warn('quoteitem/multiple failed', data);
                setButtonLoading(delMulti, false, originalHtml);
            }
        }
        catch (error) {
            console.error('quoteitem/multiple error', error);
            setButtonLoading(delMulti, false, originalHtml);
            alert('An error occurred while deleting items. See console for details.');
        }
    }
    async handleAddRowModal() {
        const quoteId = getQuoteIdFromUrl();
        const url = `${location.origin}/invoice/quoteitem/add/${encodeURIComponent(quoteId)}`;
        const placeholder = document.getElementById('modal-placeholder-quoteitem');
        if (!placeholder)
            return;
        try {
            placeholder.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
            const html = await response.text();
            placeholder.innerHTML = html;
        }
        catch (error) {
            console.error('Failed to load quoteitem modal', error);
        }
    }
    handleAddQuoteItemRow() {
        const template = document.getElementById('new_quote_item_row');
        const table = document.getElementById('item_table');
        if (template && table) {
            const clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.classList.add('item');
            clone.style.display = '';
            table.appendChild(clone);
        }
    }
    handleAddGenericRow() {
        const template = document.getElementById('new_row');
        const table = document.getElementById('item_table');
        if (template && table) {
            const clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.classList.add('item');
            clone.style.display = '';
            table.appendChild(clone);
        }
    }
    async handleAddClientModal() {
        const url = `${location.origin}/invoice/add-a-client`;
        const placeholder = document.getElementById('modal-placeholder-client');
        if (!placeholder)
            return;
        try {
            placeholder.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
            const html = await response.text();
            placeholder.innerHTML = html;
        }
        catch (error) {
            console.error('Failed to load add-a-client modal', error);
        }
    }
    async handleQuoteCreateConfirm() {
        const url = `${location.origin}/invoice/quote/create_confirm`;
        const btn = document.querySelector('.quote_create_confirm');
        const originalHtml = btn?.innerHTML || '';
        if (btn) {
            setButtonLoading(btn, true);
        }
        try {
            const payload = {
                client_id: getFieldValue('create_quote_client_id'),
                quote_group_id: getFieldValue('quote_group_id'),
                quote_password: getFieldValue('quote_password')
            };
            const response = await getJson(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);
            if (data.success === 1) {
                if (btn)
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.href = currentUrl.href;
                window.location.reload();
            }
            else if (data.success === 0) {
                if (btn)
                    btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
                window.location.href = currentUrl.href;
                window.location.reload();
                if (data.message)
                    alert(data.message);
            }
        }
        catch (error) {
            console.error('create_confirm error', error);
            if (btn) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred while creating quote. See console for details.');
        }
    }
    async handleQuotePurchaseOrderConfirm(poConfirm) {
        const url = `${location.origin}/invoice/quote/approve`;
        const btn = document.querySelector('.quote_with_purchase_order_number_confirm') || poConfirm;
        if (btn) {
            setButtonLoading(btn, true);
        }
        try {
            const payload = {
                url_key: getFieldValue('url_key'),
                client_po_number: getFieldValue('quote_with_purchase_order_number'),
                client_po_person: getFieldValue('quote_with_purchase_order_person')
            };
            const response = await getJson(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);
            if (data.success === 1) {
                if (btn)
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.href = currentUrl.href;
                window.location.reload();
            }
        }
        catch (error) {
            console.error('approve error', error);
            if (btn) {
                setButtonLoading(btn, false);
            }
            alert('An error occurred while approving quote. See console for details.');
        }
    }
    async handleQuoteToInvoiceConfirm(toInvoice) {
        const url = `${location.origin}/invoice/quote/quote_to_invoice_confirm`;
        const btn = document.querySelector('.quote_to_invoice_confirm') || toInvoice;
        const originalHtml = btn?.innerHTML || '';
        if (btn) {
            setButtonLoading(btn, true);
        }
        try {
            const quoteId = getQuoteIdFromUrl();
            const payload = {
                quote_id: quoteId,
                client_id: getFieldValue('client_id'),
                group_id: getFieldValue('group_id'),
                password: getFieldValue('password')
            };
            const response = await getJson(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);
            if (btn)
                btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
            window.location.href = currentUrl.href;
            window.location.reload();
            if (data.flash_message)
                alert(data.flash_message);
        }
        catch (error) {
            console.error('quote_to_invoice_confirm error', error);
            if (btn) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred while converting quote to invoice. See console for details.');
        }
    }
    async handleQuoteToSalesOrderConfirm(toSo) {
        const url = `${location.origin}/invoice/quote/quote_to_so_confirm`;
        const btn = document.querySelector('.quote_to_so_confirm') || toSo;
        const originalHtml = btn?.innerHTML || '';
        if (btn) {
            setButtonLoading(btn, true);
        }
        try {
            const quoteId = getQuoteIdFromUrl();
            const payload = {
                quote_id: quoteId,
                client_id: getFieldValue('client_id'),
                group_id: getFieldValue('so_group_id'),
                po_number: getFieldValue('po_number'),
                po_person: getFieldValue('po_person'),
                password: getFieldValue('password')
            };
            const response = await getJson(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);
            if (btn)
                btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
            window.location.href = currentUrl.href;
            window.location.reload();
            if (data.flash_message)
                alert(data.flash_message);
        }
        catch (error) {
            console.error('quote_to_so_confirm error', error);
            if (btn) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred while converting quote to SO. See console for details.');
        }
    }
    async handleQuoteToQuoteConfirm(toQuote) {
        const url = `${location.origin}/invoice/quote/quote_to_quote_confirm`;
        const btn = document.querySelector('.quote_to_quote_confirm') || toQuote;
        const originalHtml = btn?.innerHTML || '';
        if (btn) {
            setButtonLoading(btn, true);
        }
        try {
            const quoteId = getQuoteIdFromUrl();
            const payload = {
                quote_id: quoteId,
                client_id: getFieldValue('create_quote_client_id'),
                user_id: getFieldValue('user_id')
            };
            const response = await getJson(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);
            if (data.success === 1) {
                if (btn)
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.href = currentUrl.href;
                window.location.reload();
                if (data.flash_message)
                    alert(data.flash_message);
            }
        }
        catch (error) {
            console.error('quote_to_quote_confirm error', error);
            if (btn) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred while copying quote. See console for details.');
        }
    }
    handlePdfGeneration(target) {
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
    async handleClientNoteSave(event) {
        const target = event.target;
        const saveBtn = target.closest('#save_client_note');
        if (!saveBtn)
            return;
        const url = `${location.origin}/invoice/client/save_client_note`;
        const loadUrl = `${location.origin}/invoice/client/load_client_notes`;
        try {
            const payload = {
                client_id: getFieldValue('client_id'),
                client_note: getFieldValue('client_note')
            };
            const response = await getJson(url, payload);
            const data = parsedata(response);
            if (data.success === 1) {
                // Remove error classes
                document.querySelectorAll('.control-group').forEach(group => {
                    group.classList.remove('error');
                });
                // Clear note field
                const noteEl = document.getElementById('client_note');
                if (noteEl)
                    noteEl.value = '';
                // Reload notes list
                const notesList = document.getElementById('notes_list');
                if (notesList) {
                    const loadUrlWithParams = `${loadUrl}?client_id=${encodeURIComponent(payload.client_id)}`;
                    const notesResponse = await fetch(loadUrlWithParams, {
                        cache: 'no-store',
                        credentials: 'same-origin'
                    });
                    const html = await notesResponse.text();
                    notesList.innerHTML = html;
                }
            }
            else {
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
        }
        catch (error) {
            console.error('save_client_note error', error);
            alert('Status: error An error occurred');
        }
    }
    async handleQuoteTaxSubmit(event) {
        const target = event.target;
        const submit = target.closest('#quote_tax_submit');
        if (!submit)
            return;
        const url = `${location.origin}/invoice/quote/save_quote_tax_rate`;
        const btn = document.querySelector('.quote_tax_submit') || submit;
        if (btn) {
            setButtonLoading(btn, true);
        }
        try {
            const quoteId = getQuoteIdFromUrl();
            const payload = {
                quote_id: quoteId,
                tax_rate_id: getFieldValue('tax_rate_id'),
                include_item_tax: getFieldValue('include_item_tax')
            };
            const response = await getJson(url, payload);
            const data = parsedata(response);
            const currentUrl = new URL(location.href);
            window.location.href = currentUrl.href;
            window.location.reload();
            if (data.flash_message)
                alert(data.flash_message);
        }
        catch (error) {
            console.error('save_quote_tax_rate error', error);
            alert('An error occurred while saving quote tax rate. See console for details.');
        }
    }
    handleInput(event) {
        const target = event.target;
        // Discount amount input
        if (target.id === 'quote_discount_amount') {
            const percentEl = document.getElementById('quote_discount_percent');
            if (target.value.length > 0) {
                if (percentEl) {
                    percentEl.value = '0.00';
                    percentEl.disabled = true;
                }
            }
            else {
                if (percentEl)
                    percentEl.disabled = false;
            }
        }
        // Discount percent input
        if (target.id === 'quote_discount_percent') {
            const amountEl = document.getElementById('quote_discount_amount');
            if (target.value.length > 0) {
                if (amountEl) {
                    amountEl.value = '0.00';
                    amountEl.disabled = true;
                }
            }
            else {
                if (amountEl)
                    amountEl.disabled = false;
            }
        }
    }
    handleFocus(event) {
        const target = event.target;
        // Datepicker initialization
        if (target.id === 'datepicker') {
            this.initializeDatepicker(target);
        }
        if (target.classList?.contains('datepicker')) {
            this.initializeDatepicker(target);
        }
        // Taggable focus tracking
        if (target.classList?.contains('taggable')) {
            window.lastTaggableClicked = target;
        }
    }
    initializeDatepicker(element) {
        if (window.jQuery?.fn?.datepicker) {
            if (element.id === 'datepicker') {
                window.jQuery(element).datepicker({
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    dateFormat: 'dd-mm-yy'
                });
            }
            else {
                window.jQuery(element).datepicker({
                    beforeShow: () => {
                        setTimeout(() => {
                            document.querySelectorAll('.datepicker').forEach((d) => {
                                d.style.zIndex = '9999';
                            });
                        }, 0);
                    }
                });
            }
        }
    }
    initializeComponents() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeTooltips();
            this.initializeTagSelect();
        });
    }
    initializeTooltips() {
        if (typeof window.bootstrap?.Tooltip !== 'undefined') {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
                try {
                    new window.bootstrap.Tooltip(element);
                }
                catch (error) {
                    // Ignore tooltip initialization errors
                }
            });
        }
    }
    initializeTagSelect() {
        document.querySelectorAll('.tag-select').forEach((select) => {
            const selectElement = select;
            selectElement.addEventListener('change', (event) => {
                const currentTarget = event.currentTarget;
                if (window.lastTaggableClicked) {
                    this.insertAtCaret(window.lastTaggableClicked.id, currentTarget.value);
                }
                // Reset select value
                if (currentTarget._tomselect?.clear) {
                    currentTarget._tomselect.clear();
                }
                else if (currentTarget.tomselect?.clear) {
                    currentTarget.tomselect.clear();
                }
                else if (currentTarget.multiple) {
                    Array.from(currentTarget.options).forEach(option => {
                        option.selected = false;
                    });
                }
                else {
                    currentTarget.value = '';
                }
                event.preventDefault();
                return false;
            });
        });
    }
    insertAtCaret(elementId, text) {
        const element = document.getElementById(elementId);
        if (!element)
            return;
        const startPos = element.selectionStart || 0;
        const endPos = element.selectionEnd || 0;
        const value = element.value;
        element.value = value.substring(0, startPos) + text + value.substring(endPos);
        element.setSelectionRange(startPos + text.length, startPos + text.length);
        element.focus();
    }
}
