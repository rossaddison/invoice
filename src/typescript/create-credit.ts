import { getJson, parsedata, querySelector, getInputValue } from './utils.js';
import type { ApiResponse, InvoiceFormData } from './types.js';

/**
 * Handle create credit confirmation
 * Converts an invoice to a credit note
 */
export class CreateCreditHandler {
    private readonly confirmButtonSelector = '.create-credit-confirm';

    constructor() {
        this.initialize();
    }

    private initialize(): void {
        document.addEventListener('click', this.handleClick.bind(this), true);
    }

    private async handleClick(event: MouseEvent): Promise<void> {
        const target = event.target as Element;

        if (!target || target.id !== 'create-credit-confirm') {
            return;
        }

        event.preventDefault();

        try {
            await this.processCreateCredit();
        } catch (error) {
            console.error('Create credit error:', error);
            alert(`Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }

    private async processCreateCredit(): Promise<void> {
        const url = `${location.origin}/invoice/inv/create_credit_confirm`;
        const btn = querySelector<HTMLElement>(this.confirmButtonSelector);
        const absoluteUrl = new URL(location.href);

        // Show loading spinner
        if (btn) {
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
        }

        // Extract invoice ID from URL
        const invId = absoluteUrl.pathname.split('/').at(-1) || '';

        // Collect form data with type safety
        const formData: InvoiceFormData = {
            inv_id: invId,
            client_id: getInputValue('client_id'),
            inv_date_created: getInputValue('inv_date_created'),
            group_id: getInputValue('inv_group_id'),
            password: getInputValue('inv_password'),
            user_id: getInputValue('user_id'),
        };

        // Make API request
        const data = await getJson(url, formData);
        const response = parsedata(data) as ApiResponse;

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
        } else {
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
