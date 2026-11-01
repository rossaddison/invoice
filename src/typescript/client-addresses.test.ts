import { describe, it, expect, beforeEach } from 'vitest';
import { filterOptions, initClientAddresses, parseDwellings } from './client-addresses.js';

const DWELLINGS = {
    '1': { street: 'High Street', number: '12A', flat: '', postcode: 'SW1A1AA' },
    '2': { street: 'Low Road', number: '3', flat: 'Flat 2', postcode: 'EC1A1BB' },
};

function mount(): void {
    document.body.innerHTML = `
        <input name="client_address_1" value="">
        <input name="client_building_number" value="">
        <input name="client_zip" value="">
        <input type="checkbox" id="client_delivery_same_as_billing" checked>
        <div id="client-delivery-fields"></div>
        <div id="client-dwelling-picker" data-dwellings='${JSON.stringify(DWELLINGS)}'>
            <input id="client_dwelling_search">
            <select id="dwelling_id">
                <option value="">None</option>
                <option value="1">12A, High Street, SW1A1AA</option>
                <option value="2">Flat 2, 3, Low Road, EC1A1BB</option>
            </select>
            <div id="client_dwelling_summary"></div>
            <button id="client_use_service_address" type="button"></button>
        </div>`;
}

describe('parseDwellings', () => {
    it('returns an empty map for missing or invalid JSON', () => {
        expect(parseDwellings(undefined)).toEqual({});
        expect(parseDwellings('not json')).toEqual({});
        expect(parseDwellings('"string"')).toEqual({});
    });

    it('parses a dwelling map', () => {
        expect(parseDwellings(JSON.stringify(DWELLINGS))).toEqual(DWELLINGS);
    });
});

describe('client address form', () => {
    beforeEach(() => {
        mount();
        globalThis.location.hash = '';
    });

    it('filters dwelling options but keeps "none" and the selection', () => {
        const select = document.getElementById('dwelling_id') as HTMLSelectElement;
        select.value = '2';
        filterOptions(select, 'high');
        expect(select.options[0].hidden).toBe(false);
        expect(select.options[1].hidden).toBe(false);
        expect(select.options[2].hidden).toBe(false);
        select.value = '';
        filterOptions(select, 'high');
        expect(select.options[1].hidden).toBe(false);
        expect(select.options[2].hidden).toBe(true);
    });

    it('hides the delivery fields while "same as billing" is ticked', () => {
        initClientAddresses();
        const fields = document.getElementById('client-delivery-fields') as HTMLElement;
        const checkbox = document.getElementById('client_delivery_same_as_billing') as HTMLInputElement;
        expect(fields.classList.contains('d-none')).toBe(true);
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
        expect(fields.classList.contains('d-none')).toBe(false);
    });

    it('keeps the delivery fields visible when opened on the postal address anchor', () => {
        globalThis.location.hash = '#postaladdress_field';
        initClientAddresses();
        const fields = document.getElementById('client-delivery-fields') as HTMLElement;
        expect(fields.classList.contains('d-none')).toBe(false);
    });

    it('copies the selected service address into the billing fields', () => {
        initClientAddresses();
        const select = document.getElementById('dwelling_id') as HTMLSelectElement;
        select.value = '2';
        select.dispatchEvent(new Event('change'));
        (document.getElementById('client_use_service_address') as HTMLButtonElement).click();
        expect((document.querySelector('[name="client_address_1"]') as HTMLInputElement).value).toBe('Flat 2, Low Road');
        expect((document.querySelector('[name="client_building_number"]') as HTMLInputElement).value).toBe('3');
        expect((document.querySelector('[name="client_zip"]') as HTMLInputElement).value).toBe('EC1A1BB');
        expect(document.getElementById('client_dwelling_summary')?.textContent).toBe('Flat 2, 3, Low Road, EC1A1BB');
    });
});
