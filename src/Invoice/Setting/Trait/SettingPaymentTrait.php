<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Invoice\AppConstants;
use Yiisoft\Translator\TranslatorInterface;

trait SettingPaymentTrait
{

    /**
     * Below are listed online dashboard tested PCI COMPLIANT i.e. credit
     * card details not stored on server, Payment Gateways. Each gateway's
     * field config is split into its own method (php:S138 — this function
     * grew past 150 lines as gateways were added).
     */
    public function activePaymentGateways(): array
    {
        return [
            'Adyen' => $this->adyenGatewayFields(),
            'Amazon_Pay' => $this->amazonPayGatewayFields(),
            'Braintree' => $this->braintreeGatewayFields(),
            'GoCardless' => $this->goCardlessGatewayFields(),
            'Mollie' => $this->mollieGatewayFields(),
            'Open_Banking_With_Wonderful' => $this->openBankingWonderfulGatewayFields(),
            'Open_Banking_With_Tink' => $this->openBankingTinkGatewayFields(),
            'Robokassa' => $this->robokassaGatewayFields(),
            'Yookassa' => $this->yookassaGatewayFields(),
            'Paystack' => $this->paystackGatewayFields(),
            'Razorpay' => $this->razorpayGatewayFields(),
            'Paypal' => $this->paypalGatewayFields(),
            'StoreCove' => $this->storeCoveGatewayFields(),
            'Stripe' => $this->stripeGatewayFields(),
        ];
    }

    private function adyenGatewayFields(): array
    {
        return [
            'apiKey' => [
                'type' => 'password',
                'label' => AppConstants::LABEL_API_KEY,
            ],
            'clientKey' => [
                'type' => 'password',
                'label' => 'Client Key',
            ],
            'merchantAccount' => [
                'type' => 'text',
                'label' => 'Merchant Account',
            ],
            'webhookHmacKey' => [
                'type' => 'password',
                'label' => 'Webhook Hmac Key',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox',
            ],
        ];
    }

    private function amazonPayGatewayFields(): array
    {
        return [
            'publicKeyId' => [
                'type' => 'password',
                'label' => 'Public Key ID',
            ],
            'merchantId' => [
                'type' => 'password',
                'label' => 'Merchant ID',
            ],
            'clientId' => [
                'type' => 'password',
                'label' => 'Client ID',
            ],
            'clientSecret' => [
                'type' => 'password',
                'label' => 'Client Secret',
            ],
            'returnUrl' => [
                'type' => 'text',
                'label' => 'Return Url',
            ],
            'storeId' => [
                'type' => 'password',
                'label' => 'Store Id',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox',
            ],
        ];
    }

    // https://sandbox.braintreegateway.com/merchants
    private function braintreeGatewayFields(): array
    {
        return [
            'privateKey' => [
                'type' => 'password',
                'label' => AppConstants::LABEL_API_KEY,
            ],
            'publicKey' => [
                'type' => 'password',
                'label' => 'Public Key',
            ],
            'merchantId' => [
                'type' => 'password',
                'label' => 'Merchant Id',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox',
            ],
        ];
    }

    private function goCardlessGatewayFields(): array
    {
        return [
            'accessToken' => [
                'type' => 'password',
                'label' => 'Access Token',
            ],
            // signing secret for the GoCardless webhook endpoint, from
            // the GoCardless Dashboard webhook configuration
            'webhookSecret' => [
                'type' => 'password',
                'label' => 'Webhook Secret',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox',
            ],
        ];
    }

    private function mollieGatewayFields(): array
    {
        return [
            'testOrLiveApiKey' => [
                'type' => 'password',
                'label' =>
                'Test or Live Api Key i.e key starts with test_ or live_',
            ],
            'partnerID' => [
                'type' => 'text',
                'label' => 'Partner ID',
            ],
            'profileID' => [
                'type' => 'text',
                'label' => 'Profile ID',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox',
            ],
        ];
    }

    private function openBankingWonderfulGatewayFields(): array
    {
        return [
            'apiToken' => [
                'type' => 'password',
                'label' => 'API Token',
            ],
        ];
    }

    private function openBankingTinkGatewayFields(): array
    {
        return [
            'clientId' => [
                'type' => 'password',
                'label' => 'Client Id',
            ],
            'clientSecret' => [
                'type' => 'password',
                'label' => 'Client Secret',
            ],
        ];
    }

