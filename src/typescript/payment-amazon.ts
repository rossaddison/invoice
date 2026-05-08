/**
 * Amazon Pay Module
 *
 * Reads server-supplied values from a hidden #amazon-pay-config <div>
 * rendered by payment_information_amazon_pci.php (data-* attributes), then
 * calls amazon.Pay.renderButton() to inject the Amazon Pay button.
 *
 * The Amazon Pay SDK is loaded globally via amazon_pay_v2_7_Asset
 * (https://static-eu.payments-amazon.com/checkout.js).
 */

// ---------------------------------------------------------------------------
// Ambient declarations for the Amazon Pay browser SDK
// ---------------------------------------------------------------------------
declare global {
    interface AmazonPayEstimatedOrderAmount {
        amount: string;
        currencyCode: string;
    }
    interface AmazonPayCheckoutSessionConfig {
        payloadJSON: string;
        signature: string;
    }
    interface AmazonPayButtonConfig {
        merchantId: string;
        publicKeyId: string;
        ledgerCurrency: string;
        checkoutLanguage: string;
        productType: string;
        placement: string;
        buttonColor: string;
        estimatedOrderAmount: AmazonPayEstimatedOrderAmount;
        createCheckoutSessionConfig: AmazonPayCheckoutSessionConfig;
    }
    const amazon: {
        Pay: {
            renderButton(selector: string, config: AmazonPayButtonConfig): unknown;
        };
    };
}

// ---------------------------------------------------------------------------
// Exported initialiser — called once from index.ts on DOMContentLoaded
// ---------------------------------------------------------------------------
export function initAmazonPayment(): void {
    const configEl = document.getElementById('amazon-pay-config');
    if (!configEl) return; // not an Amazon Pay page

    const merchantId       = configEl.dataset.merchantId       ?? '';
    const publicKeyId      = configEl.dataset.publicKeyId      ?? '';
    const ledgerCurrency   = configEl.dataset.ledgerCurrency   ?? '';
    const checkoutLanguage = configEl.dataset.checkoutLanguage ?? '';
    const productType      = configEl.dataset.productType      ?? '';
    const amount           = configEl.dataset.amount           ?? '';
    const payloadJSON      = configEl.dataset.payloadJson      ?? '';
    const signature        = configEl.dataset.signature        ?? '';

    if (!merchantId || !publicKeyId) return;

    amazon.Pay.renderButton('#AmazonPayButton', {
        merchantId,
        publicKeyId,
        ledgerCurrency,
        checkoutLanguage,
        productType,
        placement: 'Other',
        buttonColor: 'Gold',
        estimatedOrderAmount: { amount, currencyCode: ledgerCurrency },
        createCheckoutSessionConfig: { payloadJSON, signature },
    });
}
