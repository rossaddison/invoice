/**
 * Requires a product prefix whenever a comma list is provided on the Family form.
 *
 * Extracted from an inline <script> in resources/views/invoice/family/_form.php
 * so script-src no longer needs 'unsafe-inline'. No-ops on pages without a
 * #FamilyForm element.
 */
export function initFamilyFormValidation(): void {
    const form = document.getElementById('FamilyForm');
    const commalistEl = document.getElementById('family_commalist') as HTMLTextAreaElement | null;
    const prefixEl = document.getElementById('family_productprefix') as HTMLInputElement | null;
    if (!form || !commalistEl || !prefixEl) return;

    form.addEventListener('submit', (e: Event) => {
        const commalist = commalistEl.value.trim();
        const prefix = prefixEl.value.trim();
        if (commalist !== '' && prefix === '') {
            e.preventDefault();
            prefixEl.focus();
            prefixEl.classList.add('is-invalid');
            if (!document.getElementById('prefix-required-feedback')) {
                const msg = document.createElement('div');
                msg.id = 'prefix-required-feedback';
                msg.className = 'invalid-feedback d-block';
                msg.textContent = 'Product prefix is required when a comma list is provided.';
                prefixEl.insertAdjacentElement('afterend', msg);
            }
        } else {
            prefixEl.classList.remove('is-invalid');
            document.getElementById('prefix-required-feedback')?.remove();
        }
    });

    prefixEl.addEventListener('input', () => {
        if (prefixEl.value.trim() !== '') {
            prefixEl.classList.remove('is-invalid');
            document.getElementById('prefix-required-feedback')?.remove();
        }
    });
}
