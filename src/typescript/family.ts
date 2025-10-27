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

// Family handler class
export class FamilyHandler {
    constructor() {
        this.bindEventListeners();
    }

    private bindEventListeners(): void {
        document.addEventListener('DOMContentLoaded', this.initializeSelectors.bind(this));
    }

    private initializeSelectors(): void {
        const primarySelect = document.getElementById('family-category-primary-id') as HTMLSelectElement;
        const secondarySelect = document.getElementById('family-category-secondary-id') as HTMLSelectElement;

        if (primarySelect) {
            primarySelect.addEventListener('change', this.onPrimaryChange.bind(this), false);
            // Trigger initial change to populate secondaries on load (if selection exists)
            Promise.resolve().then(() => this.onPrimaryChange());
        }

        if (secondarySelect) {
            secondarySelect.addEventListener('change', this.onSecondaryChange.bind(this), false);
            // Trigger initial load of family names if secondary already has a value
            Promise.resolve().then(() => this.onSecondaryChange());
        }
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
        const primarySelect = document.getElementById('family-category-primary-id') as HTMLSelectElement;
        if (!primarySelect) return;

        const primaryCategoryId = primarySelect.value || '';
        const url = `${location.origin}/invoice/family/secondaries/${encodeURIComponent(primaryCategoryId)}`;

        try {
            const payload: FamilySecondaryRequest = {
                category_primary_id: primaryCategoryId
            };

            const response = await getJson<FamilySecondaryResponse>(url, payload);
            const data = parsedata(response) as FamilySecondaryResponse;

            if (data.success === 1) {
                const secondaryCategories = data.secondary_categories || {};
                const secondaryDropdown = document.getElementById('family-category-secondary-id') as HTMLSelectElement;
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
        const secondarySelect = document.getElementById('family-category-secondary-id') as HTMLSelectElement;
        if (!secondarySelect) return;

        const secondaryCategoryId = secondarySelect.value || '';
        const url = `${location.origin}/invoice/family/names/${encodeURIComponent(secondaryCategoryId)}`;

        try {
            const payload: FamilyNamesRequest = {
                category_secondary_id: secondaryCategoryId
            };

            const response = await getJson<FamilyNamesResponse>(url, payload);
            const data = parsedata(response) as FamilyNamesResponse;

            if (data.success === 1) {
                const familyNames = data.family_names || {};
                const familyNameDropdown = document.getElementById('family-name') as HTMLSelectElement;
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
}