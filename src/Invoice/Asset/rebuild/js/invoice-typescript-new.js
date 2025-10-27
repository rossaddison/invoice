// Type definitions for Invoice Application
define("types", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
});
define("utils", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.parsedata = parsedata;
    exports.getJson = getJson;
    exports.closestSafe = closestSafe;
    exports.getElementById = getElementById;
    exports.querySelector = querySelector;
    exports.querySelectorAll = querySelectorAll;
    exports.getInputValue = getInputValue;
    /**
     * Safe JSON parser that always returns an object
     * @param data - Data to parse (can be string, object, or any type)
     * @returns Parsed object or empty object if parsing fails
     */
    function parsedata(data) {
        if (!data)
            return {};
        if (typeof data === 'object' && data !== null)
            return data;
        if (typeof data === 'string') {
            try {
                return JSON.parse(data);
            }
            catch (e) {
                return {};
            }
        }
        return {};
    }
    /**
     * HTTP GET helper that serializes arrays as bracketed keys (key[]=v1&key[]=v2)
     * @param url - Request URL
     * @param params - Parameters to send
     * @param options - Additional fetch options
     * @returns Promise resolving to parsed JSON or text
     */
    async function getJson(url, params, options = {}) {
        let requestUrl = url;
        if (params) {
            const searchParams = new URLSearchParams();
            Object.entries(params).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    // Append as key[] so server parses it as an array (matches jQuery behavior)
                    value.forEach((item) => {
                        if (item !== null && item !== undefined) {
                            searchParams.append(`${key}[]`, String(item));
                        }
                    });
                }
                else if (value !== undefined && value !== null) {
                    searchParams.append(key, String(value));
                }
            });
            const separator = url.includes('?') ? '&' : '?';
            requestUrl = `${url}${separator}${searchParams.toString()}`;
        }
        const defaultOptions = {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' },
            ...options
        };
        const response = await fetch(requestUrl, defaultOptions);
        if (!response.ok) {
            throw new Error(`Network response not ok: ${response.status}`);
        }
        const text = await response.text();
        try {
            return JSON.parse(text);
        }
        catch (e) {
            return text;
        }
    }
    /**
     * Safe closest element finder with fallback for older browsers
     * @param element - Starting element
     * @param selector - CSS selector to match
     * @returns Matching ancestor element or null
     */
    function closestSafe(element, selector) {
        try {
            if (!element)
                return null;
            if (typeof element.closest === 'function') {
                return element.closest(selector);
            }
            // Fallback: walk up parents manually
            let node = element;
            while (node) {
                if (node.matches && node.matches(selector)) {
                    return node;
                }
                node = node.parentElement;
            }
        }
        catch (e) {
            console.warn('closestSafe error:', e);
            return null;
        }
        return null;
    }
    /**
     * Safe DOM element getter with type safety
     * @param id - Element ID
     * @returns Element or null
     */
    function getElementById(id) {
        return document.getElementById(id);
    }
    /**
     * Safe DOM element selector with type safety
     * @param selector - CSS selector
     * @returns Element or null
     */
    function querySelector(selector) {
        return document.querySelector(selector);
    }
    /**
     * Safe DOM elements selector with type safety
     * @param selector - CSS selector
     * @returns NodeList of elements
     */
    function querySelectorAll(selector) {
        return document.querySelectorAll(selector);
    }
    /**
     * Get form field value safely
     * @param id - Element ID
     * @returns Value or empty string
     */
    function getInputValue(id) {
        const element = getElementById(id);
        return element?.value || '';
    }
});
define("create-credit", ["require", "exports", "utils"], function (require, exports, utils_js_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.CreateCreditHandler = void 0;
    /**
     * Handle create credit confirmation
     * Converts an invoice to a credit note
     */
    class CreateCreditHandler {
        constructor() {
            this.confirmButtonSelector = '.create-credit-confirm';
            this.initialize();
        }
        initialize() {
            document.addEventListener('click', this.handleClick.bind(this), true);
        }
        async handleClick(event) {
            const target = event.target;
            if (!target || target.id !== 'create-credit-confirm') {
                return;
            }
            event.preventDefault();
            try {
                await this.processCreateCredit();
            }
            catch (error) {
                console.error('Create credit error:', error);
                alert(`Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
            }
        }
        async processCreateCredit() {
            const url = `${location.origin}/invoice/inv/create_credit_confirm`;
            const btn = (0, utils_js_1.querySelector)(this.confirmButtonSelector);
            const absoluteUrl = new URL(location.href);
            // Show loading spinner
            if (btn) {
                btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            }
            // Extract invoice ID from URL
            const invId = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
            // Collect form data with type safety
            const formData = {
                inv_id: invId,
                client_id: (0, utils_js_1.getInputValue)('client_id'),
                inv_date_created: (0, utils_js_1.getInputValue)('inv_date_created'),
                group_id: (0, utils_js_1.getInputValue)('inv_group_id'),
                password: (0, utils_js_1.getInputValue)('inv_password'),
                user_id: (0, utils_js_1.getInputValue)('user_id')
            };
            // Make API request
            const data = await (0, utils_js_1.getJson)(url, formData);
            const response = (0, utils_js_1.parsedata)(data);
            if (response.success === 1) {
                // Success
                if (btn) {
                    btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check2-square"></i></h2>';
                }
                if (response.flash_message) {
                    alert(response.flash_message);
                }
                // Redirect and reload
                location.href = absoluteUrl.href;
                location.reload();
            }
            else {
                // Failure
                if (btn) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                }
                if (response.flash_message) {
                    alert(response.flash_message);
                }
                // Redirect and reload
                location.href = absoluteUrl.href;
                location.reload();
            }
        }
    }
    exports.CreateCreditHandler = CreateCreditHandler;
});
define("quote", ["require", "exports", "utils"], function (require, exports, utils_js_2) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.QuoteHandler = void 0;
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
    class QuoteHandler {
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
                const response = await (0, utils_js_2.getJson)(url, { id });
                const data = (0, utils_js_2.parsedata)(response);
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
                const response = await (0, utils_js_2.getJson)('/invoice/quoteitem/multiple', { item_ids });
                const data = (0, utils_js_2.parsedata)(response);
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
                const response = await (0, utils_js_2.getJson)(url, payload);
                const data = (0, utils_js_2.parsedata)(response);
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
                const response = await (0, utils_js_2.getJson)(url, payload);
                const data = (0, utils_js_2.parsedata)(response);
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
                const response = await (0, utils_js_2.getJson)(url, payload);
                const data = (0, utils_js_2.parsedata)(response);
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
                const response = await (0, utils_js_2.getJson)(url, payload);
                const data = (0, utils_js_2.parsedata)(response);
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
                const response = await (0, utils_js_2.getJson)(url, payload);
                const data = (0, utils_js_2.parsedata)(response);
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
                const response = await (0, utils_js_2.getJson)(url, payload);
                const data = (0, utils_js_2.parsedata)(response);
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
                const response = await (0, utils_js_2.getJson)(url, payload);
                const data = (0, utils_js_2.parsedata)(response);
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
    exports.QuoteHandler = QuoteHandler;
});
define("client", ["require", "exports", "utils"], function (require, exports, utils_js_3) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.ClientHandler = void 0;
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
    // Client handler class
    class ClientHandler {
        constructor() {
            this.bindEventListeners();
        }
        bindEventListeners() {
            document.addEventListener('click', this.handleClick.bind(this), true);
        }
        handleClick(event) {
            const target = event.target;
            // Client create confirm
            const createBtn = target.closest('#client_create_confirm');
            if (createBtn) {
                this.handleClientCreateConfirm(createBtn);
                return;
            }
            // Save client note
            const saveNoteBtn = target.closest('#save_client_note_new');
            if (saveNoteBtn) {
                this.handleSaveClientNote(saveNoteBtn);
                return;
            }
        }
        async handleClientCreateConfirm(createBtn) {
            const url = `${location.origin}/invoice/client/create_confirm`;
            const btn = document.querySelector('.client_create_confirm') || createBtn;
            const currentUrl = new URL(location.href);
            // Set loading state
            if (btn) {
                setButtonLoading(btn, true);
            }
            try {
                const payload = {
                    client_name: getFieldValue('client_name'),
                    client_surname: getFieldValue('client_surname'),
                    client_email: getFieldValue('client_email')
                };
                const response = await (0, utils_js_3.getJson)(url, payload);
                const data = (0, utils_js_3.parsedata)(response);
                if (data.success === 1) {
                    if (btn) {
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    }
                    // Navigate and reload as per original behavior
                    window.location.href = currentUrl.href;
                    window.location.reload();
                }
                else {
                    if (btn) {
                        setButtonLoading(btn, false);
                    }
                    console.warn('create_confirm response', data);
                }
            }
            catch (error) {
                console.warn(error);
                if (btn) {
                    setButtonLoading(btn, false);
                }
                alert('An error occurred while creating client. See console for details.');
            }
        }
        async handleSaveClientNote(saveNoteBtn) {
            const url = `${location.origin}/invoice/client/save_client_note_new`;
            const loadNotesUrl = `${location.origin}/invoice/client/load_client_notes`;
            const btn = document.querySelector('.save_client_note') || saveNoteBtn;
            const currentUrl = new URL(location.href);
            if (btn) {
                setButtonLoading(btn, true);
            }
            try {
                const payload = {
                    client_id: getFieldValue('client_id'),
                    client_note: getFieldValue('client_note')
                };
                const response = await (0, utils_js_3.getJson)(url, payload);
                const data = (0, utils_js_3.parsedata)(response);
                if (data.success === 1) {
                    if (btn) {
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    }
                    // Clear the client note field
                    const noteEl = document.getElementById('client_note');
                    if (noteEl)
                        noteEl.value = '';
                    // Reload notes list (replacing jQuery .load behavior)
                    const notesList = document.getElementById('notes_list');
                    if (notesList) {
                        const loadUrl = `${loadNotesUrl}?client_id=${encodeURIComponent(payload.client_id)}`;
                        try {
                            const notesResponse = await fetch(loadUrl, {
                                cache: 'no-store',
                                credentials: 'same-origin'
                            });
                            const html = await notesResponse.text();
                            notesList.innerHTML = html;
                            console.log(html);
                        }
                        catch (loadError) {
                            console.error('load_client_notes failed', loadError);
                        }
                    }
                    // Navigate and reload as per original behavior
                    window.location.href = currentUrl.href;
                    window.location.reload();
                }
                else {
                    // Handle validation errors
                    this.clearValidationErrors();
                    if (data.validation_errors) {
                        this.showValidationErrors(data.validation_errors);
                    }
                    if (btn) {
                        setButtonLoading(btn, false);
                    }
                }
            }
            catch (error) {
                console.warn(error);
                if (btn) {
                    setButtonLoading(btn, false);
                }
                alert('An error occurred while saving client note. See console for details.');
            }
        }
        clearValidationErrors() {
            document.querySelectorAll('.control-group').forEach(group => {
                group.classList.remove('error');
            });
        }
        showValidationErrors(validationErrors) {
            Object.keys(validationErrors).forEach(key => {
                const element = document.getElementById(key);
                if (element?.parentElement) {
                    element.parentElement.classList.add('has-error');
                }
            });
        }
    }
    exports.ClientHandler = ClientHandler;
});
define("invoice", ["require", "exports", "utils"], function (require, exports, utils_js_4) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.InvoiceHandler = void 0;
    // Helper to set button loading state
    function setButtonLoading(button, isLoading, originalHtml) {
        if (isLoading) {
            button.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            button.disabled = true;
        }
        else {
            button.innerHTML = originalHtml || '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
            button.disabled = false;
        }
    }
    // Helper to get form field value safely
    function getFieldValue(id) {
        const element = document.getElementById(id);
        return element?.value || '';
    }
    // Invoice handler class
    class InvoiceHandler {
        constructor() {
            this.bindEventListeners();
        }
        bindEventListeners() {
            document.addEventListener('click', this.handleClick.bind(this), true);
        }
        handleClick(event) {
            const target = event.target;
            // Mark as sent
            const markAsSent = (0, utils_js_4.closestSafe)(target, '#btn-mark-as-sent');
            if (markAsSent) {
                this.handleMarkAsSent();
                return;
            }
            // Mark sent as draft
            const markDraft = (0, utils_js_4.closestSafe)(target, '#btn-mark-sent-as-draft');
            if (markDraft) {
                this.handleMarkSentAsDraft();
                return;
            }
            // Create recurring invoice
            const createRecurring = (0, utils_js_4.closestSafe)(target, '.create_recurring_confirm_multiple');
            if (createRecurring) {
                this.handleCreateRecurringMultiple(createRecurring);
                return;
            }
        }
        getCheckedInvoiceIds() {
            const selected = [];
            const table = document.getElementById('table-invoice');
            if (!table)
                return selected;
            const checkboxes = table.querySelectorAll('input[type="checkbox"]:checked');
            checkboxes.forEach(checkbox => {
                if (checkbox.id) {
                    selected.push(checkbox.id);
                }
            });
            return selected;
        }
        async handleMarkAsSent() {
            const btn = document.getElementById('btn-mark-as-sent');
            const originalHtml = btn?.innerHTML;
            if (btn) {
                setButtonLoading(btn, true);
            }
            try {
                const selected = this.getCheckedInvoiceIds();
                const url = `${location.origin}/invoice/inv/mark_as_sent`;
                const response = await (0, utils_js_4.getJson)(url, { keylist: selected });
                const data = (0, utils_js_4.parsedata)(response);
                if (data.success === 1) {
                    if (btn)
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    window.location.reload();
                }
                else {
                    if (btn)
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    window.location.reload();
                }
            }
            catch (error) {
                console.error('mark_as_sent error', error);
                if (btn && originalHtml) {
                    setButtonLoading(btn, false, originalHtml);
                }
                alert('An error occurred. See console for details.');
            }
        }
        async handleMarkSentAsDraft() {
            const btn = document.getElementById('btn-mark-sent-as-draft');
            const originalHtml = btn?.innerHTML;
            if (btn) {
                setButtonLoading(btn, true);
            }
            try {
                const selected = this.getCheckedInvoiceIds();
                const url = `${location.origin}/invoice/inv/mark_sent_as_draft`;
                const response = await (0, utils_js_4.getJson)(url, { keylist: selected });
                const data = (0, utils_js_4.parsedata)(response);
                if (data.success === 1) {
                    if (btn)
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    window.location.reload();
                }
                else {
                    if (btn)
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    window.location.reload();
                }
            }
            catch (error) {
                console.error('mark_sent_as_draft error', error);
                if (btn && originalHtml) {
                    setButtonLoading(btn, false, originalHtml);
                }
                alert('An error occurred. See console for details.');
            }
        }
        async handleCreateRecurringMultiple(createRecurring) {
            const btn = document.querySelector('.create_recurring_confirm_multiple') || createRecurring;
            const originalHtml = btn?.innerHTML;
            if (btn) {
                btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                btn.disabled = true;
            }
            try {
                // Get selected invoice checkboxes
                const selected = this.getCheckedInvoiceIds();
                if (selected.length === 0) {
                    alert('Please select invoices to create recurring invoices.');
                    if (btn && originalHtml) {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                    return;
                }
                const payload = {
                    keylist: selected,
                    recur_frequency: getFieldValue('recur_frequency'),
                    recur_start_date: getFieldValue('recur_start_date'),
                    recur_end_date: getFieldValue('recur_end_date')
                };
                // Validate required fields
                if (!payload.recur_frequency || !payload.recur_start_date) {
                    alert('Please select frequency and start date.');
                    if (btn && originalHtml) {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                    return;
                }
                const url = `${location.origin}/invoice/invrecurring/multiple`;
                const response = await (0, utils_js_4.getJson)(url, payload);
                const data = (0, utils_js_4.parsedata)(response);
                if (data.success === 1) {
                    if (btn)
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    // Close modal if using Bootstrap
                    this.closeModal('create-recurring-multiple');
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
                else {
                    if (btn)
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    alert('Failed to create recurring invoices. Please try again.');
                    if (btn && originalHtml) {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                }
            }
            catch (error) {
                console.error('invrecurring/multiple error', error);
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
                alert('An error occurred while creating recurring invoices. See console for details.');
            }
        }
        closeModal(modalId) {
            try {
                if (typeof window.bootstrap?.Modal !== 'undefined') {
                    const modalEl = document.getElementById(modalId);
                    if (modalEl) {
                        const modalInstance = window.bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                }
            }
            catch (e) {
                console.warn('Failed to close modal:', e);
            }
        }
    }
    exports.InvoiceHandler = InvoiceHandler;
});
define("index", ["require", "exports", "create-credit", "quote", "client", "invoice"], function (require, exports, create_credit_js_1, quote_js_1, client_js_1, invoice_js_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.InvoiceApp = void 0;
    /**
     * Initialize Invoice Application
     */
    class InvoiceApp {
        constructor() {
            // Initialize handlers (stored as properties to keep event listeners active)
            this._createCreditHandler = new create_credit_js_1.CreateCreditHandler();
            this._quoteHandler = new quote_js_1.QuoteHandler();
            this._clientHandler = new client_js_1.ClientHandler();
            this._invoiceHandler = new invoice_js_1.InvoiceHandler();
            this.initializeTooltips();
            this.initializeTaggableFocus();
            console.log('Invoice TypeScript App initialized with Quote, Client, and Invoice handlers');
        }
        /**
         * Initialize Bootstrap tooltips
         */
        initializeTooltips() {
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltipElements.forEach((element) => {
                        try {
                            new bootstrap.Tooltip(element);
                        }
                        catch (error) {
                            console.warn('Tooltip initialization failed:', error);
                        }
                    });
                }
            });
        }
        /**
         * Keep track of last taggable focused element
         */
        initializeTaggableFocus() {
            document.addEventListener('focus', (event) => {
                const target = event.target;
                if (target?.classList?.contains('taggable')) {
                    window.lastTaggableClicked = target;
                }
            }, true);
        }
    }
    exports.InvoiceApp = InvoiceApp;
    // Initialize the application when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new InvoiceApp());
    }
    else {
        new InvoiceApp();
    }
});
