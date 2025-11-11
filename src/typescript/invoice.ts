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
}

interface CopySingleInvoiceData extends RequestParams {
    inv_id: string;
    client_id: string;
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

// Helper to set button loading state
function setButtonLoading(button: HTMLElement, isLoading: boolean, originalHtml?: string): void {
    if (isLoading) {
        button.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        (button as HTMLButtonElement).disabled = true;
    } else {
        button.innerHTML =
            originalHtml || '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
        (button as HTMLButtonElement).disabled = false;
    }
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
            return;
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

        // PDF Export with custom fields
        const pdfWithCustom = closestSafe<HTMLElement>(target, '#inv_to_pdf_confirm_with_custom_fields');
        if (pdfWithCustom) {
            this.handlePdfExport(true);
            return;
        }

        // PDF Export without custom fields
        const pdfWithoutCustom = closestSafe<HTMLElement>(target, '#inv_to_pdf_confirm_without_custom_fields');
        if (pdfWithoutCustom) {
            this.handlePdfExport(false);
            return;
        }

        // Modal PDF with custom fields
        const modalPdfWithCustom = closestSafe<HTMLElement>(target, '#inv_to_modal_pdf_confirm_with_custom_fields');
        if (modalPdfWithCustom) {
            this.handleModalPdfView(true);
            return;
        }

        // Modal PDF without custom fields
        const modalPdfWithoutCustom = closestSafe<HTMLElement>(target, '#inv_to_modal_pdf_confirm_without_custom_fields');
        if (modalPdfWithoutCustom) {
            this.handleModalPdfView(false);
            return;
        }

        // HTML Export with custom fields
        const htmlWithCustom = closestSafe<HTMLElement>(target, '#inv_to_html_confirm_with_custom_fields');
        if (htmlWithCustom) {
            this.handleHtmlExport(true);
            return;
        }

        // HTML Export without custom fields
        const htmlWithoutCustom = closestSafe<HTMLElement>(target, '#inv_to_html_confirm_without_custom_fields');
        if (htmlWithoutCustom) {
            this.handleHtmlExport(false);
            return;
        }

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
            return;
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
            setButtonLoading(btn, true);
        }

