import { parsedata, getJson, ApiResponse, RequestParams } from './utils.js';

// Family-specific interfaces
interface FamilySecondaryRequest extends RequestParams {
    category_primary_id: string;
}

interface FamilyNamesRequest extends RequestParams {
    category_secondary_id: string;
}

interface FamilySecondaryResponse extends ApiResponse {
    success: 0 | 1;
    secondary_categories?: Record<string, string>;
}

interface FamilyNamesResponse extends ApiResponse {
    success: 0 | 1;
    family_names?: Record<string, string>;
}

interface FamilyGenerateRequest extends RequestParams {
    family_ids: string[];
    tax_rate_id: string;
    unit_id: string;
    _csrf: string;
}

interface FamilyGenerateResponse extends ApiResponse {
    success: boolean;
    count?: number;
    message?: string;
    warnings?: string[];
    redirect_url?: string;
}

interface FamilyData {
    family_id: string;
    family_name: string;
    family_productprefix: string;
    family_commalist: string;
}

// Family handler class
export class FamilyHandler {
    constructor() {
        this.bindEventListeners();
    }

    private bindEventListeners(): void {
        // Check if DOM is already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', this.initializeSelectors.bind(this));
        } else {
            // DOM is already loaded, initialize immediately
            this.initializeSelectors();
        }
        document.addEventListener('click', this.handleClick.bind(this), true);
    }

    private handleClick(event: Event): void {
        const target = event.target as HTMLElement;

        if (target.closest('#btn-generate-products')) {
            event.preventDefault();
            event.stopPropagation();
            const checkedBoxes = document.querySelectorAll<HTMLInputElement>(
                'input[type="checkbox"][name="family_ids[]"]:checked'
            );
            if (checkedBoxes.length === 0) {
                alert('Please select at least one family to generate products.');
                return;
            }
            const modalEl = document.getElementById('generate-products-modal');
            if (modalEl && typeof (window as any).bootstrap?.Modal !== 'undefined') {
                (window as any).bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
            return;
        }

        if (target.closest('#process-generate-products')) {
            this.processProductGeneration();
            return;
        }
    }

    private initializeSelectors(): void {
        const primarySelect = document.getElementById(
            'family-category-primary-id'
        ) as HTMLSelectElement;
        const secondarySelect = document.getElementById(
            'family-category-secondary-id'
        ) as HTMLSelectElement;

        if (primarySelect) {
            primarySelect.addEventListener('change', this.onPrimaryChange.bind(this), false);
            // Trigger initial change to populate secondaries on load (if selection exists)
            this.initializeSelector(() => this.onPrimaryChange());
        }

        if (secondarySelect) {
            secondarySelect.addEventListener('change', this.onSecondaryChange.bind(this), false);
            // Trigger initial load of family names if secondary already has a value
            this.initializeSelector(() => this.onSecondaryChange());
        }
    }

    /**
     * Initialize selector with deferred execution using ES2024 Promise.withResolvers
     * Enhanced with error handling and timeout protection
     */
    private initializeSelector(callback: () => void): void {
        const { promise, resolve, reject } = Promise.withResolvers<void>();
        
        // Schedule callback with timeout protection
        const timeoutId = setTimeout(() => {
            reject(new Error('Selector initialization timeout', { 
                cause: 'DOM not ready within expected timeframe' 
            }));
        }, 5000);
        
        // Schedule callback to run after current execution stack
        setTimeout(() => {
            clearTimeout(timeoutId);
            resolve();
        }, 0);
        
        promise
            .then(() => callback())
            .catch(error => {
                console.error('Selector initialization failed:', error);
                // Fallback: try to execute callback anyway
                try {
                    callback();
                } catch (callbackError) {
                    console.error('Callback execution failed:', callbackError);
                }
            });
    }

    /**
     * Populate a <select> element with options from an object { key: value, ... }
     */
    private populateSelect(
        selectEl: HTMLSelectElement | null,
        items: Record<string, string> | string[] | null,
        promptText?: string
    ): void {
        if (!selectEl) return;

        // Clear existing options
        selectEl.innerHTML = '';

        // Add prompt/none option
        const promptOption = document.createElement('option');
        promptOption.value = '';
        promptOption.textContent = promptText || 'None';
        selectEl.appendChild(promptOption);

        if (!items) return;

        // Handle both array and object formats
        if (Array.isArray(items)) {
            items.forEach((value, index) => {
                const option = document.createElement('option');
                option.value = index.toString();
                option.textContent = value;
                selectEl.appendChild(option);
            });
        } else {
            Object.entries(items).forEach(([key, value]) => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = value;
                selectEl.appendChild(option);
            });
        }
    }

    /**
     * Handler: when primary category changes, load secondary categories
     */
    private async onPrimaryChange(): Promise<void> {
        const primarySelect = document.getElementById(
            'family-category-primary-id'
        ) as HTMLSelectElement;
        if (!primarySelect) return;

        const primaryCategoryId = primarySelect.value || '';
        const url = `${location.origin}/invoice/family/secondaries/${encodeURIComponent(primaryCategoryId)}`;

        try {
            const payload: FamilySecondaryRequest = {
                category_primary_id: primaryCategoryId,
            };

            const response = await getJson<FamilySecondaryResponse>(url, payload);
            const data = parsedata(response) as FamilySecondaryResponse;

            if (data.success === 1) {
                const secondaryCategories = data.secondary_categories || {};
                const secondaryDropdown = document.getElementById(
                    'family-category-secondary-id'
                ) as HTMLSelectElement;
                this.populateSelect(secondaryDropdown, secondaryCategories, 'None');

                // Trigger change on secondary to cascade populate family names
                if (secondaryDropdown) {
                    const changeEvent = new Event('change', { bubbles: true });
                    secondaryDropdown.dispatchEvent(changeEvent);
                }
            } else {
                // In failure case, clear secondary and family name selects
                this.populateSelect(
                    document.getElementById('family-category-secondary-id') as HTMLSelectElement,
                    {},
                    'None'
                );
                this.populateSelect(
                    document.getElementById('family-name') as HTMLSelectElement,
                    {},
                    'None'
                );
            }
        } catch (error) {
            console.error('Error loading secondary categories', error);
            // Clear selects on error
            this.populateSelect(
                document.getElementById('family-category-secondary-id') as HTMLSelectElement,
                {},
                'None'
            );
            this.populateSelect(
                document.getElementById('family-name') as HTMLSelectElement,
                {},
                'None'
            );
        }
    }

    /**
     * Handler: when secondary category changes, load family names
     */
    private async onSecondaryChange(): Promise<void> {
        const secondarySelect = document.getElementById(
            'family-category-secondary-id'
        ) as HTMLSelectElement;
        if (!secondarySelect) return;

        const secondaryCategoryId = secondarySelect.value || '';
        const url = `${location.origin}/invoice/family/names/${encodeURIComponent(secondaryCategoryId)}`;

        try {
            const payload: FamilyNamesRequest = {
                category_secondary_id: secondaryCategoryId,
            };

            const response = await getJson<FamilyNamesResponse>(url, payload);
            const data = parsedata(response) as FamilyNamesResponse;

            if (data.success === 1) {
                const familyNames = data.family_names || {};
                const familyNameDropdown = document.getElementById(
                    'family-name'
                ) as HTMLSelectElement;
                this.populateSelect(familyNameDropdown, familyNames, 'None');
            } else {
                this.populateSelect(
                    document.getElementById('family-name') as HTMLSelectElement,
                    {},
                    'None'
                );
            }
        } catch (error) {
            console.error('Error loading family names', error);
            // Clear family names select on error
            this.populateSelect(
                document.getElementById('family-name') as HTMLSelectElement,
                {},
                'None'
            );
        }
    }



    /**
     * Get family data from checked checkboxes
     */
    private getFamilyDataFromCheckedBoxes(): FamilyData[] {
        const families: FamilyData[] = [];
        const checkedBoxes = document.querySelectorAll<HTMLInputElement>('input[name="family_ids[]"]:checked');
        
        checkedBoxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            if (!row) return;
            
            const familyId = checkbox.value;
            const familyNameCell = row.querySelector('[data-family-name]') as HTMLElement;
            const familyPrefixCell = row.querySelector('[data-family-prefix]') as HTMLElement;
            const familyCommaListCell = row.querySelector('[data-family-commalist]') as HTMLElement;
            
            const familyData: FamilyData = {
                family_id: familyId,
                family_name: familyNameCell?.textContent?.trim() || '',
                family_productprefix: familyPrefixCell?.textContent?.trim() || '',
                family_commalist: familyCommaListCell?.textContent?.trim() || ''
            };
            
            families.push(familyData);
        });
        
        return families;
    }



    /**
     * Process product generation
     */
    private async processProductGeneration(): Promise<void> {
        const taxRateSelect = document.getElementById('tax_rate_id') as HTMLSelectElement;
        const unitSelect = document.getElementById('unit_id') as HTMLSelectElement;
        const processBtn = document.getElementById('process-generate-products') as HTMLButtonElement;
        
        if (!taxRateSelect?.value || !unitSelect?.value) {
            alert('Please select both tax rate and unit.');
            return;
        }

        const selectedFamilies = this.getFamilyDataFromCheckedBoxes();
        if (selectedFamilies.length === 0) {
            alert('No families selected.');
            return;
        }

        // Show loading state
        const originalText = processBtn.innerHTML;
        processBtn.innerHTML = '<i class="fa fa-spin fa-spinner"></i> Generating...';
        processBtn.disabled = true;

        try {
            const familyIds = selectedFamilies.map(f => f.family_id);
            const csrfToken = (document.querySelector('input[name="_csrf"]') as HTMLInputElement)?.value || '';

            // Build URL-encoded body manually so family_ids[] are sent as repeated keys
            const params = new URLSearchParams();
            familyIds.forEach(id => params.append('family_ids[]', id));
            params.append('tax_rate_id', taxRateSelect.value);
            params.append('unit_id', unitSelect.value);
            params.append('_csrf', csrfToken);

            const response = await fetch(`${location.origin}/invoice/family/generateProducts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: params.toString()
            });

            const rawText = await response.text();
            console.log('[generateProducts] HTTP status:', response.status);
            console.log('[generateProducts] raw response:', rawText);

            let data: FamilyGenerateResponse;
            try {
                const parsed = JSON.parse(rawText);
                // Handle double-encoded JSON (server wrapped JSON string in another JSON string)
                data = (typeof parsed === 'string' ? JSON.parse(parsed) : parsed) as FamilyGenerateResponse;
            } catch (parseError) {
                alert('Server returned non-JSON response (HTTP ' + response.status + '). Check PHP error log.');
                processBtn.innerHTML = originalText;
                processBtn.disabled = false;
                return;
            }

            if (data.success) {
                processBtn.innerHTML = '<i class="fa fa-check"></i> Success!';
                alert(data.message || `Successfully generated ${data.count || 0} products!`);
                
                if (data.warnings && data.warnings.length > 0) {
                    console.warn('Warnings during product generation:', data.warnings);
                }

                // Close modal then redirect or reload
                const modal = document.getElementById('generate-products-modal');
                if (modal && typeof (window as any).bootstrap?.Modal !== 'undefined') {
                    const bsModal = (window as any).bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                }
                setTimeout(() => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        window.location.reload();
                    }
                }, 1000);
            } else {
                processBtn.innerHTML = originalText;
                processBtn.disabled = false;
                alert(`Error: ${data.message || 'Unknown error occurred'}`);
            }
        } catch (error) {
            console.error('Product generation error:', error);
            processBtn.innerHTML = originalText;
            processBtn.disabled = false;
            alert('An error occurred while generating products. Please try again.');
        }
    }


}
