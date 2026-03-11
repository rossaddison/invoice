/**
 * Braintree Drop-in Payment Module
 *
 * Reads the client token from a hidden #braintree-config <div> rendered by
 * payment_information_braintree_pci.php (data-client-token attribute), then
 * initialises the Braintree Drop-in UI and wires the payment form.
 *
 * The Braintree Drop-in SDK is loaded globally via braintree_dropin_1_33_7_Asset
 * (//js.braintreegateway.com/web/dropin/1.33.7/js/dropin.min.js).
 */

// ---------------------------------------------------------------------------
// Ambient declarations for the Braintree Drop-in browser SDK
// ---------------------------------------------------------------------------
declare global {
    interface BraintreeDropinPayload {
        nonce: string;
    }
    interface BraintreeDropinInstance {
        requestPaymentMethod(
            callback: (err: Error | null, payload: BraintreeDropinPayload | null) => void,
        ): void;
    }
    const braintree: {
        dropin: {
            create(
                options: { authorization: string; container: string },
                callback: (
                    error: Error | null,
                    instance: BraintreeDropinInstance | null,
                ) => void,
            ): void;
        };
    };
}

// ---------------------------------------------------------------------------
// Exported initialiser — called once from index.ts on DOMContentLoaded
// ---------------------------------------------------------------------------
export function initBraintreePayment(): void {
    const configEl = document.getElementById('braintree-config');
    if (!configEl) return; // not a Braintree payment page

    const clientToken = configEl.dataset.clientToken ?? '';
    if (!clientToken) return;

    const form = document.getElementById('payment-form') as HTMLFormElement | null;

    braintree.dropin.create(
        { authorization: clientToken, container: '#dropin-container' },
        (error, dropinInstance) => {
            if (error) {
                console.error('Braintree Drop-in error:', error);
                return;
            }
            if (!dropinInstance || !form) return;

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                dropinInstance.requestPaymentMethod((err, payload) => {
                    if (err) {
                        console.error('Braintree requestPaymentMethod error:', err);
                        return;
                    }
                    const nonceField = document.getElementById(
                        'nonce',
                    ) as HTMLInputElement | null;
                    if (nonceField && payload) {
                        nonceField.value = payload.nonce;
                    }
                    form.submit();
                });
            });
        },
    );
}
