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
// Ambient declarations for the Adyen Web SDK (v6). Confirmed against the
// real CDN UMD bundle: the global is window.AdyenWeb (v5 was
// window.AdyenCheckout directly), and Drop-in creation is now
// `new AdyenWeb.Dropin(checkout, config)` rather than
// `checkout.create('dropin')`.
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
    interface AdyenCheckoutConfig {
        environment: string;
        clientKey: string;
        countryCode: string;
        session: AdyenCheckoutSession;
        onPaymentCompleted?: (result: AdyenCheckoutResult, component: unknown) => void;
        onPaymentFailed?: (result: AdyenCheckoutResult, component: unknown) => void;
        onError?: (error: { name?: string; message?: string }, component: unknown) => void;
    }
    interface AdyenCheckoutInstance {
        // Present for interface conformance; component creation goes
        // through the Dropin constructor in v6, not this method.
    }
    interface AdyenWebNamespace {
        AdyenCheckout(config: AdyenCheckoutConfig): Promise<AdyenCheckoutInstance>;
        Dropin: new (
            checkout: AdyenCheckoutInstance,
            config?: Record<string, unknown>,
        ) => AdyenDropinComponent;
    }
    // eslint-disable-next-line no-var
    var AdyenWeb: AdyenWebNamespace;
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
    const countryCode = configEl.dataset.countryCode ?? '';

    if (!clientKey || !sessionId || !sessionData || !countryCode) return;

    void globalThis.AdyenWeb.AdyenCheckout({
        environment,
        clientKey,
        countryCode,
        session: { id: sessionId, sessionData },
        onError: (error) => {
            const messageContainer = document.querySelector<HTMLElement>('#dropin-container');
            if (messageContainer) {
                messageContainer.dataset.adyenError = error.message ?? 'Unknown error';
            }
        },
    }).then((checkout) => {
        new globalThis.AdyenWeb.Dropin(checkout).mount('#dropin-container');
    });
}
