import { parsedata, getJson, ApiResponse, RequestParams, closestSafe } from './utils.js';

// Invoice-specific interfaces
interface RecurringInvoiceData extends RequestParams {
    keylist: string[];
    recur_start_date: string;
    recur_end_date?: string;
    recur_frequency: string;
}

// Helper to set button loading state
function setButtonLoading(button: HTMLElement, isLoading: boolean, originalHtml?: string): void {
    if (isLoading) {
        button.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        (button as HTMLButtonElement).disabled = true;
    } else {
        button.innerHTML = originalHtml || '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
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
        const createRecurring = closestSafe<HTMLElement>(target, '.create_recurring_confirm_multiple');
        if (createRecurring) {
            this.handleCreateRecurringMultiple(createRecurring);
            return;
        }
    }

    private getCheckedInvoiceIds(): string[] {
        const selected: string[] = [];
        const table = document.getElementById('table-invoice');
        
        if (!table) return selected;
        
        const checkboxes = table.querySelectorAll('input[type="checkbox"]:checked') as NodeListOf<HTMLInputElement>;
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
        const btn = document.querySelector('.create_recurring_confirm_multiple') as HTMLElement || createRecurring;
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
                recur_end_date: getFieldValue('recur_end_date')
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
                alert('Failed to create recurring invoices. Please try again.');
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