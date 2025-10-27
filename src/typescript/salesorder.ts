import { parsedata, getJson, ApiResponse } from './utils.js';

// SalesOrder-specific interfaces
interface SalesOrderFormData {
    [key: string]: string | number | boolean;
}

interface SalesOrderSaveResponse extends ApiResponse {
    success: 0 | 1;
    message?: string;
}

// SalesOrder uses global TomSelect and Bootstrap defined in types.ts

// SalesOrder handler class
export class SalesOrderHandler {
    constructor() {
        this.bindEventListeners();
        this.initializeOnLoad();
    }

    private bindEventListeners(): void {
        document.addEventListener('click', this.handleClick.bind(this), true);
    }

    private initializeOnLoad(): void {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initSelects();
            });
        } else {
            this.initSelects();
        }
    }

    private handleClick(event: Event): void {
        const target = event.target as HTMLElement;

        // Open sales order modal
        const openModalBtn = target.closest('.open-salesorder-modal') as HTMLElement;
        if (openModalBtn) {
            this.handleOpenModal(openModalBtn);
            return;
        }

        // Save sales order via AJAX
        const saveBtn = target.closest('.salesorder-save') as HTMLElement;
        if (saveBtn) {
            this.handleSaveSalesOrder();
            return;
        }
    }

    /**
     * Initialize Tom Select if present for salesorder selects
     */
    private initSelects(): void {
        if (typeof window.TomSelect === 'undefined') return;

        const selects = document.querySelectorAll('.simple-select') as NodeListOf<HTMLSelectElement>;
        selects.forEach(element => {
            // Check if already initialized
            if (!(element as any)._tomselect) {
                try {
                    new window.TomSelect(element, {});
                    (element as any)._tomselect = true;
                } catch (error) {
                    console.warn('Failed to initialize TomSelect:', error);
                }
            }
        });
    }

    /**
     * Handle opening the sales order modal
     */
    private async handleOpenModal(openBtn: HTMLElement): Promise<void> {
        const url = openBtn.dataset.url || `${location.origin}/invoice/salesorder/modal`;
        const targetId = openBtn.dataset.target || 'modal-placeholder-salesorder';
        const target = document.getElementById(targetId);
        
        if (!target) {
            console.error(`Modal target element not found: ${targetId}`);
            return;
        }

        try {
            const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
            const html = await response.text();
            
            target.innerHTML = html;
            
            // Show modal using Bootstrap if available
            const modalEl = target.querySelector('.modal') as HTMLElement;
            if (modalEl && window.bootstrap?.Modal) {
                const modalInstance = new window.bootstrap.Modal(modalEl);
                modalInstance.show();
            }
            
            // Initialize selects in the new modal content
            this.initSelects();
        } catch (error) {
            console.error('Failed to load sales order modal:', error);
            alert('Failed to load modal. Please try again.');
        }
    }

    /**
     * Handle saving the sales order form
     */
    private async handleSaveSalesOrder(): Promise<void> {
        const form = document.querySelector('#salesorder_form') as HTMLFormElement;
        if (!form) {
            console.error('Sales order form not found');
            return;
        }

        try {
            const action = form.getAttribute('action') || `${location.origin}/invoice/salesorder/save`;
            const formData = new FormData(form);
            
            // Convert FormData to URLSearchParams for GET request
            const params = new URLSearchParams();
            formData.forEach((value, key) => {
                params.append(key, value.toString());
            });

            const url = `${action}?${params.toString()}`;
            const response = await fetch(url, { 
                cache: 'no-store', 
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            const parsedResponse = parsedata(data) as SalesOrderSaveResponse;

            if (parsedResponse.success === 1) {
                // Reload page on successful save
                window.location.reload();
            } else {
                const message = parsedResponse.message || 'Save failed';
                alert(message);
            }
        } catch (error) {
            console.error('Sales order save failed:', error);
            alert('An error occurred while saving. Please try again.');
        }
    }
}