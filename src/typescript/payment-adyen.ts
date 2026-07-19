/**
 * Adyen Payment Module
 *
 * Reads server-supplied values from a hidden #adyen-payment-config <div>
 * rendered by payment_information_adyen_pci.php (data-* attributes), then
 * initialises AdyenCheckout and mounts its Drop-in component.
 *
 * The Adyen Web SDK is loaded globally via AdyenAsset
 * (checkoutshopper-test.adyen.com/checkoutshopper/sdk/.../adyen.js).
 *
 * Unlike payment-stripe.ts, there is no client-side submit handler here —
 * Drop-in handles payment submission entirely itself and calls Adyen's own
 * servers directly; the actual outcome is confirmed server-side by the
 * AUTHORISATION webhook, not anything read from this page.
 */

// ---------------------------------------------------------------------------
// Ambient declarations for the Adyen Web SDK (v5, AdyenCheckout is async)
// ---------------------------------------------------------------------------
declare global {
    interface AdyenCheckoutSession {
        id: string;
        sessionData: string;
    }
    interface AdyenCheckoutResult {
        resultCode?: string;
    }
    interface AdyenDropinComponent {
        mount(selector: string): AdyenDropinComponent;
    }
    interface AdyenCheckoutInstance {
        create(type: 'dropin'): AdyenDropinComponent;
    }
    interface AdyenCheckoutConfig {
        environment: string;
        clientKey: string;
        session: AdyenCheckoutSession;
        onPaymentCompleted?: (result: AdyenCheckoutResult, component: unknown) => void;
        onError?: (error: { name?: string; message?: string }, component: unknown) => void;
    }
    // The global AdyenCheckout() factory injected by the CDN bundle
    const AdyenCheckout: (config: AdyenCheckoutConfig) => Promise<AdyenCheckoutInstance>;
}

// ---------------------------------------------------------------------------
// Exported initialiser — called once from index.ts on DOMContentLoaded
// ---------------------------------------------------------------------------
export function initAdyenPayment(): void {
    const configEl = document.getElementById('adyen-payment-config');
    if (!configEl) return; // not an Adyen payment page

    const clientKey   = configEl.dataset.clientKey   ?? '';
    const sessionId   = configEl.dataset.sessionId   ?? '';
    const sessionData = configEl.dataset.sessionData ?? '';
    const environment = configEl.dataset.environment ?? 'test';

    if (!clientKey || !sessionId || !sessionData) return;

    void AdyenCheckout({
        environment,
        clientKey,
        session: { id: sessionId, sessionData },
        onError: (error) => {
            const messageContainer = document.querySelector<HTMLElement>('#dropin-container');
            if (messageContainer) {
                messageContainer.setAttribute('data-adyen-error', error.message ?? 'Unknown error');
            }
        },
    }).then((checkout) => {
        checkout.create('dropin').mount('#dropin-container');
    });
}
