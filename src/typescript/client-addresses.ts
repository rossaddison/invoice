// Client form (resources/views/invoice/client/_form.php) address helpers:
//  - "Same as billing address" shows/hides the delivery (postal address) fields
//  - search box narrowing the Dwelling (service address) dropdown
//  - "Use service address as billing address" copy button

export interface DwellingData {
    street: string;
    number: string;
    flat: string;
    postcode: string;
}

export type DwellingMap = Record<string, DwellingData>;

const DELIVERY_ANCHOR = '#postaladdress_field';

export function parseDwellings(raw: string | undefined): DwellingMap {
    if (!raw) {
        return {};
    }
    try {
        const parsed: unknown = JSON.parse(raw);
        return parsed !== null && typeof parsed === 'object' ? (parsed as DwellingMap) : {};
    } catch {
        return {};
    }
}

export function filterOptions(select: HTMLSelectElement, term: string): void {
    const needle = term.trim().toLowerCase();
    for (const option of Array.from(select.options)) {
        // The empty "none" option and the current selection always stay.
        const keep = option.value === '' || option.selected || option.text.toLowerCase().includes(needle);
        option.hidden = !keep;
    }
}

function fieldByName(name: string): HTMLInputElement | null {
    return document.querySelector<HTMLInputElement>(`[name="${name}"]`);
}

function initSameAsBilling(): void {
    const checkbox = document.getElementById('client_delivery_same_as_billing') as HTMLInputElement | null;
    const fields = document.getElementById('client-delivery-fields');
    if (!checkbox || !fields) {
        return;
    }
    // The "fix it now" link from a missing-postal-address Peppol error lands
    // on #postaladdress_field, which must be visible to be useful.
    if (globalThis.location.hash === DELIVERY_ANCHOR) {
        checkbox.checked = false;
    }
    const sync = (): void => {
        fields.classList.toggle('d-none', checkbox.checked);
    };
    checkbox.addEventListener('change', sync);
    sync();
}

function describe(data: DwellingData): string {
    const parts = [data.flat, data.number, data.street, data.postcode].filter(p => p !== '');
    return parts.join(', ');
}

function initDwellingPicker(): void {
    const picker = document.getElementById('client-dwelling-picker');
    const select = document.getElementById('dwelling_id') as HTMLSelectElement | null;
    if (!picker || !select) {
        return;
    }
    const dwellings = parseDwellings(picker.dataset['dwellings']);
    const summary = document.getElementById('client_dwelling_summary');
    const copyButton = document.getElementById('client_use_service_address') as HTMLButtonElement | null;

    const refresh = (): void => {
        const data = dwellings[select.value];
        if (summary) {
            summary.textContent = data ? describe(data) : '';
        }
        if (copyButton) {
            copyButton.disabled = !data;
        }
    };

    document.getElementById('client_dwelling_search')?.addEventListener('input', (event) => {
        filterOptions(select, (event.target as HTMLInputElement).value);
    });
    select.addEventListener('change', refresh);

    copyButton?.addEventListener('click', () => {
        const data = dwellings[select.value];
        if (!data) {
            return;
        }
        const street = fieldByName('client_address_1');
        const building = fieldByName('client_building_number');
        const zip = fieldByName('client_zip');
        if (street) { street.value = (data.flat === '' ? '' : data.flat + ', ') + data.street; }
        if (building) { building.value = data.number; }
        if (zip) { zip.value = data.postcode; }
    });
    refresh();
}

export function initClientAddresses(): void {
    initSameAsBilling();
    initDwellingPicker();
}
