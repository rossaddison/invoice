<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;
use App\Invoice\PaymentInformation\PaymentRefundResult;

class StripePaymentService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
    ) {
        $this->setApiKey();
    }

    private function setApiKey(): void
    {
        $secretKeySetting = $this->settings->getSetting('gateway_stripe_secretKey');

        $skTest = (string) $this->settings->decode($secretKeySetting);
        if (!empty($skTest)) {
            Stripe::setApiKey($skTest);
        }
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'stripe';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        $secretKey = (string) $this->settings->decode(
            $this->settings->getSetting('gateway_stripe_secretKey') ?: '');
        $publishableKey = $this->getPublishableKey();

        return $secretKey !== '' && $publishableKey !== '';
    }

    public function getPublishableKey(): string
    {
        $publishableKey = $this->settings->getSetting('gateway_stripe_publishableKey');

        return (string) $this->settings->decode($publishableKey ?: '');
    }

    public function createPaymentIntent(array $invoiceData): ?string
    {
        $paymentIntent = PaymentIntent::create([
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
        return $paymentIntent->client_secret;
    }

    /**
     * Authoritatively confirms a PaymentIntent's status by asking Stripe
     * directly, rather than trusting a client-supplied redirect parameter.
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        $paymentIntent = PaymentIntent::retrieve($providerReference);

        return new PaymentVerificationResult(
            paid: $paymentIntent->status === 'succeeded',
            providerReference: $providerReference,
            message: $paymentIntent->status,
        );
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        try {
            $refund = Refund::create([
                'payment_intent' => $providerReference,
                'amount' => (int) round($amount * 100),
            ]);

            return new PaymentRefundResult(
                refunded: $refund->status === 'succeeded' || $refund->status === 'pending',
                providerReference: $refund->id,
                message: (string) $refund->status,
            );
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe refund failed.', [
                'payment_intent' => $providerReference,
                'error' => $e->getMessage(),
            ]);
            return new PaymentRefundResult(
                refunded: false,
                providerReference: $providerReference,
                message: $e->getMessage(),
            );
        }
    }

    /**
     * Verifies the `Stripe-Signature` header against the raw request body
     * using the configured webhook secret. Returns null (and logs) on any
     * failure instead of throwing, so callers can respond with a plain 400.
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): ?Event
    {
        $webhookSecret = (string) $this->settings->decode(
            $this->settings->getSetting('gateway_stripe_webhookSecret') ?: '');

        if ($webhookSecret === '') {
            $this->logger->warning('Stripe webhook received but no webhook secret is configured.');
            return null;
        }

        try {
            return \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (UnexpectedValueException $e) {
            $this->logger->warning('Stripe webhook: invalid payload.', ['error' => $e->getMessage()]);
        } catch (SignatureVerificationException $e) {
            $this->logger->warning('Stripe webhook: signature verification failed.', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
