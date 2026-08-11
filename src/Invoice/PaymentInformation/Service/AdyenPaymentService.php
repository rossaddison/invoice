<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use Adyen\AdyenException;
use Adyen\Client;
use Adyen\Environment;
use Adyen\Model\Checkout\Amount;
use Adyen\Model\Checkout\CreateCheckoutSessionRequest;
use Adyen\Model\Checkout\CreateCheckoutSessionResponse;
use Adyen\Model\Checkout\PaymentRefundRequest;
use Adyen\Service\Checkout\ModificationsApi;
use Adyen\Service\Checkout\PaymentsApi;
use Adyen\Util\HmacSignature;
use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use Psr\Log\LoggerInterface;
use Yiisoft\Json\Json;

final class AdyenPaymentService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'adyen';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->apiKey() !== '' && $this->clientKey() !== '' && $this->merchantAccount() !== '';
    }

    public function getClientKey(): string
    {
        return $this->clientKey();
    }

    public function isSandbox(): bool
    {
        return $this->settings->getSetting('gateway_adyen_sandbox') === '1';
    }

    /**
     * Creates an Adyen Checkout Session (POST /sessions). The invoice's
     * url_key is stored as the session `reference`, which Adyen echoes back
     * as `merchantReference` in the AUTHORISATION webhook — the same role
     * Stripe's PaymentIntent metadata plays.
     */
    public function createSession(array $invoiceData, string $returnUrl, string $countryCode): ?CreateCheckoutSessionResponse
    {
        $amount = new Amount();
        $amount->setCurrency(strtoupper((string) $invoiceData['currency']));
        $amount->setValue((int) round(((float) $invoiceData['balance'] ?: 0.00) * 100));
        $request = new CreateCheckoutSessionRequest();
        $request->setAmount($amount);
        $request->setMerchantAccount($this->merchantAccount());
        $request->setReference((string) $invoiceData['url_key']);
        $request->setReturnUrl($returnUrl);
        $request->setCountryCode($countryCode);

        try {
            $paymentsApi = new PaymentsApi($this->buildClient());
            return $paymentsApi->sessions($request);
        } catch (AdyenException $e) {
            $this->logger->error('Adyen session creation failed.', [
                'error'           => $e->getMessage(),
                'merchantAccount' => $this->merchantAccount(),
                'sandbox'         => $this->isSandbox(),
                'apiKeyLength'    => strlen($this->apiKey()),
            ]);
            return null;
        }
    }

    /**
     * Authoritatively confirms a session's outcome by asking Adyen directly.
     * Not currently called by the webhook handler itself, which — matching
     * Adyen's own documented pattern — trusts the already-HMAC-verified
     * notification body directly; kept for interface conformance and reuse
     * elsewhere, mirroring StripePaymentService::verifyPayment().
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $paymentsApi = new PaymentsApi($this->buildClient());
            $result = $paymentsApi->getResultOfPaymentSession($providerReference);
        } catch (AdyenException $e) {
            $this->logger->warning('Adyen verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(paid: false, providerReference: $providerReference, message: $e->getMessage());
        }

        return new PaymentVerificationResult(
            paid: $result->getStatus() === 'completed',
            providerReference: $providerReference,
            message: (string) $result->getStatus(),
        );
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $refundAmount = new Amount();
        $refundAmount->setCurrency(strtoupper($this->settings->getSetting('currency_code') ?: 'GBP'));
        $refundAmount->setValue((int) round($amount * 100));

        $request = new PaymentRefundRequest();
        $request->setAmount($refundAmount);
        $request->setMerchantAccount($this->merchantAccount());
        $request->setReference($providerReference);

        try {
            $modificationsApi = new ModificationsApi($this->buildClient());
            $response = $modificationsApi->refundCapturedPayment($providerReference, $request);

            return new PaymentRefundResult(
                refunded: in_array($response->getStatus(), ['received', 'success'], true),
                providerReference: $response->getPspReference(),
                message: $response->getStatus(),
            );
        } catch (AdyenException $e) {
            $this->logger->error('Adyen refund failed.', [
                'pspReference' => $providerReference,
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
     * Verifies the HMAC signature of a standard Adyen webhook payload and
     * returns the single NotificationRequestItem as a plain array (Adyen's
     * classic webhook shape isn't a typed model in the SDK — the same shape
     * Adyen\Util\HmacSignature itself expects). Returns null on any
     * failure instead of throwing.
     */
    public function verifyWebhookNotification(string $rawBody): ?array
    {
        $hmacKey = $this->hmacKey();
        if ($hmacKey === '') {
            $this->logger->warning('Adyen webhook received but no HMAC key is configured.');
            return null;
        }

        $item = $this->extractNotificationItem($rawBody);
        if (!is_array($item)) {
            return null;
        }

        return $this->isValidNotificationHmac($hmacKey, $item) ? $item : null;
    }

    private function extractNotificationItem(string $rawBody): mixed
    {
        /** @var array{notificationItems?: array<int, array{NotificationRequestItem?: array}>} $decoded */
        $decoded = (array) Json::decode($rawBody);
        return $decoded['notificationItems'][0]['NotificationRequestItem'] ?? null;
    }

    private function isValidNotificationHmac(string $hmacKey, array $item): bool
    {
        try {
            return new HmacSignature()->isValidNotificationHMAC($hmacKey, $item);
        } catch (AdyenException $e) {
            $this->logger->warning('Adyen webhook: HMAC validation failed.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function buildClient(): Client
    {
        $client = new Client();
        $client->setXApiKey($this->apiKey());
        $client->setEnvironment($this->isSandbox() ? Environment::TEST : Environment::LIVE);
        return $client;
    }

    private function apiKey(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_adyen_apiKey') ?: '');
    }

    private function clientKey(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_adyen_clientKey') ?: '');
    }

    /**
     * Not decoded: merchantAccount is a text-type setting (see
     * SettingPaymentTrait::activePaymentGateways()), so it is never
     * encrypted on save — unlike apiKey/clientKey/webhookHmacKey, which are
     * password-type fields. Running it through decode() would corrupt it.
     */
    private function merchantAccount(): string
    {
        return $this->settings->getSetting('gateway_adyen_merchantAccount') ?: '';
    }

    private function hmacKey(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_adyen_webhookHmacKey') ?: '');
    }

    /**
     * Independently computes the currently-configured HMAC key's own KCV
     * (Key Check Value) — HMAC-SHA256("00000000", key-packed-as-binary),
     * last 3 bytes, uppercase hex — so it can be compared directly against
     * the KCV Adyen's own Customer Area displays for a webhook's HMAC key.
     *
     * This exists because "the key is present and correctly formatted" and
     * "the key is the *right* key" turned out to be two separate things
     * worth verifying independently: Adyen shows a freshly-generated HMAC
     * key's raw value exactly once, and if that value is copied but the
     * webhook's own configuration page is never explicitly saved, Adyen
     * keeps signing with whatever key was active before — silently leaving
     * this app with a well-formed but wrong key. A real live incident hit
     * exactly this; see docs/ADYEN_WEBHOOK_HMAC_KEY_NOT_SAVED_AUGUST_2026.md
     * for the full diagnosis this method now saves a future user from
     * having to redo by hand.
     *
     * Returns null when no key is configured or it isn't valid hex —
     * matching hmacKey()'s own two failure states in
     * verifyWebhookNotification(), where mirroring hex-validity here means
     * this check can never claim a KCV for a key that would fail signing
     * for an unrelated, more basic reason.
     */
    public function hmacKeyKcv(): ?string
    {
        $key = $this->hmacKey();
        if ($key === '' || !ctype_xdigit($key)) {
            return null;
        }

        $hash = hash_hmac('sha256', '00000000', pack('H*', $key), true);
        return strtoupper(bin2hex(substr($hash, -3)));
    }
}
