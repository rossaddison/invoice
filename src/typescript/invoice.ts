import { parsedata, getJson, ApiResponse, RequestParams, closestSafe } from './utils.js';

// Invoice-specific interfaces
interface RecurringInvoiceData extends RequestParams {
    keylist: string[];
    recur_start_date: string;
    recur_end_date?: string;
    recur_frequency: string;
}

interface DeleteInvoiceItemsData extends RequestParams {
    item_ids: string[];
    inv_id: string;
}

interface CopyMultipleInvoicesData extends RequestParams {
    keylist: string[];
    modal_created_date: string;
    client_ids: string[];
}

interface CopyInvSpreadsheetRow {
    date_created: string;
    note: string;
    same_amount: string;
    payment_date: string;
}

interface CopyInvSpreadsheetPayload extends RequestParams {
    keylist: string[];
    client_ids: string[];
    rows_json: string;
}

interface CopySingleInvoiceData extends RequestParams {
    inv_id: string;
    client_ids: string[];
    user_id: string;
}

interface AddInvoiceTaxData extends RequestParams {
    inv_id: string;
    inv_tax_rate_id: string;
    include_inv_item_tax: string;
}

interface PaymentData extends RequestParams {
    amount: string;
    payment_method: string;
    payment_date: string;
}

interface DeleteItemData extends RequestParams {
    id: string;
}

function setButtonLoadingOn(button: HTMLElement): void {
    button.innerHTML = '<h2 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h2>';
    (button as HTMLButtonElement).disabled = true;
}

function setButtonLoadingOff(button: HTMLElement, originalHtml?: string): void {
    button.innerHTML =
        originalHtml || '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
    (button as HTMLButtonElement).disabled = false;
}

// Helper to get form field value safely
function getFieldValue(id: string): string {
    const element = document.getElementById(id) as HTMLInputElement | HTMLSelectElement | null;
    return element?.value || '';
}

// Invoice handler class
export class InvoiceHandler {
    constructor() {
        this.bindEventListeners();
    }

    private bindEventListeners(): void {
        document.addEventListener('click', this.handleClick.bind(this), true);
        document.addEventListener('change', this.handleChange.bind(this), true);

        // Initialize all clients check on page load
        this.initializeAllClientsCheck();

        // Batch email modal: fetch preview when modal opens.
        // Document-level delegation so this fires even if #modal-batch-email is
        // injected after init (e.g. HTMX swap) or replaced during a grid refresh.
        document.addEventListener('show.bs.modal', (event: Event) => {
            if ((event.target as HTMLElement)?.id === 'modal-batch-email') {
                void this.handleBatchEmailPreview();
            }
        });

        // Auto-open batch email modal when returning from the from/add form.
        if (new URLSearchParams(location.search).get('openModal') === 'batchEmail') {
            const modal = document.getElementById('modal-batch-email');
            if (modal) {
                (globalThis as any).bootstrap?.Modal?.getOrCreateInstance(modal)?.show();
                history.replaceState({}, '', location.pathname);
            }
        }
    }

    private handleChange(event: Event): void {
        const target = event.target as HTMLElement;

        // Handle "select all" checkbox
        const selectAll = target.closest('[name="checkbox-selection-all"]') as HTMLInputElement;
        if (selectAll) {
            this.handleSelectAllCheckboxes(selectAll.checked);
            return;
        }

        // Handle user all clients checkbox
        const userAllClients = target.closest('#user_all_clients') as HTMLInputElement;
        if (userAllClients) {
            this.handleAllClientsCheck();
        }

        // Filter the copy-to-client checkbox list (inv/view modal)
        const copySearch = target.closest('#copy-client-search') as HTMLInputElement | null;
        if (copySearch) {
            this.filterCopyClientList(copySearch.value);
        }

        // Filter the bulk-copy client checkbox list (inv/index modal)
        const copyMultipleSearch = target.closest('#copy-inv-multiple-client-search') as HTMLInputElement | null;
        if (copyMultipleSearch) {
            this.filterCopyMultipleClientList(copyMultipleSearch.value);
        }

        // Parse CSV file for spreadsheet copy
        const csvFile = target.closest('#copy_inv_csv_file') as HTMLInputElement | null;
        if (csvFile) {
            void this.handleCopyCsvFileChange(csvFile);
        }
    }

