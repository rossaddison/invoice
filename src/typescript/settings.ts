import { parsedata, ApiResponse } from './utils.js';

// Settings-specific interfaces
interface FphGenerateRequest {
    userAgent: string;
    width: number;
    height: number;
    scalingFactor: number;
    colourDepth: number;
    windowInnerWidth: number;
    windowInnerHeight: number;
}

interface FphGenerateResponse extends ApiResponse {
    success: 0 | 1;
    userAgent?: string;
    deviceId?: string;
    width?: string;
    height?: string;
    scalingFactor?: string;
    colourDepth?: string;
    timestamp?: string;
    windowSize?: string;
    userUuid?: string;
}

interface CronKeyResponse extends ApiResponse {
    success: 0 | 1;
    cronkey?: string;
}

// Settings handler class
export class SettingsHandler {
    private originalDisplayStyles: Record<string, string> = {};
    private originalDisabledStates: Record<string, { element: HTMLElement; disabled: boolean }> = {};

    constructor() {
        this.bindEventListeners();
    }

    private bindEventListeners(): void {
        document.addEventListener('DOMContentLoaded', this.initialize.bind(this));
    }

    private initialize(): void {
        // Initialize SMTP toggle
        this.toggleSmtpSettings();

        // Email send method change handler
        const emailSendMethodEl = document.getElementById('email_send_method') as HTMLSelectElement;
        if (emailSendMethodEl) {
            emailSendMethodEl.addEventListener('change', this.toggleSmtpSettings.bind(this));
        }

        // FPH generate button
        const fphBtn = document.getElementById('btn_fph_generate') as HTMLButtonElement;
        if (fphBtn) {
            fphBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleFphGenerateClick();
            });
        }

        // Generate cron key button
        const cronBtn = document.getElementById('btn_generate_cron_key') as HTMLButtonElement;
        if (cronBtn) {
            cronBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleGenerateCronKeyClick();
            });
        }

        // Submit button
        const submitBtn = document.getElementById('btn-submit') as HTMLButtonElement;
        if (submitBtn) {
            submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSettingsSubmitClick();
            });
        }

        // Online payment select
        const onlineSelect = document.getElementById('online-payment-select') as HTMLSelectElement;
        if (onlineSelect) {
            onlineSelect.addEventListener('change', this.handleOnlinePaymentSelectChange.bind(this));
        }

        // Run online payment handler once to ensure initial state
        this.handleOnlinePaymentSelectChange();
    }

    /**
     * Toggle visibility of SMTP settings based on email_send_method value
     */
    private toggleSmtpSettings(): void {
        const emailSendMethodEl = document.getElementById('email_send_method') as HTMLSelectElement;
        const div = document.getElementById('div-smtp-settings') as HTMLDivElement;
        
        if (!div || !emailSendMethodEl) return;

        if (emailSendMethodEl.value === 'smtp') {
            div.style.display = '';
        } else {
            div.style.display = 'none';
        }
    }

    /**
     * Generate fingerprint / client metrics for FPH
     */
    private async handleFphGenerateClick(): Promise<void> {
        const url = `${location.origin}/invoice/setting/fphgenerate`;
        
        const requestData: FphGenerateRequest = {
            userAgent: navigator.userAgent,
            width: window.screen.width,
            height: window.screen.height,
            scalingFactor: Math.round(window.devicePixelRatio * 100) / 100,
            colourDepth: window.screen.colorDepth,
            windowInnerWidth: window.innerWidth,
            windowInnerHeight: window.innerHeight
        };

        const params = new URLSearchParams();
        Object.entries(requestData).forEach(([key, value]) => {
            params.append(key, value.toString());
        });

        try {
            const response = await fetch(`${url}?${params.toString()}`, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`Network response not ok: ${response.status}`);
            }

            const data = await response.json().catch(() => ({}));
            const parsedResponse = parsedata(data) as FphGenerateResponse;

            if (parsedResponse.success === 1) {
                this.updateSettingField('settings[fph_client_browser_js_user_agent]', parsedResponse.userAgent);
                this.updateSettingField('settings[fph_client_device_id]', parsedResponse.deviceId);
                this.updateSettingField('settings[fph_screen_width]', parsedResponse.width);
                this.updateSettingField('settings[fph_screen_height]', parsedResponse.height);
                this.updateSettingField('settings[fph_screen_scaling_factor]', parsedResponse.scalingFactor);
                this.updateSettingField('settings[fph_screen_colour_depth]', parsedResponse.colourDepth);
                this.updateSettingField('settings[fph_timestamp]', parsedResponse.timestamp);
                this.updateSettingField('settings[fph_window_size]', parsedResponse.windowSize);
                this.updateSettingField('settings[fph_gov_client_user_id]', parsedResponse.userUuid);
            }
        } catch (error) {
            console.error('FPH generate failed', error);
        }
    }

    /**
     * Helper to update a settings field value
     */
    private updateSettingField(fieldId: string, value?: string): void {
        const element = document.getElementById(fieldId) as HTMLInputElement;
        if (element && value !== undefined) {
            element.value = value;
        }
    }

    /**
     * Generate cron key
     */
    private async handleGenerateCronKeyClick(): Promise<void> {
        const buttons = document.querySelectorAll('.btn_generate_cron_key') as NodeListOf<HTMLButtonElement>;
        
        // Set loading state
        buttons.forEach(button => {
            button.innerHTML = '<i class="fa fa-spin fa-spinner fa-margin"></i>';
        });

        try {
            const url = `${location.origin}/invoice/setting/get_cron_key`;
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`Network response not ok: ${response.status}`);
            }

            const data = await response.json().catch(() => ({}));
            const parsedResponse = parsedata(data) as CronKeyResponse;

            if (parsedResponse.success === 1 && parsedResponse.cronkey) {
                // Update all elements with .cron_key class
                const cronKeyElements = document.querySelectorAll('.cron_key') as NodeListOf<HTMLInputElement>;
                cronKeyElements.forEach(element => {
                    element.value = parsedResponse.cronkey || '';
                });
            }

            // Restore button state
            buttons.forEach(button => {
                button.innerHTML = '<i class="fa fa-recycle fa-margin"></i>';
            });
        } catch (error) {
            console.error('get_cron_key failed', error);
            
            // Restore button state on error
            buttons.forEach(button => {
                button.innerHTML = '<i class="fa fa-recycle fa-margin"></i>';
            });
        }
    }

    /**
     * Submit settings form - ensure all tab elements are included
     */
    private handleSettingsSubmitClick(): void {
        const form = document.getElementById('form-settings') as HTMLFormElement;
        if (!form) return;

        // Before submitting, temporarily make all tab panes visible
        // to ensure all form elements are included in the submission
        const tabPanes = form.querySelectorAll('.tab-pane') as NodeListOf<HTMLDivElement>;
        
        this.originalDisplayStyles = {};
        this.originalDisabledStates = {};

        tabPanes.forEach(pane => {
            if (pane.id) {
                // Store and modify display style
                this.originalDisplayStyles[pane.id] = pane.style.display;
                pane.style.display = 'block';

                // Temporarily enable any disabled form elements in hidden tabs
                const disabledElements = pane.querySelectorAll('input:disabled, select:disabled, textarea:disabled') as NodeListOf<HTMLElement>;
                disabledElements.forEach((element, index) => {
                    const key = `${pane.id}_${index}`;
                    this.originalDisabledStates[key] = { element, disabled: true };
                    (element as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement).disabled = false;
                });
            }
        });

        // Small delay to ensure DOM updates, then submit
        setTimeout(() => {
            form.submit();
            this.restoreFormState(tabPanes);
        }, 10);
    }

    /**
     * Restore form state after submission
     */
    private restoreFormState(tabPanes: NodeListOf<HTMLDivElement>): void {
        // Restore original display styles
        tabPanes.forEach(pane => {
            if (pane.id && this.originalDisplayStyles[pane.id] !== undefined) {
                pane.style.display = this.originalDisplayStyles[pane.id];
            }
        });

        // Restore disabled states
        Object.entries(this.originalDisabledStates).forEach(([_, state]) => {
            if (state.element && state.disabled) {
                (state.element as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement).disabled = true;
            }
        });
    }

    /**
     * Online payment select change handler (show/hide gateway settings)
     */
    private handleOnlinePaymentSelectChange(): void {
        const select = document.getElementById('online-payment-select') as HTMLSelectElement;
        if (!select) return;

        const driver = select.value;

        // Hide all gateway settings
        const gatewaySettings = document.querySelectorAll('.gateway-settings') as NodeListOf<HTMLElement>;
        gatewaySettings.forEach(element => {
            if (!element.classList.contains('active-gateway')) {
                element.classList.add('hidden');
            }
        });

        // Show selected gateway settings
        const target = document.getElementById(`gateway-settings-${driver}`) as HTMLElement;
        if (target) {
            target.classList.remove('hidden');
            target.classList.add('active-gateway');
        }
    }
}