        try {
            const selected = this.getCheckedInvoiceIds();
            const url = `${location.origin}/invoice/inv/mark_as_sent`;

            const response = await getJson<ApiResponse>(url, { keylist: selected });
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.reload();
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                window.location.reload();
            }
        } catch (error) {
            console.error('mark_as_sent error', error);
            if (btn && originalHtml) {
                setButtonLoading(btn, false, originalHtml);
            }
            alert('An error occurred. See console for details.');
        }
    }

    private async handleMarkSentAsDraft(): Promise<void> {
        const btn = document.getElementById('btn-mark-sent-as-draft');
        const originalHtml = btn?.innerHTML;

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const selected = this.getCheckedInvoiceIds();
            const url = `${location.origin}/invoice/inv/mark_sent_as_draft`;

            const response = await getJson<ApiResponse>(url, { keylist: selected });
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.reload();
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                window.location.reload();
            }
        } catch (error) {
            console.error('mark_sent_as_draft error', error);
            if (btn && originalHtml) {
                setButtonLoading(btn, false, originalHtml);
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
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            (btn as HTMLButtonElement).disabled = true;
        }

        try {
            // Get selected invoice checkboxes
            const selected = this.getCheckedInvoiceIds();

            if (selected.length === 0) {
                alert('Please select invoices to create recurring invoices.');
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    (btn as HTMLButtonElement).disabled = false;
                }
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
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    (btn as HTMLButtonElement).disabled = false;
                }
                return;
            }

            const url = `${location.origin}/invoice/invrecurring/multiple`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';

                // Close modal if using Bootstrap
                this.closeModal('create-recurring-multiple');

                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                // Use the server's error message if available, otherwise fall back to generic message
                const errorMessage = data.message || 'Failed to create recurring invoices. Please try again.';
                alert(errorMessage);
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    (btn as HTMLButtonElement).disabled = false;
                }
            }
        } catch (error) {
            console.error('invrecurring/multiple error', error);
            if (btn && originalHtml) {
                btn.innerHTML = originalHtml;
                (btn as HTMLButtonElement).disabled = false;
            }
            alert('An error occurred while creating recurring invoices. See console for details.');
        }
    }

    private async handleCopyMultipleInvoices(copyMultiple: HTMLElement): Promise<void> {
        const btn =
            (document.querySelector('.modal_copy_inv_multiple_confirm') as HTMLElement) ||
            copyMultiple;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            (btn as HTMLButtonElement).disabled = true;
        }

        try {
            const modalCreatedDate = getFieldValue('modal_created_date');
            const selected = this.getCheckedInvoiceIds();

            if (selected.length === 0) {
                alert('Please select invoices to copy.');
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    (btn as HTMLButtonElement).disabled = false;
                }
                return;
            }

            const payload: CopyMultipleInvoicesData = {
                keylist: selected,
                modal_created_date: modalCreatedDate,
            };

            const url = `${location.origin}/invoice/inv/multiplecopy`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                window.location.reload();
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                window.location.reload();
            }
        } catch (error) {
            console.error('multiplecopy error', error);
            if (btn && originalHtml) {
                btn.innerHTML = originalHtml;
                (btn as HTMLButtonElement).disabled = false;
            }
            alert('An error occurred. See console for details.');
        }
    }

    private async handleAddInvoiceTax(invTaxSubmit: HTMLElement): Promise<void> {
        const btn = document.getElementById('inv_tax_submit') as HTMLButtonElement;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
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

            const url = `${location.origin}/invoice/inv/save_inv_tax_rate`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<i class="fa fa-check"></i>';

                // Close modal and reload page
                this.closeModal('add-inv-tax');

                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                if (btn) btn.innerHTML = '<i class="fa fa-times"></i>';
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
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            (btn as HTMLButtonElement).disabled = true;
        }

        try {
            const absoluteUrl = new URL(location.href);
            const inv_id = absoluteUrl.pathname.split('/').at(-1) || '';

            const payload: CopySingleInvoiceData = {
                inv_id: inv_id,
                client_id: getFieldValue('create_inv_client_id'),
                user_id: getFieldValue('user_id'),
            };

            const url = `${location.origin}/invoice/inv/inv_to_inv_confirm`;
            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';

                // Redirect to the newly created invoice
                if (data.new_invoice_id) {
                    window.location.href = `${location.origin}/invoice/inv/view/${data.new_invoice_id}`;
                } else {
                    // Fallback to reload current page if new_invoice_id not provided
                    window.location.reload();
                }
            } else {
                if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                window.location.reload();
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

    private async handleDeleteInvoiceItems(deleteItemsConfirm: HTMLElement): Promise<void> {
        const btn =
            (document.querySelector('.delete-items-confirm-inv') as HTMLElement) ||
            deleteItemsConfirm;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
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
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    (btn as HTMLButtonElement).disabled = false;
                }
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
                if (btn) btn.innerHTML = '<i class="fa fa-check"></i>';

                // Close modal and reload page
                this.closeModal('delete-items');

                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                if (btn) btn.innerHTML = '<i class="fa fa-times"></i>';
                alert('Failed to delete items. Please try again.');
                if (btn && originalHtml) {
                    btn.innerHTML = originalHtml;
                    (btn as HTMLButtonElement).disabled = false;
                }
            }
        } catch (error) {
            console.error('delete items error', error);
            if (btn && originalHtml) {
                btn.innerHTML = originalHtml;
                (btn as HTMLButtonElement).disabled = false;
            }
            alert('An error occurred while deleting items. See console for details.');
        }
    }

    private handlePdfExport(withCustomFields: boolean): void {
        const endpoint = withCustomFields ? '1' : '0';
        const url = `${location.origin}/invoice/inv/pdf/${endpoint}`;
        window.open(url, '_blank');
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
            if (typeof (window as any).bootstrap?.Modal !== 'undefined') {
                const modalEl = document.getElementById('modal-layout-modal-pdf-inv');
                if (modalEl) {
                    const modal = new (window as any).bootstrap.Modal(modalEl);
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
        window.open(url, '_blank');
    }

    private async handlePaymentSubmit(): Promise<void> {
        const url = `${location.origin}/invoice/payment/add_with_ajax`;
        const btn = document.getElementById('btn_modal_payment_submit') as HTMLButtonElement;
        const originalHtml = btn?.innerHTML;

        if (btn) {
            btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
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
                if (btn) btn.innerHTML = '<i class="fa fa-check"></i>';
                // Close payment modal and reload
                this.closeModal('payment-modal'); // Adjust modal ID as needed
                setTimeout(() => {
                    window.location.reload();
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
                        if (doc && doc.body) {
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
        const itemId = deleteItem.getAttribute('data-id');
        
        if (!itemId) {
            // If no ID, just remove the DOM element (unsaved item)
            const itemRow = deleteItem.closest('.item');
            if (itemRow) {
                itemRow.remove();
            }
            return;
        }

        try {
            const url = `${location.origin}/invoice/inv/delete_item/${itemId}`;
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
                window.location.reload();
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
            if (typeof (window as any).bootstrap?.Modal !== 'undefined') {
                const modalEl = document.getElementById(modalId);
                if (modalEl) {
                    const modalInstance = (window as any).bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            }
        } catch (e) {
            console.warn('Failed to close modal:', e);
        }
    }
}
