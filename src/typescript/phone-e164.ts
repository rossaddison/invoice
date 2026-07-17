import { parsePhoneNumberFromString } from 'libphonenumber-js';
import type { CountryCode } from 'libphonenumber-js';

/**
 * Normalizes any input[data-e164] field to E.164 on blur (e.g. "07700 900000"
 * -> "+447700900000"), using the paired data-e164-default-region attribute
 * (an ISO 3166-1 alpha-2 code, server-computed from the form's country
 * field) to resolve national-format numbers. Shared by the client and
 * company forms rather than duplicated per form.
 *
 * Delegated on the document (blur doesn't bubble, hence the capture-phase
 * listener) so it also covers fields added to the DOM after page load.
 */
export function initE164PhoneFields(): void {
    document.addEventListener(
        'blur',
        (e: FocusEvent) => {
            const target = e.target;
            if (target instanceof HTMLInputElement && target.hasAttribute('data-e164')) {
                applyE164(target);
            }
        },
        true,
    );
}

function applyE164(field: HTMLInputElement): void {
    const raw = field.value.trim();
    const feedbackId = `${field.id}-e164-feedback`;

    if (raw === '') {
        field.classList.remove('is-invalid');
        document.getElementById(feedbackId)?.remove();
        return;
    }

    const region = field.dataset['e164DefaultRegion'];
    const parsed = parsePhoneNumberFromString(raw, (region || undefined) as CountryCode | undefined);

    if (parsed?.isValid()) {
        field.value = parsed.number;
        field.classList.remove('is-invalid');
        document.getElementById(feedbackId)?.remove();
        return;
    }

    field.classList.add('is-invalid');
    if (!document.getElementById(feedbackId)) {
        const msg = document.createElement('div');
        msg.id = feedbackId;
        msg.className = 'invalid-feedback d-block';
        msg.textContent = 'Enter a valid phone number, including country code (e.g. +447700900000).';
        field.after(msg);
    }
}
