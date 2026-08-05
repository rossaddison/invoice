<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Razorpay — this app's first India-region payment gateway. Built as a
 * direct HTTP integration against Razorpay's **Payment Links** API (a
 * hosted checkout page you redirect the customer to), not its Orders +
 * embedded-JS-Checkout-widget flow that most Razorpay integrations use —
 * chosen specifically because Payment Links match this app's existing
 * redirect-based pattern (Robokassa/YooKassa/Paystack/Mollie/GoCardless),
 * with zero client-side JS integration needed on this app's own pages.
 *
 * Razorpay's own official PHP SDK, `razorpay/razorpay` (github.com/razorpay/
 * razorpay-php), is genuinely first-party and actively maintained (pushed to
 * 2026-07-23, not archived) — unlike Robokassa/Paystack's thin/unofficial
 * community packages. It was deliberately **not installed** as a composer
 * dependency here, though: its HTTP layer is built on `rmccue/requests`, a
 * different HTTP client entirely from the Guzzle client every other gateway
 * in this app is built and tested against (via
 * `GuzzleHttp\Handler\MockHandler`), and it ships no fake/mock client
 * equivalent to Mollie's `Mollie\Api\Fake\MockMollieClient` for testing
 * without one. Rather than add a second HTTP-client dependency for a single
 * gateway, this integration is built as direct Guzzle HTTP calls instead —
 * every URL, field name, and formula below is still ground-truthed directly
 * against that SDK's real executable source (`src/Api.php`, `src/Request.php`,
 * `src/Entity.php`, `src/PaymentLink.php`, `src/Refund.php`,
 * `src/Utility.php`), plus Razorpay's own current API docs where reachable
 * (see per-method notes) — not guessed.
 *
 * Confirmed from the SDK source:
 * - Base URL `https://api.razorpay.com` (`Api::$baseUrl`).
 * - Auth: HTTP Basic, `key_id:key_secret` (`Request::request()`'s
 *   `$options['auth']` — NOT a Bearer token, unlike Paystack/Stripe).
 * - `Entity::getEntityUrl()` builds `{snake_case_plural_class_name}/` —
 *   confirming `POST /v1/payment_links`, `GET /v1/payment_links/{id}`, and
 *   (via `Payment::refund()` → `new Refund` → `Entity::create()`)
 *   `POST /v1/refunds` with `payment_id` in the body.
 *
 * Confirmed from Razorpay's own current API docs
 * (`razorpay.com/docs/api/payments/payment-links/create-standard/`, fetched
 * this session): the create response's `id`, `short_url` (the hosted
 * checkout URL to redirect the customer to), and initial `status: "created"`
 * fields.
 *
 * Amount is always sent in the smallest currency subunit (paise for INR),
 * matching the `(int) round($amount * 100)` pattern used throughout this app.
 */
final class RazorpayPaymentService implements PaymentGatewayInterface
{
    private const string BASE_URL = 'https://api.razorpay.com/v1';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'razorpay';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->keyId() !== '' && $this->keySecret() !== '';
    }

    /**
     * Creates a Payment Link (POST /v1/payment_links) and returns its own
     * `id` (the provider reference used for the later authenticated
     * `GET /v1/payment_links/{id}` re-confirmation — see
     * RazorpayWebhookHandler) alongside the hosted `short_url` to redirect
     * the customer to. `$metadata['invoiceUrlKey']` becomes Razorpay's own
     * first-class `reference_id` field (rather than being buried in `notes`
     * like most other gateways' metadata bags) — this is how the webhook
     * finds the right invoice, since `reference_id` is echoed back verbatim
     * in both the payment_link.paid webhook payload and the callback
     * signature payload. Returns null on any failure, logging the detail
     * for a maintainer.
     *
     * @param array<string, string> $metadata Round-tripped as Razorpay's `notes` object; `invoiceUrlKey` is additionally promoted to `reference_id`.
     * @return array{paymentLinkId: string, paymentLinkUrl: string}|null
     */
    public function createPayment(float $amount, string $description, string $callbackUrl, array $metadata = []): ?array
    {
        $invoiceUrlKey = $metadata['invoiceUrlKey'] ?? '';
        $body = [
            'amount' => (int) round($amount * 100),
            'currency' => strtoupper($this->settings->getSetting('currency_code') ?: 'INR'),
            'description' => $description,
            'reference_id' => $invoiceUrlKey,
            'callback_url' => $callbackUrl,
            'callback_method' => 'get',
            'notes' => $metadata,
        ];

        try {
            $response = $this->httpClient->post(self::BASE_URL . '/payment_links', [
                'auth' => [$this->keyId(), $this->keySecret()],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $body,
            ]);
            /** @var array{id?: string, short_url?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $paymentLinkId = $data['id'] ?? null;
            $shortUrl = $data['short_url'] ?? null;
            if (null === $paymentLinkId || null === $shortUrl) {
                $this->logger->error('Razorpay createPayment: response missing id or short_url.');
                return null;
            }

            return ['paymentLinkId' => $paymentLinkId, 'paymentLinkUrl' => $shortUrl];
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Razorpay createPayment failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $response = $this->httpClient->get(self::BASE_URL . '/payment_links/' . rawurlencode($providerReference), [
                'auth' => [$this->keyId(), $this->keySecret()],
            ]);
            /** @var array{status?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $status = $data['status'] ?? '';

            return new PaymentVerificationResult($status === 'paid', $providerReference, $status);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('Razorpay verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }
    }

    /**
     * Requests a refund via POST /v1/refunds. Unlike `verifyPayment()`,
     * `$providerReference` here must be the underlying Razorpay **payment**
     * id (`pay_...`), not the payment link id — refunds are per-payment, not
     * per-link, per `Payment::refund()`'s own source (it merges `payment_id`
     * into the request body before creating a `Refund` entity). The webhook
     * handler stores the payment id (not the payment link id) as the
     * Merchant audit record's provider_reference specifically so this works.
     */
    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $body = [
            'payment_id' => $providerReference,
            'amount' => (int) round($amount * 100),
        ];

        try {
            $response = $this->httpClient->post(self::BASE_URL . '/refunds', [
                'auth' => [$this->keyId(), $this->keySecret()],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $body,
                'http_errors' => false,
            ]);
            /** @var array{id?: string, status?: string, error?: array{description?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($data['error'])) {
                return new PaymentRefundResult(false, $providerReference, $data['error']['description'] ?? 'Razorpay refund request rejected.');
            }

            $refundId = $data['id'] ?? '';
            $status = $data['status'] ?? '';
            // Razorpay refunds are created in 'processed' status for instant
            // refunds, or 'pending' while a normal-speed refund settles.
            return new PaymentRefundResult(
                in_array($status, ['pending', 'processed'], true),
                $refundId !== '' ? $refundId : $providerReference,
                $status,
            );
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Razorpay refund failed.', ['error' => $e->getMessage()]);
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }
    }

    private function keyId(): string
    {
        return $this->settings->getSetting('gateway_razorpay_keyId') ?: '';
    }

    private function keySecret(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_razorpay_keySecret') ?: '');
    }
}