    /**
     * Russia/CIS Central Asia. Per Robokassa's own OpenAPI spec
     * (docs.robokassa.ru/openapi/robokassa.yaml), both the Invoice API and
     * OpStateExt are explicitly production-mode-only (testSupported: false)
     * — Robokassa's IsTest flag only applies to the legacy
     * Merchant/Index.aspx redirect scheme, which this integration
     * deliberately doesn't use. So there is exactly one credential set, no
     * separate sandbox/test passwords.
     */
    private function robokassaGatewayFields(): array
    {
        return [
            'login' => [
                'type' => 'text',
                'label' => 'Merchant Login',
            ],
            'password1' => [
                'type' => 'password',
                'label' => 'Password #1',
            ],
            'password2' => [
                'type' => 'password',
                'label' => 'Password #2',
            ],
            // Only issued once Robokassa support has enabled the Refund
            // API for this merchant account — a separate password from
            // Password #1/#2, used only for RefundService/Refund/Create.
            // Leave blank if refunds haven't been enabled; refund()
            // reports a clear "not configured" message instead of
            // failing silently.
            'password3' => [
                'type' => 'password',
                'label' => 'Password #3 (Refund API, optional)',
            ],
        ];
    }

    /**
     * Russia — second Asia/CIS-adjacent gateway. Unlike Robokassa, YooKassa
     * (formerly Yandex.Checkout) has a genuine sandbox: a free test shop
     * with its own shopId/secretKey, hitting the same api.yookassa.ru/v3
     * base URL as production — same pattern as Mollie's single
     * testOrLiveApiKey field, 'sandbox' here is informational only (which
     * credential set is currently pasted in), not a code branch.
     */
    private function yookassaGatewayFields(): array
    {
        return [
            'shopId' => [
                'type' => 'text',
                'label' => 'Shop ID',
            ],
            'secretKey' => [
                'type' => 'password',
                'label' => 'Secret Key',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox (test shop credentials)',
            ],
        ];
    }

    /**
     * Africa — Paystack's core markets are Nigeria, Ghana, South Africa,
     * Kenya, Côte d'Ivoire, Egypt, and Rwanda, filling a real gap this
     * app's other gateways don't cover directly (Stripe's own Africa
     * support is "extended network only", via Paystack itself — see
     * gateways.json). Single secretKey field: like Mollie/YooKassa, test
     * vs live mode is just which key prefix (sk_test_/sk_live_) is pasted
     * in, same base URL — 'sandbox' here is informational only, not a
     * code branch.
     */
    private function paystackGatewayFields(): array
    {
        return [
            'secretKey' => [
                'type' => 'password',
                'label' => 'Secret Key',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox (test secret key)',
            ],
        ];
    }

    /**
     * India — Razorpay is this app's first India-region gateway, built
     * against its Payment Links API (a hosted checkout page, unlike the
     * embedded-JS-widget Orders API most Razorpay integrations use — chosen
     * specifically because it matches this app's existing redirect-based
     * pattern, no client-side JS integration needed). keyId/keySecret are
     * genuinely different per test/live mode (rzp_test_.../rzp_live_...),
     * same base URL — 'sandbox' here is informational only, not a code
     * branch. webhookSecret is a separate secret configured when setting up
     * the webhook in the Razorpay Dashboard, distinct from keySecret (same
     * pattern as Stripe/Adyen/GoCardless's own webhookSecret field).
     */
    private function razorpayGatewayFields(): array
    {
        return [
            'keyId' => [
                'type' => 'text',
                'label' => 'Key ID',
            ],
            'keySecret' => [
                'type' => 'password',
                'label' => 'Key Secret',
            ],
            'webhookSecret' => [
                'type' => 'password',
                'label' => 'Webhook Secret',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox (test key id/secret)',
            ],
        ];
    }

    /**
     * PayPal — this app's broadest-reach gateway (200+ markets across every
     * populated continent), built against the Orders v2 REST API
     * (`POST /v2/checkout/orders` + `.../capture`), ground-truthed against
     * the official `paypal/paypal-server-sdk` (github.com/paypal/
     * PayPal-PHP-Server-SDK, actively maintained, read for research purposes
     * only — see PaypalPaymentService's own docblock for why no SDK is
     * installed here).
     *
     * Unlike every other gateway's 'sandbox' checkbox in this app — which is
     * purely informational, since Mollie/YooKassa/Paystack/Razorpay all use
     * one base URL and differ only by which credential is pasted in —
     * PayPal's sandbox genuinely IS a separate base URL
     * (`api-m.sandbox.paypal.com` vs `api-m.paypal.com`), so here 'sandbox'
     * is a real code branch, not just documentation.
     */
    private function paypalGatewayFields(): array
    {
        return [
            'clientId' => [
                'type' => 'text',
                'label' => 'Client ID',
            ],
            'clientSecret' => [
                'type' => 'password',
                'label' => 'Client Secret',
            ],
            // From the webhook's own configuration page in the PayPal
            // Developer Dashboard — required by the Verify Webhook Signature
            // API call, distinct from clientId/clientSecret.
            'webhookId' => [
                'type' => 'text',
                'label' => 'Webhook ID',
            ],
            'sandbox' => [
                'type' => 'checkbox',
                'label' => 'Sandbox (uses api-m.sandbox.paypal.com)',
            ],
        ];
    }

    private function storeCoveGatewayFields(): array
    {
        return [
            'apiKey' => [
                'type' => 'password',
                'label' => AppConstants::LABEL_API_KEY,
            ],
        ];
    }

