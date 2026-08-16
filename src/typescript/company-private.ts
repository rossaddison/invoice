/**
 * Company Private — BACS Sort Code Boxes
 *
 * Combines the three 2-digit sort-code boxes (bacs_sort_code_1/2/3)
 * rendered by CompanyPrivateFormFields::companyPrivateBacsSortCodeField()
 * into the single hidden bacs_sort_code field the form actually submits.
 * Moved out of an inline <script> in that widget (CSP script-src has no
 * 'unsafe-inline', confirmed via this app's own response headers — the
 * inline version was being silently blocked by every browser, so the
 * hidden field never updated and the sort code was never actually saved)
 * — same migration pattern as payment-adyen.ts / payment-braintree.ts /
 * bacs-quickpay.ts.
 */

export function initCompanyPrivate(): void {
    const b1 = document.getElementById('bacs_sort_code_1') as HTMLInputElement | null;
    const b2 = document.getElementById('bacs_sort_code_2') as HTMLInputElement | null;
    const b3 = document.getElementById('bacs_sort_code_3') as HTMLInputElement | null;
    const hidden = document.getElementById('bacs_sort_code') as HTMLInputElement | null;
    if (!b1 || !b2 || !b3 || !hidden) return; // not a page with this widget

    const combine = (): void => {
        hidden.value = `${b1.value}-${b2.value}-${b3.value}`;
    };

    const wire = (current: HTMLInputElement, next: HTMLInputElement | null): void => {
        current.addEventListener('input', () => {
            combine();
            if (current.value.length === 2 && next) {
                next.focus();
            }
        });
    };

    wire(b1, b2);
    wire(b2, b3);
    wire(b3, null);

    // Boxes are pre-filled server-side from the stored value (see the PHP
    // widget), but the hidden field itself starts as a plain server-rendered
    // value too — combine once up front so a save without touching any box
    // (e.g. editing an unrelated field on the same form) doesn't silently
    // wipe an already-correct stored sort code.
    combine();
}
