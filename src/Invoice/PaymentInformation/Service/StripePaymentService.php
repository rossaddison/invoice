<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\Libraries\Crypt;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Invoice\Entity\Inv;

class StripePaymentService
{
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly Crypt $crypt,
        private string $salt,
    ) {
        $this->salt = (new Crypt())->salt();
        $this->setApiKey();
    }

    private function setApiKey(): void
    {
        $secretKeySetting = $this->settings->getSetting('gateway_stripe_secretKey');

        $sk_test = (string) $this->crypt->decode($secretKeySetting);
        if (!empty($sk_test)) {
            Stripe::setApiKey($sk_test);
        }
    }

    public function getPublishableKey(): string
    {
        $publishableKey = $this->settings->getSetting('gateway_stripe_publishableKey');

        return (string) $this->crypt->decode($publishableKey ?: '');
    }

    public function createPaymentIntent(array $invoiceData): ?string
    {
        $payment_intent = PaymentIntent::create([
            // convert the float amount to cents
            'amount' => (int) round(((float) $invoiceData['balance'] ?: 0.00) * 100),
            'currency' => (string) $invoiceData['currency'],
            // include the payment methods you have chosen listed in dashboard.stripe.com eg. card, bacs direct debit,
            // googlepay etc.
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            //'customer' => $invoiceData['customer'],
            //'description' => $invoiceData['description'],
            'receipt_email' => (string) $invoiceData['customer_email'],
            'metadata' => [
                'invoice_id' => (string) $invoiceData['id'],
                'invoice_customer_id' => (string) $invoiceData['customer_id'],
                'invoice_number' => (string) $invoiceData['number'] ?: '',
                'invoice_payment_method' => '',
                'invoice_url_key' => (string) $invoiceData['url_key'],
            ],
        ]);
        return $payment_intent->client_secret;
    }

    /**
     * Checks the payment status from Stripe's redirect parameters.
     */
    public function handleCompletion(Inv $invoice, string $redirectStatus): array
    {
        $result = [
            'status_id' => null,
            'payment_method' => null,
            'success' => false,
            'message' => '',
        ];

        if ($redirectStatus === 'succeeded') {
            $result['status_id'] = 4; // paid
            $result['payment_method'] = 4;
            $result['success'] = true;
            $result['message'] = 'Payment successful';
        } elseif ($redirectStatus === 'requires_payment_method') {
            $result['status_id'] = 3; // viewed
            $result['payment_method'] = 5;
            $result['success'] = false;
            $result['message'] = 'Requires a payment method';
        } else {
            $result['status_id'] = 3;
            $result['payment_method'] = 5;
            $result['success'] = false;
            $result['message'] = 'Payment failed';
        }

        return $result;
    }
}