    private handleClick(event: Event): void {
        const target = event.target as HTMLElement;

        // Mark as sent
        const markAsSent = closestSafe(target, '#btn-mark-as-sent');
        if (markAsSent) {
            this.handleMarkAsSent();
            return;
        }

        // Mark sent as draft
        const markDraft = closestSafe(target, '#btn-mark-sent-as-draft');
        if (markDraft) {
            this.handleMarkSentAsDraft();
            return;
        }

        // Create recurring invoice
        const createRecurring = closestSafe<HTMLElement>(
            target,
            '.create_recurring_confirm_multiple'
        );
        if (createRecurring) {
            this.handleCreateRecurringMultiple(createRecurring);
            return;
        }

        // Delete invoice items
        const deleteItemsConfirm =
            closestSafe<HTMLElement>(target, '.delete-items-confirm-inv') ||
            closestSafe<HTMLElement>(target, '#delete-items-confirm-inv');
        if (deleteItemsConfirm) {
            this.handleDeleteInvoiceItems(deleteItemsConfirm);
            return;
        }

        // Bulk quick pay (modal confirm)
        const bulkQuickPay = closestSafe(target, '#bulk-quick-pay-confirm');
        if (bulkQuickPay) {
            void this.handleBulkQuickPay();
            return;
        }

        // Batch email (modal confirm)
        const batchEmailConfirm = closestSafe(target, '#batch-email-confirm');
        if (batchEmailConfirm) {
            void this.handleBatchEmail();
            return;
        }

        // Copy multiple invoices (spreadsheet import)
        const copySpreadsheet = closestSafe<HTMLElement>(target, '#modal_copy_inv_spreadsheet_confirm');
        if (copySpreadsheet) {
            void this.handleCopyInvoicesSpreadsheet();
            return;
        }

        // Copy multiple invoices
        const copyMultiple = closestSafe<HTMLElement>(target, '.modal_copy_inv_multiple_confirm');
        if (copyMultiple) {
            this.handleCopyMultipleInvoices(copyMultiple);
            return;
        }

        // Copy single invoice
        const invToInv =
            closestSafe<HTMLElement>(target, '#inv_to_inv_confirm') ||
            closestSafe<HTMLElement>(target, '.inv_to_inv_confirm');
        if (invToInv) {
            this.handleCopySingleInvoice(invToInv);
            return;
        }

        // Add invoice tax
        const invTaxSubmit = closestSafe<HTMLElement>(target, '#inv_tax_submit');
        if (invTaxSubmit) {
            event.preventDefault();
            this.handleAddInvoiceTax(invTaxSubmit);
            return;
        }

        if (this.handleExportClick(target)) { return; }

        // Payment modal submit
        const paymentSubmit = closestSafe<HTMLElement>(target, '#btn_modal_payment_submit');
        if (paymentSubmit) {
            this.handlePaymentSubmit();
            return;
        }

        // Add row modal
        const addRowModal = closestSafe<HTMLElement>(target, '.btn_add_row_modal');
        if (addRowModal) {
            this.handleAddRowModal();
            return;
        }

        // Add invoice item row
        const addItemRow = closestSafe<HTMLElement>(target, '.btn_inv_item_add_row');
        if (addItemRow) {
            this.handleAddInvoiceItemRow();
            return;
        }

        // Delete single item
        const deleteItem = closestSafe<HTMLElement>(target, '.btn_delete_item');
        if (deleteItem) {
            this.handleDeleteSingleItem(deleteItem);
        }
    }

    private handleExportClick(target: HTMLElement): boolean {
        if (closestSafe<HTMLElement>(target, '#inv_to_pdf_confirm_with_custom_fields')) {
            this.handlePdfExport(true);
            return true;
        }
        if (closestSafe<HTMLElement>(target, '#inv_to_pdf_confirm_without_custom_fields')) {
            this.handlePdfExport(false);
            return true;
        }
        if (closestSafe<HTMLElement>(target, '#inv_to_modal_pdf_confirm_with_custom_fields')) {
            this.handleModalPdfView(true);
            return true;
        }
        if (closestSafe<HTMLElement>(target, '#inv_to_modal_pdf_confirm_without_custom_fields')) {
            this.handleModalPdfView(false);
            return true;
        }
        if (closestSafe<HTMLElement>(target, '#inv_to_html_confirm_with_custom_fields')) {
            this.handleHtmlExport(true);
            return true;
        }
        if (closestSafe<HTMLElement>(target, '#inv_to_html_confirm_without_custom_fields')) {
            this.handleHtmlExport(false);
            return true;
        }
        return false;
    }

