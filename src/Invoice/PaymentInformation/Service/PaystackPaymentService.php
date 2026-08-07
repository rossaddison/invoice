<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Paystack — Africa's major payment gateway (Nigeria, Ghana, South Africa,
 * Kenya, Côte d'Ivoire, Egypt, Rwanda), this app's first Africa-region
 * gateway. Built as a direct HTTP integration — no third-party SDK.
 *
 * Paystack's own GitHub org (`PaystackHQ/paystack-php`) turned out to just
 * be a stale mirror of the community `yabacon/paystack-php` package, last
 * pushed in 2017 — not a genuinely first-party, actively-maintained SDK.
 * `yabacon/paystack-php` itself (more recently touched, 2023, but still a
 * small community package, 116 stars) was read directly for research
 * purposes only, not installed as a dependency, per this project's general
 * small-third-party-package caution (same reasoning as Robokassa/YooKassa).
 *
 * Ground-truthed directly from that package's real executable source
 * (not just its README):
 *
 * - Base URL `https://api.paystack.co`
 *   (`src/Paystack/Helpers/Router.php::PAYSTACK_API_ROOT`).
 * - Auth: `Authorization: Bearer {secretKey}` (secretKey starts with `sk_`)
 *   (`src/Paystack/Http/RequestBuilder.php::build()`).
 * - Paths `POST /transaction/initialize`, `GET /transaction/verify/{reference}`
 *   (`src/Paystack/Routes/Transaction.php`).
 * - Response envelope `{status: bool, message: string, data: {...}}`, POST
 *   bodies sent as plain `json_encode()` with no client-side param
 *   allowlisting (`src/Paystack/Http/Response.php`,
 *   `RequestBuilder::packagePayload()`).
 * - Webhook signature: `X-Paystack-Signature` = `hash_hmac('sha512',
 *   rawBody, secretKey)` — see PaystackSignatureService's docblock, which
 *   cites the SDK's own `Event::validFor()` method verbatim.
 *
 * **Not independently re-confirmed against Paystack's own primary docs**
 * this session — `paystack.com/docs` returned HTTP 403 to every fetch
 * attempt (bot-protection), and this integration cannot be tested against
 * a real account either way (Paystack requires business registration
 * details to sign up, the same practical barrier already hit with
 * YooKassa). The `/refund` endpoint specifically (`transaction`, `amount`
 * body fields) isn't covered by the SDK read above at all — it's built
 * from Paystack's well-established, consistently-documented public API
 * shape, not a primary-source confirmation. Treat this integration as
 * unverified against a live account, same as Robokassa/YooKassa.
 *
 * Amount is always sent in the smallest currency subunit (kobo for NGN,
 * cents for USD/ZAR/KES, etc. — all of Paystack's supported currencies use
 * a 2-decimal-place subunit), matching the `(int) round($amount * 100)`
 * pattern already used throughout this app for Stripe/Adyen/GoCardless.
 *
 * Paystack requires a customer email address to initialize a transaction —
 * unlike most other gateways in this app, there is no way to omit it.
 */
final class PaystackPaymentService implements PaymentGatewayInterface
{
    private const string BASE_URL = 'https://api.paystack.co';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'paystack';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->secretKey() !== '';
    }

    /**
     * Initializes a transaction (POST /transaction/initialize) and returns
     * the reference this app generates (used for later verify/refund calls)
     * alongside the hosted `authorization_url` to redirect the customer to.
     * Returns null on any failure, logging the detail for a maintainer.
     *
     * @param array<string, string> $metadata Round-tripped verbatim onto the transaction and its webhook notification — this is how the webhook finds the right invoice.
     * @return array{reference: string, authorizationUrl: string}|null
     */
    public function createPayment(float $amount, string $email, string $callbackUrl, array $metadata = []): ?array
    {
        if ($email === '') {
            $this->logger->error('Paystack createPayment: customer email is required but missing.');
            return null;
        }

        $reference = 'inv-' . Uuid::uuid4()->toString();
        $body = [
            'amount' => (int) round($amount * 100),
            'email' => $email,
            // Nigeria is Paystack's home market and NGN its default
            // settlement currency; matches this app's convention of a
            // gateway-appropriate fallback (RUB for YooKassa, EUR for
            // Mollie) when currency_code hasn't been explicitly set.
            'currency' => strtoupper($this->settings->getSetting('currency_code') ?: 'NGN'),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ];

        try {
            $response = $this->httpClient->post(self::BASE_URL . '/transaction/initialize', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);
            return $this->parseCreatePaymentResponse($response, $reference);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Paystack createPayment failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return array{reference: string, authorizationUrl: string}|null
     */
    private function parseCreatePaymentResponse(ResponseInterface $response, string $reference): ?array
    {
        /** @var array{status?: bool, message?: string, data?: array{authorization_url?: string, reference?: string}} $data */
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (($data['status'] ?? false) !== true) {
            $this->logger->error('Paystack createPayment rejected.', ['message' => $data['message'] ?? 'unknown error']);
            return null;
        }
        $authorizationUrl = $data['data']['authorization_url'] ?? null;
        if ($authorizationUrl === null) {
            return null;
        }

        return ['reference' => $reference, 'authorizationUrl' => $authorizationUrl];
    }

    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $response = $this->httpClient->get(
                self::BASE_URL . '/transaction/verify/' . rawurlencode($providerReference),
                ['headers' => ['Authorization' => 'Bearer ' . $this->secretKey()]],
            );
            /** @var array{status?: bool, message?: string, data?: array{status?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $transactionStatus = $data['data']['status'] ?? '';

            return new PaymentVerificationResult($transactionStatus === 'success', $providerReference, $transactionStatus);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('Paystack verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }
    }

    /**
     * Requests a full refund via POST /refund. Not covered by the SDK
     * source this class otherwise cites — built from Paystack's
     * well-established public API documentation for this endpoint, not an
     * independently re-confirmed primary source this session (see this
     * class's own docblock).
     */
    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $body = [
            'transaction' => $providerReference,
            'amount' => (int) round($amount * 100),
        ];

        try {
            $response = $this->httpClient->post(self::BASE_URL . '/refund', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'http_errors' => false,
            ]);
            /** @var array{status?: bool, message?: string, data?: array{status?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (($data['status'] ?? false) !== true) {
                return new PaymentRefundResult(false, $providerReference, $data['message'] ?? 'Paystack refund request rejected.');
            }

            $refundStatus = $data['data']['status'] ?? '';
            return new PaymentRefundResult(
                in_array($refundStatus, ['pending', 'processed'], true),
                $providerReference,
                $refundStatus,
            );
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Paystack refund failed.', ['error' => $e->getMessage()]);
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }
    }

    private function secretKey(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_paystack_secretKey') ?: '');
    }
}
