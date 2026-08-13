import { beforeEach, describe, expect, it } from 'vitest';
import { initE164PhoneFields } from './phone-e164.js';

describe('initE164PhoneFields', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        initE164PhoneFields();
    });

    it.each([
        {
            description: 'reformats a UK national number to E.164 on blur, using the default region',
            html: '<input id="client_mobile" data-e164 data-e164-default-region="GB" value="07726232648">',
            id: 'client_mobile',
            expectedValue: '+447726232648',
        },
        {
            description: 'accepts an already-international number with no default region set',
            html: '<input id="phone" data-e164 data-e164-default-region="" value="+447726232648">',
            id: 'phone',
            expectedValue: '+447726232648',
        },
        {
            description: 'does not touch inputs without data-e164',
            html: '<input id="client_web" value="not-a-phone-number">',
            id: 'client_web',
            expectedValue: 'not-a-phone-number',
        },
    ])('$description', ({ html, id, expectedValue }) => {
        document.body.innerHTML = html;
        const input = document.getElementById(id) as HTMLInputElement;

        input.dispatchEvent(new FocusEvent('blur'));

        expect(input.value).toBe(expectedValue);
        expect(input.classList.contains('is-invalid')).toBe(false);
    });

    it('marks the field invalid and adds feedback for an unparseable number', () => {
        document.body.innerHTML = '<input id="client_mobile" data-e164 data-e164-default-region="GB" value="abc">';
        const input = document.getElementById('client_mobile') as HTMLInputElement;

        input.dispatchEvent(new FocusEvent('blur'));

        expect(input.classList.contains('is-invalid')).toBe(true);
        expect(document.getElementById('client_mobile-e164-feedback')).not.toBeNull();
    });

    it('clears invalid state and feedback once the field is emptied', () => {
        document.body.innerHTML = '<input id="client_mobile" data-e164 data-e164-default-region="GB" value="abc">';
        const input = document.getElementById('client_mobile') as HTMLInputElement;

        input.dispatchEvent(new FocusEvent('blur'));
        expect(input.classList.contains('is-invalid')).toBe(true);

        input.value = '';
        input.dispatchEvent(new FocusEvent('blur'));

        expect(input.classList.contains('is-invalid')).toBe(false);
        expect(document.getElementById('client_mobile-e164-feedback')).toBeNull();
    });
});