    private restoreButton(btn: HTMLElement | null, html: string | undefined): void {
        if (btn && html) {
            btn.innerHTML = html;
            (btn as HTMLButtonElement).disabled = false;
        }
    }

    private getCheckedInvoiceIds(): string[] {
        const selected: string[] = [];
        const table = document.getElementById('table-invoice');

        if (!table) return selected;

        const checkboxes = table.querySelectorAll(
            'input[type="checkbox"]:checked'
        ) as NodeListOf<HTMLInputElement>;
        checkboxes.forEach(checkbox => {
            if (checkbox.id) {
                selected.push(checkbox.id);
            }
        });

        return selected;
    }

    private async handleMarkAsSent(): Promise<void> {
        const btn = document.getElementById('btn-mark-as-sent');
        const originalHtml = btn?.innerHTML;

        if (btn) {
            setButtonLoadingOn(btn);
        }

        try {
            const selected = this.getCheckedInvoiceIds();
            const url = `${location.origin}/invoice/inv/markAsSent`;

            const response = await getJson<ApiResponse>(url, { keylist: selected });
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
                globalThis.location.reload();
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
                globalThis.location.reload();
            }
        } catch (error) {
            console.error('mark_as_sent error', error);
            if (btn && originalHtml) {
                setButtonLoadingOff(btn, originalHtml);
            }
            alert('An error occurred. See console for details.');
        }
    }

    private async handleBulkQuickPay(): Promise<void> {
        const selected = this.getCheckedInvoiceIds();
        if (selected.length === 0) {
            alert('No invoices selected.');
            return;
        }
        const dateInput = document.getElementById('bulk-quick-pay-date') as HTMLInputElement | null;
        const noteInput = document.getElementById('bulk-quick-pay-note') as HTMLInputElement | null;
        const date = dateInput?.value ?? '';
        const note = noteInput?.value ?? '';
        if (date === '') {
            alert('Please enter a payment date.');
            return;
        }
        const btn = document.getElementById('bulk-quick-pay-confirm');
        const originalHtml = btn?.innerHTML;
        if (btn) { setButtonLoadingOn(btn); }
        try {
            const url = `${location.origin}/invoice/inv/bulkquickpay`;
            const response = await getJson<ApiResponse>(url, { keylist: selected, date, note });
            const data = parsedata(response);
            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
            } else if (btn) {
                btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
            }
            globalThis.location.reload();
        } catch (error) {
            console.error('bulk_quick_pay error', error);
            if (btn && originalHtml) { setButtonLoadingOff(btn, originalHtml); }
            alert('An error occurred. See console for details.');
        }
    }

    private async handleMarkSentAsDraft(): Promise<void> {
        const btn = document.getElementById('btn-mark-sent-as-draft');
        const originalHtml = btn?.innerHTML;

        if (btn) {
            setButtonLoadingOn(btn);
        }

        try {
            const selected = this.getCheckedInvoiceIds();
            const url = `${location.origin}/invoice/inv/markSentAsDraft`;

            const response = await getJson<ApiResponse>(url, { keylist: selected });
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
                globalThis.location.reload();
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
                globalThis.location.reload();
            }
        } catch (error) {
            console.error('mark_sent_as_draft error', error);
            if (btn && originalHtml) {
                setButtonLoadingOff(btn, originalHtml);
            }
            alert('An error occurred. See console for details.');
        }
    }

    private async handleCreateRecurringMultiple(createRecurring: HTMLElement): Promise<void> {
        const btn =
            (document.querySelector('.create_recurring_confirm_multiple') as HTMLElement) ||
            createRecurring;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<h6 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h6>';
            (btn as HTMLButtonElement).disabled = true;
        }

        try {
            // Get selected invoice checkboxes
            const selected = this.getCheckedInvoiceIds();

            if (selected.length === 0) {
                alert('Please select invoices to create recurring invoices.');
                this.restoreButton(btn, originalHtml);
                return;
            }

            const payload: RecurringInvoiceData = {
                keylist: selected,
                recur_frequency: getFieldValue('recur_frequency'),
                recur_start_date: getFieldValue('recur_start_date'),
                recur_end_date: getFieldValue('recur_end_date'),
            };

            // Validate required fields
            if (!payload.recur_frequency || !payload.recur_start_date) {
                alert('Please select frequency and start date.');
                this.restoreButton(btn, originalHtml);
                return;
            }

            const url = `${location.origin}/invoice/invrecurring/multiple`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';

                // Close modal if using Bootstrap
                this.closeModal('create-recurring-multiple');

                setTimeout(() => {
                    globalThis.location.reload();
                }, 500);
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
                // Use the server's error message if available, otherwise fall back to generic message
                const errorMessage = data.message || 'Failed to create recurring invoices. Please try again.';
                alert(errorMessage);
                this.restoreButton(btn, originalHtml);
            }
        } catch (error) {
            console.error('invrecurring/multiple error', error);
            this.restoreButton(btn, originalHtml);
            alert('An error occurred while creating recurring invoices. See console for details.');
        }
    }

    private async handleCopyMultipleInvoices(copyMultiple: HTMLElement): Promise<void> {
        const btn =
            (document.querySelector('.modal_copy_inv_multiple_confirm') as HTMLElement) ||
            copyMultiple;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<h2 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h2>';
            (btn as HTMLButtonElement).disabled = true;
        }

        try {
            const modalCreatedDate = getFieldValue('modal_created_date');
            const selected = this.getCheckedInvoiceIds();

            if (selected.length === 0) {
                alert('Please select invoices to copy.');
                this.restoreButton(btn, originalHtml);
                return;
            }

            const clientIds: string[] = Array.from(
                document.querySelectorAll<HTMLInputElement>(
                    'input[name="copy_inv_multiple_client_ids[]"]:checked'
                )
            ).map(cb => cb.value).filter(v => v !== '');

            if (clientIds.length === 0) {
                alert('Please select at least one client.');
                this.restoreButton(btn, originalHtml);
                return;
            }

            const payload: CopyMultipleInvoicesData = {
                keylist: selected,
                modal_created_date: modalCreatedDate,
                client_ids: clientIds,
            };

            const url = `${location.origin}/invoice/inv/multiplecopy`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
                globalThis.location.reload();
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
                globalThis.location.reload();
            }
        } catch (error) {
            console.error('multiplecopy error', error);
            this.restoreButton(btn, originalHtml);
            alert('An error occurred. See console for details.');
        }
    }

    private parseCopyCsv(text: string): CopyInvSpreadsheetRow[] {
        const lines = text.split(/\r?\n/).filter(l => l.trim() !== '');
        if (lines.length < 2) {
            return [];
        }
        const header = lines[0];
        const delimiter = header.includes(';') ? ';' : ',';
        const splitLine = (line: string): string[] =>
            line.split(delimiter).map(c => c.trim().replace(/^"|"$/g, ''));
        const rows: CopyInvSpreadsheetRow[] = [];
        for (let i = 1; i < lines.length; i++) {
            const cols = splitLine(lines[i]);
            rows.push({
                date_created: cols[0] ?? '',
                note:         cols[1] ?? '',
                same_amount:  cols[2] ?? '1',
                payment_date: cols[3] ?? '',
            });
        }
        return rows;
    }

    private async handleCopyCsvFileChange(input: HTMLInputElement): Promise<void> {
        const file = input.files?.[0];
        const preview = document.getElementById('copy_inv_csv_preview');
        const tbody   = document.getElementById('copy_inv_csv_tbody');
        const importBtn = document.getElementById('modal_copy_inv_spreadsheet_confirm') as HTMLButtonElement | null;

        if (!file || !preview || !tbody || !importBtn) {
            return;
        }

        const text = await file.text();
        const rows = this.parseCopyCsv(text);

        tbody.innerHTML = '';
        rows.forEach(row => {
            const tr = document.createElement('tr');
            (['date_created', 'note', 'same_amount', 'payment_date'] as const).forEach(key => {
                const td = document.createElement('td');
                td.textContent = row[key];
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });

        if (rows.length > 0) {
            preview.style.display = 'block';
            importBtn.style.display = 'inline-block';
        } else {
            preview.style.display = 'none';
            importBtn.style.display = 'none';
        }
    }

    private async handleCopyInvoicesSpreadsheet(): Promise<void> {
        const btn = document.getElementById('modal_copy_inv_spreadsheet_confirm') as HTMLButtonElement | null;
        const originalHtml = btn?.innerHTML ?? '';

        if (btn) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            btn.disabled = true;
        }

        try {
            const selected = this.getCheckedInvoiceIds();
            if (selected.length === 0) {
                alert('Please select invoices to copy.');
                this.restoreButton(btn, originalHtml);
                return;
            }

            const clientIds: string[] = Array.from(
                document.querySelectorAll<HTMLInputElement>(
                    'input[name="copy_inv_multiple_client_ids[]"]:checked'
                )
            ).map(cb => cb.value).filter(v => v !== '');

            const tbody = document.getElementById('copy_inv_csv_tbody');
            const rows: CopyInvSpreadsheetRow[] = [];
            tbody?.querySelectorAll('tr').forEach(tr => {
                const cells = tr.querySelectorAll('td');
                rows.push({
                    date_created: (cells[0]?.textContent ?? '').trim(),
                    note:         (cells[1]?.textContent ?? '').trim(),
                    same_amount:  (cells[2]?.textContent ?? '1').trim(),
                    payment_date: (cells[3]?.textContent ?? '').trim(),
                });
            });

            if (rows.length === 0) {
                alert('No spreadsheet rows found. Please upload a CSV file first.');
                this.restoreButton(btn, originalHtml);
                return;
            }

            const payload: CopyInvSpreadsheetPayload = {
                keylist:    selected,
                client_ids: clientIds,
                rows_json:  JSON.stringify(rows),
            };

            const url = `${location.origin}/invoice/inv/multiplecopyspreadsheet`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                globalThis.location.reload();
            } else {
                if (btn) btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                globalThis.location.reload();
            }
        } catch (error) {
            console.error('multiplecopyspreadsheet error', error);
            this.restoreButton(btn, originalHtml);
            alert('An error occurred. See console for details.');
        }
    }

    private async handleAddInvoiceTax(invTaxSubmit: HTMLElement): Promise<void> {
        const btn = document.getElementById('inv_tax_submit') as HTMLButtonElement;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            btn.disabled = true;
        }

        try {
            // Get invoice ID from current URL
            const currentUrl = new URL(location.href);
            const inv_id = currentUrl.pathname.split('/').at(-1) || '';

            const payload: AddInvoiceTaxData = {
                inv_id: inv_id,
                inv_tax_rate_id: getFieldValue('inv_tax_rate_id'),
                include_inv_item_tax: getFieldValue('include_inv_item_tax'),
            };

            const url = `${location.origin}/invoice/inv/saveInvTaxRate`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<i class="bi bi-check-lg"></i>';

                // Close modal and reload page
                this.closeModal('add-inv-tax');

                setTimeout(() => {
                    globalThis.location.reload();
                }, 500);
            } else {
                if (btn) btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                alert('Failed to add invoice tax. Please try again.');
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            }
        } catch (error) {
            console.error('invoice tax add error', error);
            if (btn && originalHtml) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
            alert('An error occurred while adding invoice tax. See console for details.');
        }
    }

    private async handleCopySingleInvoice(invToInv: HTMLElement): Promise<void> {
        const btn =
            (document.querySelector('.inv_to_inv_confirm') as HTMLElement) || invToInv;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<h6 class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span></h6>';
            (btn as HTMLButtonElement).disabled = true;
        }

        try {
            const absoluteUrl = new URL(location.href);
            const inv_id = absoluteUrl.pathname.split('/').at(-1) ?? '';

            // Collect all checked client checkboxes (multiselect modal)
            const clientIds: string[] = Array.from(
                document.querySelectorAll<HTMLInputElement>('input[name="copy_client_ids[]"]:checked')
            ).map(cb => cb.value).filter(v => v !== '');

            if (clientIds.length === 0) {
                alert('Please select at least one client.');
                this.restoreButton(btn, originalHtml);
                return;
            }

            const payload: CopySingleInvoiceData = {
                inv_id,
                client_ids: clientIds,
                user_id: getFieldValue('user_id'),
            };

            const url = `${location.origin}/invoice/inv/invToInvConfirm`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check-lg"></i></h2>';
                // Single client: redirect to new invoice; multiple: reload list
                if (data.new_invoice_id) {
                    globalThis.location.href = `${location.origin}/invoice/inv/view/${data.new_invoice_id}`;
                } else {
                    globalThis.location.reload();
                }
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-x-lg"></i></h2>';
                globalThis.location.reload();
            }
        } catch (error) {
            console.error('inv_to_inv_confirm error', error);
            if (btn && originalHtml) {
                btn.innerHTML = originalHtml;
                (btn as HTMLButtonElement).disabled = false;
            }
            alert('An error occurred. See console for details.');
        }
    }

    private filterCopyClientList(query: string): void {
        const q = query.toLowerCase();
        document.querySelectorAll<HTMLElement>('#copy-client-list .form-check').forEach(el => {
            const label = el.querySelector('label');
            el.style.display = !q || (label?.textContent?.toLowerCase().includes(q) ?? false) ? '' : 'none';
        });
    }

    private filterCopyMultipleClientList(query: string): void {
        const q = query.toLowerCase();
        document.querySelectorAll<HTMLElement>('#copy-inv-multiple-client-list .form-check').forEach(el => {
            const label = el.querySelector('label');
            el.style.display = !q || (label?.textContent?.toLowerCase().includes(q) ?? false) ? '' : 'none';
        });
    }

    private async handleDeleteInvoiceItems(deleteItemsConfirm: HTMLElement): Promise<void> {
        const btn =
            (document.querySelector('.delete-items-confirm-inv') as HTMLElement) ||
            deleteItemsConfirm;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            (btn as HTMLButtonElement).disabled = true;
        }

        try {
            // Get selected item checkboxes from the modal table
            const selected: string[] = [];

            // Use the same selector pattern as quote for consistency
            const checkboxes = document.querySelectorAll(
                "input[name='item_ids[]']:checked"
            ) as NodeListOf<HTMLInputElement>;

            checkboxes.forEach(checkbox => {
                if (checkbox.value) {
                    selected.push(checkbox.value);
                }
            });

            // Get invoice ID from current URL
            const currentUrl = new URL(location.href);
            const inv_id = currentUrl.pathname.split('/').at(-1) || '';

            if (selected.length === 0) {
                alert('Please select items to delete.');
                this.restoreButton(btn, originalHtml);
                return;
            }

            const payload: DeleteInvoiceItemsData = {
                item_ids: selected,
                inv_id: inv_id,
            };

            const url = `${location.origin}/invoice/invitem/multiple`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<i class="bi bi-check-lg"></i>';

                // Close modal and reload page
                this.closeModal('delete-items');

                setTimeout(() => {
                    globalThis.location.reload();
                }, 500);
            } else {
                if (btn) btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                alert('Failed to delete items. Please try again.');
                this.restoreButton(btn, originalHtml);
            }
        } catch (error) {
            console.error('delete items error', error);
            this.restoreButton(btn, originalHtml);
            alert('An error occurred while deleting items. See console for details.');
        }
    }

    private handlePdfExport(withCustomFields: boolean): void {
        const endpoint = withCustomFields ? '1' : '0';
        const url = `${location.origin}/invoice/inv/pdf/${endpoint}`;
        globalThis.open(url, '_blank');
    }

    private handleModalPdfView(withCustomFields: boolean): void {
        const endpoint = withCustomFields ? '1' : '0';
        const url = `${location.origin}/invoice/inv/pdf/${endpoint}`;

        // Set the iframe src to the URL of the PDF
        const iframe = document.getElementById('modal-view-inv-pdf') as HTMLIFrameElement;
        if (iframe) {
            iframe.src = url;
        }

        // Open the modal using Bootstrap
        try {
            if ((globalThis as any).bootstrap?.Modal !== undefined) {
                const modalEl = document.getElementById('modal-layout-modal-pdf-inv');
                if (modalEl) {
                    const modal = new (globalThis as any).bootstrap.Modal(modalEl);
                    modal.show();
                }
            }
        } catch (e) {
            console.warn('Failed to open PDF modal:', e);
        }
    }

    private handleHtmlExport(withCustomFields: boolean): void {
        const endpoint = withCustomFields ? '1' : '0';
        const url = `${location.origin}/invoice/inv/html/${endpoint}`;
        globalThis.open(url, '_blank');
    }

    private async handlePaymentSubmit(): Promise<void> {
        const url = `${location.origin}/invoice/payment/add_with_ajax`;
        const btn = document.getElementById('btn_modal_payment_submit') as HTMLButtonElement;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            btn.disabled = true;
        }

        try {
            // Get payment form data - adjust field names as needed
            const payload = {
                // Add payment form fields here based on the actual form structure
                // This is a placeholder - you'll need to adjust based on the actual payment form
                amount: getFieldValue('payment_amount'),
                payment_method: getFieldValue('payment_method'),
                payment_date: getFieldValue('payment_date'),
                // Add other payment fields as needed
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                // Close payment modal and reload
                this.closeModal('payment-modal'); // Adjust modal ID as needed
                setTimeout(() => {
                    globalThis.location.reload();
                }, 500);
            } else {
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
                alert('Failed to process payment. Please try again.');
            }
        } catch (error) {
            console.error('Payment submission error:', error);
            if (btn && originalHtml) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
            alert('An error occurred while processing payment. See console for details.');
        }
    }

    private handleAddRowModal(): void {
        const currentUrl = new URL(location.href);
        const inv_id = currentUrl.pathname.split('/').at(-1) || '';
        const url = `${location.origin}/invoice/invitem/add/${inv_id}`;

        // Load content into modal placeholder
        const modalPlaceholder = document.getElementById('modal-placeholder-invitem');
        if (modalPlaceholder) {
            // Use fetch to load the content
            fetch(url, { credentials: 'same-origin' })
                .then(response => response.text())
                .then(html => {
                    // Sanitize HTML content to prevent XSS attacks
                    modalPlaceholder.textContent = '';
                    const tempDiv = document.createElement('div');
                    tempDiv.textContent = html;
                    // For trusted server content, use createDocumentFragment for better security
                    const fragment = document.createDocumentFragment();
                    const parser = new DOMParser();
                    try {
                        const doc = parser.parseFromString(html, 'text/html');
                        // Only append if parsing was successful and content is from trusted source
                        if (doc?.body) {
                            while (doc.body.firstChild) {
                                fragment.appendChild(doc.body.firstChild);
                            }
                            modalPlaceholder.appendChild(fragment);
                        }
                    } catch (e) {
                        console.error('HTML parsing error:', e);
                        modalPlaceholder.textContent = 'Error loading content';
                    }
                })
                .catch(error => {
                    console.error('Failed to load modal content:', error);
                    modalPlaceholder.textContent = 'Failed to load item form. Please try again.';
                });
        }
    }

    private handleAddInvoiceItemRow(): void {
        // Clone the new row template and append to item table
        const newRow = document.getElementById('new_row');
        const itemTable = document.getElementById('item_table');

        if (newRow && itemTable) {
            const clonedRow = newRow.cloneNode(true) as HTMLElement;
            clonedRow.removeAttribute('id');
            clonedRow.classList.add('item');
            clonedRow.style.display = 'block'; // Show the cloned row
            itemTable.appendChild(clonedRow);
        }
    }

    private async handleDeleteSingleItem(deleteItem: HTMLElement): Promise<void> {
        const itemId = deleteItem.dataset['id'];

        if (!itemId) {
            // If no ID, just remove the DOM element (unsaved item)
            const itemRow = deleteItem.closest('.item');
            if (itemRow) {
                itemRow.remove();
            }
            return;
        }

        try {
            const url = `${location.origin}/invoice/inv/deleteItem/${itemId}`;
            const response = await getJson<ApiResponse>(url, { id: itemId });
            const data = parsedata(response);

            if (data.success === 1) {
                // Remove the item row from DOM
                const itemRow = deleteItem.closest('.item');
                if (itemRow) {
                    itemRow.remove();
                }
                alert('Deleted');
                // Reload to update totals
                globalThis.location.reload();
            } else {
                alert('Failed to delete item. Please try again.');
            }
        } catch (error) {
            console.error('Delete item error:', error);
            alert('An error occurred while deleting the item. Please try again.');
        }
    }

    private handleSelectAllCheckboxes(checked: boolean): void {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]') as NodeListOf<HTMLInputElement>;
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
    }

    private initializeAllClientsCheck(): void {
        this.handleAllClientsCheck();
    }

    private handleAllClientsCheck(): void {
        const userAllClientsCheckbox = document.getElementById('user_all_clients') as HTMLInputElement;
        const listClientElement = document.getElementById('list_client');

        if (userAllClientsCheckbox && listClientElement) {
            if (userAllClientsCheckbox.checked) {
                listClientElement.style.display = 'none';
            } else {
                listClientElement.style.display = 'block';
            }
        }
    }

    private closeModal(modalId: string): void {
        try {
            if ((globalThis as any).bootstrap?.Modal !== undefined) {
                const modalEl = document.getElementById(modalId);
                if (modalEl) {
                    const modalInstance = (globalThis as any).bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            }
        } catch (e) {
            console.warn('Failed to close modal:', e);
        }
    }

    private async handleBatchEmailPreview(): Promise<void> {
        const loadingEl  = document.getElementById('batch-email-loading');
        const previewEl  = document.getElementById('batch-email-preview');
        const bodyEl     = document.getElementById('batch-email-preview-body');
        const confirmBtn = document.getElementById('batch-email-confirm');

        if (loadingEl)  { loadingEl.classList.remove('d-none'); }
        if (previewEl)  { previewEl.classList.add('d-none'); }
        if (confirmBtn) { confirmBtn.classList.add('d-none'); }

        const selected = this.getCheckedInvoiceIds();
        if (selected.length === 0) {
            if (loadingEl) { loadingEl.classList.add('d-none'); }
            return;
        }

        try {
            const url = `${location.origin}/invoice/inv/batchEmailPreview`;
            type PreviewRow = { client: string; email: string; source: string; invoice_count: number };
            const raw = await getJson<unknown>(url, { keylist: selected });
            // DataResponseFactory double-encodes JSON; unwrap if the result is a string
            const rows: PreviewRow[] = Array.isArray(raw)
                ? (raw as PreviewRow[])
                : typeof raw === 'string'
                    ? (JSON.parse(raw) as PreviewRow[])
                    : [];
            if (bodyEl) {
                bodyEl.innerHTML = rows.map(r =>
                    `<tr><td>${r.client}</td><td>${r.email}</td><td>${r.source}</td><td>${String(r.invoice_count)}</td></tr>`
                ).join('');
            }
            if (loadingEl) { loadingEl.classList.add('d-none'); }
            if (previewEl) { previewEl.classList.remove('d-none'); }
            if (confirmBtn && rows.length > 0) {
                confirmBtn.classList.remove('d-none');
            }
        } catch (error) {
            console.error('batch_email_preview error', error);
            if (loadingEl) { loadingEl.classList.add('d-none'); }
        }
    }

    private async handleBatchEmail(): Promise<void> {
        const selected      = this.getCheckedInvoiceIds();
        const selectEl      = document.getElementById('batch-email-template') as HTMLSelectElement | null;
        const fromSelectEl  = document.getElementById('batch-email-from') as HTMLSelectElement | null;
        const confirmBtn    = document.getElementById('batch-email-confirm');
        const originalHtml  = confirmBtn?.innerHTML;

        if (selected.length === 0 || !selectEl?.value) {
            alert('Please select invoices and an email template.');
            return;
        }

        if (confirmBtn) { setButtonLoadingOn(confirmBtn); }

        try {
            const url = `${location.origin}/invoice/inv/batchEmail`;
            const response = await getJson<ApiResponse>(url, {
                keylist: selected,
                email_template_id: selectEl.value,
                from_dropdown_id: fromSelectEl?.value ?? '',
            });
            const data = parsedata(response);
            if (data.success === 1) {
                if (confirmBtn) {
                    confirmBtn.innerHTML = '<i class="bi bi-check-lg"></i>';
                }
            } else if (confirmBtn) {
                confirmBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
            }
            globalThis.location.reload();
        } catch (error) {
            console.error('batch_email error', error);
            if (confirmBtn && originalHtml) { setButtonLoadingOff(confirmBtn, originalHtml); }
            alert('An error occurred sending batch email. See console for details.');
        }
    }
}
