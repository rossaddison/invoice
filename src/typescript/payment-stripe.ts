/**
 * Stripe Payment Module
 *
 * Reads server-supplied values from a hidden #stripe-payment-config <div>
 * rendered by payment_information_stripe_pci.php (data-* attributes), then
 * initialises Stripe Elements and wires the payment form.
 *
 * The Stripe.js v3 SDK is loaded globally via stripe_v10_Asset (//js.stripe.com/v3/).
 */

// ---------------------------------------------------------------------------
// Ambient declarations for the Stripe.js v3 browser SDK
// ---------------------------------------------------------------------------
declare global {
    interface StripePaymentElementOptions {
        layout: string;
    }
    interface StripePaymentElementInstance {
        mount(selector: string): void;
    }
    interface StripeElementsInstance {
        create(
            type: 'payment',
            options: StripePaymentElementOptions,
        ): StripePaymentElementInstance;
    }
    interface StripeConfirmPaymentError {
        type: string;
        message?: string;
    }
    interface StripePaymentIntent {
        status: string;
    }
    interface StripeInstance {
        elements(options: { clientSecret: string }): StripeElementsInstance;
        confirmPayment(options: {
            elements: StripeElementsInstance;
            confirmParams: { return_url: string };
        }): Promise<{ error: StripeConfirmPaymentError }>;
        retrievePaymentIntent(
            clientSecret: string,
        ): Promise<{ paymentIntent: StripePaymentIntent }>;
    }
    // The global Stripe() constructor injected by the CDN bundle
    const Stripe: (publishableKey: string) => StripeInstance;
}

// ---------------------------------------------------------------------------
// Exported initialiser — called once from index.ts on DOMContentLoaded
// ---------------------------------------------------------------------------
export function initStripePayment(): void {
    const configEl = document.getElementById('stripe-payment-config');
    if (!configEl) return; // not a Stripe payment page

    const publishableKey = configEl.dataset.publishableKey ?? '';
    const clientSecret   = configEl.dataset.clientSecret   ?? '';
    const returnUrl      = configEl.dataset.returnUrl      ?? '';

    if (!publishableKey || !clientSecret) return;

    const stripe = Stripe(publishableKey);
    let elements: StripeElementsInstance;

    async function initialize(): Promise<void> {
        elements = stripe.elements({ clientSecret });
        const paymentElement = elements.create('payment', { layout: 'tabs' });
        paymentElement.mount('#payment-element');
    }

    async function handleSubmit(e: Event): Promise<void> {
        e.preventDefault();
        setLoading(true);
        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: { return_url: returnUrl },
        });
        if (error.type === 'card_error' || error.type === 'validation_error') {
            showMessage(error.message ?? 'An error occurred.');
        } else {
            showMessage('An unexpected error occurred.');
        }
        setLoading(false);
    }

    async function checkStatus(): Promise<void> {
        const intentSecret = new URLSearchParams(globalThis.location.search).get(
            'payment_intent_client_secret',
        );
        if (!intentSecret) return;

        const { paymentIntent } = await stripe.retrievePaymentIntent(intentSecret);
        switch (paymentIntent.status) {
            case 'succeeded':
                showMessage('Payment succeeded!');
                break;
            case 'processing':
                showMessage('Your payment is processing.');
                break;
            case 'requires_payment_method':
                showMessage('Your payment was not successful, please try again.');
                break;
            default:
                showMessage('Something went wrong.');
        }
    }

    function showMessage(messageText: string): void {
        const messageContainer =
            document.querySelector<HTMLElement>('#payment-message');
        if (!messageContainer) return;
        messageContainer.classList.remove('hidden');
        messageContainer.textContent = messageText;
        setTimeout(() => {
            messageContainer.classList.add('hidden');
            messageContainer.textContent = '';
        }, 4000);
    }

    function setLoading(isLoading: boolean): void {
        const submitBtn  = document.querySelector<HTMLButtonElement>('#submit');
        const spinner    = document.querySelector<HTMLElement>('#spinner');
        const buttonText = document.querySelector<HTMLElement>('#button-text');
        if (!submitBtn || !spinner || !buttonText) return;
        submitBtn.disabled = isLoading;
        if (isLoading) {
            spinner.classList.remove('hidden');
            buttonText.classList.add('hidden');
        } else {
            spinner.classList.add('hidden');
            buttonText.classList.remove('hidden');
        }
    }

    void initialize();
    void checkStatus();

    const form = document.querySelector<HTMLFormElement>('#payment-form');
    form?.addEventListener('submit', (e) => void handleSubmit(e));
}
