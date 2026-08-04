<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Invoice\AppConstants;
use Yiisoft\Translator\TranslatorInterface;

trait SettingPaymentTrait
{

    public function activePaymentGateways(): array
    {
        return [
            // Below are listed online dashboard tested PCI COMPLIANT
            //  i.e. credit card details not stored on server, Payment Gateways
            'Adyen' => [
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
            ],
            'Amazon_Pay' => [
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
            ],
            // https://sandbox.braintreegateway.com/merchants
            'Braintree' => [
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
            ],
            'GoCardless' => [
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
            ],
            'Mollie' => [
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
            ],
            'Open_Banking_With_Wonderful' => [
                'apiToken' => [
                    'type' => 'password',
                    'label' => 'API Token',
                ],
            ],
            'Open_Banking_With_Tink' => [
                'clientId' => [
                    'type' => 'password',
                    'label' => 'Client Id',
                ],
                'clientSecret' => [
                    'type' => 'password',
                    'label' => 'Client Secret',
                ],
            ],
            // Russia/CIS Central Asia. Per Robokassa's own OpenAPI spec
            // (docs.robokassa.ru/openapi/robokassa.yaml), both the Invoice
            // API and OpStateExt are explicitly production-mode-only
            // (testSupported: false) — Robokassa's IsTest flag only applies
            // to the legacy Merchant/Index.aspx redirect scheme, which this
            // integration deliberately doesn't use. So there is exactly one
            // credential set, no separate sandbox/test passwords.
            'Robokassa' => [
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
            ],
            'StoreCove' => [
                'apiKey' => [
                    'type' => 'password',
                    'label' => AppConstants::LABEL_API_KEY,
                ],
            ],
            'Stripe' => [
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