    private function stripeGatewayFields(): array
    {
        return [
            'apiKey' => [
                'type' => 'password',
                'label' => AppConstants::LABEL_API_KEY,
            ],
            // Related logic: see src/Invoice/Language/English/gateway_lang
            // Not server-side ie. client-side
            'publishableKey' => [
                'type' => 'password',
                'label' => 'Publishable Key',
            ],
            // server-side Related logic:
            // https://dashboard.stripe.com/test/dashboard
            'secretKey' => [
                'type' => 'password',
                'label' => 'Secret Key',
            ],
            // signing secret for the /paymentinformation/stripeWebhook
            // endpoint, from the Stripe Dashboard webhook configuration
            'webhookSecret' => [
                'type' => 'password',
                'label' => 'Webhook Secret',
            ],
        ];
    }

    /**
     * @return (int|string)[]
     *
     * @psalm-return list<array-key>
     */
    public function paymentGatewaysEnabledDriverList(): array
    {
        $available_drivers = [];
        $gateways = $this->activePaymentGateways();
        foreach ($gateways as $driver => $_fields) {
            $d = strtolower((string) $driver);
            if ($this->getSetting('gateway_' . $d . '_enabled') === '1') {
                $available_drivers[] = $driver;
            }
        }
        return $available_drivers;
    }

    /**
     * @return array
     */
    public function sandboxUrlArray(): array
    {
        return [
            'stripe' => 'https://dashboard.stripe.com',
            'amazon_pay' => 'https://sellercentral-europe.amazon.com/'
            . 'external-payments/sandbox/home',
            'braintree' => 'https://sandbox.braintreegateway.com/login',
            'mollie' => 'https://my.mollie.com/dashboard/',
            'adyen' => 'https://ca-test.adyen.com',
            'gocardless' => 'https://manage-sandbox.gocardless.com',
            // Robokassa has no sandbox at all for the Invoice/OpStateExt
            // APIs this integration uses (confirmed via Robokassa's own
            // OpenAPI spec: both are testSupported: false) — this is just
            // the ordinary merchant cabinet.
            'robokassa' => 'https://partner.robokassa.ru',
            // Real test-shop dashboard, same UI as production — confirmed
            // via yookassa.ru's own developer docs.
            'yookassa' => 'https://yookassa.ru/my/',
            // Same dashboard for test and live secret keys, distinguished
            // only by which key (sk_test_/sk_live_) is configured.
            'paystack' => 'https://dashboard.paystack.com',
            // Same dashboard for test and live mode, toggled via a switch —
            // distinguished by which key id/secret (rzp_test_/rzp_live_) is
            // configured.
            'razorpay' => 'https://dashboard.razorpay.com',
            // Genuinely separate sandbox environment (its own base URL and
            // its own test buyer/business accounts), unlike every other
            // gateway above.
            'paypal' => 'https://developer.paypal.com/dashboard/accounts',
        ];
    }

    /**
     * @return array
     */
    public function getPaymentTermArray(TranslatorInterface $translator): array
    {
        return [
            $translator->translate('payment.term'),
            $translator->translate('payment.term.0.days'),
            $translator->translate('payment.term.net.15.days'),
            $translator->translate('payment.term.net.30.days'),
            $translator->translate('payment.term.net.60.days'),
            $translator->translate('payment.term.net.90.days'),
            $translator->translate('payment.term.net.120.days'),
            $translator->translate('payment.term.eom.15.days'),
            $translator->translate('payment.term.eom.30.days'),
            $translator->translate('payment.term.eom.60.days'),
            $translator->translate('payment.term.eom.90.days'),
            $translator->translate('payment.term.eom.120.days'),
            $translator->translate('payment.term.mfi.15'),
            $translator->translate('payment.term.general'),
            $translator->translate('payment.term.polite'),
            $translator->translate('payment.term.pia'),
        ];
    }

    public function mollieSupportedPaymentMethodArray(): array
    {
        // Payment methods for mollie can be selected on the dashboard 18-03-2024
        // These methods will appear on the $payment->getCheckOutUrl()
        return [
            'applepay',
            'bancontact', 'banktransfer', 'belfius',
            'creditcard',
            'directdebit',
            'eps',
            'giftcard','giropay',
            'ideal',
            'kbc',
            'mybank',
            'paypal', 'paysafecard', 'przelewy24',
            'sofort',
        ];
    }

    public function mollieSupportedLocaleArray(): array
    {
        return [
            'en_US',
            'en_GB',
            'nl_NL',
            'nl_BE',
            'fr_FR',
            'fr_BE',
            'de_DE',
            'de_AT',
            'de_CH',
            'es_ES',
            'ca_ES',
            'pt_PT',
            'it_IT',
            'nb_NO',
            'sv_SE',
            'fi_FI',
            'da_DK',
            'is_IS',
            'hu_HU',
            'pl_PL',
            'lv_LV',
            'lt_LT',
        ];
    }
}
