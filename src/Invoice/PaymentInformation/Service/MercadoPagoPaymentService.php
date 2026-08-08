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
 * Mercado Pago — this app's first South-America-priority payment gateway,
 * the dominant checkout provider across Brazil, Argentina, Mexico, Chile,
 * Colombia, Peru, and Uruguay (tied to Mercado Livre, the region's largest
 * marketplace). Built as a direct HTTP integration against Mercado Pago's
 * **Checkout Pro / Preferences** API (`POST /checkout/preferences`, a hosted
 * checkout page you redirect the customer to), the same redirect-based shape
 * as Razorpay's Payment Links / Square's Payment Links — chosen for the same
 * reason: zero client-side JS integration needed on this app's own pages.
 *
 * Mercado Pago's own official PHP SDK, `mercadopago/sdk-php`
 * (github.com/mercadopago/sdk-php), is genuinely first-party and actively
 * maintained. It was deliberately **not installed** as a composer dependency
 * here, though: its HTTP transport is a bespoke `CurlRequest` class
 * (`src/MercadoPago/Net/CurlRequest.php`), not the Guzzle client every other
 * gateway in this app is built and tested against, and it ships no
 * mockable test double for that transport. Rather than add a second HTTP
 * client dependency for one gateway, this integration is built as direct
 * Guzzle HTTP calls instead — every URL, field name, and formula below is
 * still ground-truthed directly against that SDK's real executable source
 * (`Client/MercadoPagoClient.php`, `Client/Preference/PreferenceClient.php`,
 * `Client/Payment/PaymentClient.php`, `Client/Payment/PaymentRefundClient.php`,
 * `Resources/Preference.php`, `Resources/Payment.php`), plus Mercado Pago's
 * own current docs where reachable — not guessed.
 *
 * Confirmed from the SDK source:
 * - Base URL `https://api.mercadopago.com` (`MercadoPagoConfig::$BASE_URL`).
 * - Auth: `Authorization: Bearer {access_token}` — a single credential,
 *   unlike Razorpay's key id + secret pair
 *   (`MercadoPagoClient::getAccessToken()`).
 * - `POST /checkout/preferences` (`PreferenceClient::create()`) returns
 *   `id`, `init_point` (live checkout URL), and `sandbox_init_point` (test
 *   checkout URL) — which one gets used is controlled by this gateway's own
 *   `sandbox` setting, same convention as Adyen/Razorpay.
 * - `GET /v1/payments/{id}` (`PaymentClient::get()`) returns `status`
 *   (`"approved"` on success — `Resources/Payment.php`'s own field comment)
 *   and `external_reference`, echoing back whatever was set on the
 *   preference — this is how `MercadoPagoWebhookHandler` correlates a
 *   payment back to this app's invoice `url_key`.
 * - `POST /v1/payments/{id}/refunds` (`PaymentRefundClient::refund()`) with
 *   `{"amount": <float>}` in the body.
 *
 * Amount is a plain decimal float throughout (`Preference\Item::$unit_price`,
 * `Payment::$transaction_amount`, and the refund request are all typed
 * `?float`) — unlike Stripe/Razorpay/Square, Mercado Pago does NOT use
 * integer subunits (cents/paise). Matches the plain-float pattern already
 * used for Mollie/Robokassa/YooKassa.
 */
final class MercadoPagoPaymentService implements PaymentGatewayInterface
{
    private const string BASE_URL = 'https://api.mercadopago.com';
    private const string BEARER_PREFIX = 'Bearer ';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'mercado_pago';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->accessToken() !== '';
    }

    /**
     * Creates a Preference (`POST /checkout/preferences`) and returns its own
     * `id` (the provider reference used for the later authenticated
     * `GET /v1/payments/{id}` re-confirmation happens against the *payment*
     * id instead — see MercadoPagoWebhookHandler, since a preference and the
     * payment eventually made against it are different resources) alongside
     * the hosted checkout URL to redirect the customer to.
     * `$metadata['invoiceUrlKey']` becomes Mercado Pago's own first-class
     * `external_reference` field, echoed back verbatim on the resulting
     * Payment resource — this is how the webhook finds the right invoice.
     * Returns null on any failure, logging the detail for a maintainer.
     *
     * `$notificationUrl` is Mercado Pago's own per-preference webhook
     * config (`Preference::$notification_url`) — computed by the caller via
     * `UrlGenerator`, the same way `$callbackUrl` already is, rather than a
     * manually-entered Setting duplicating a value this app already knows.
     * Unlike every other gateway here, Mercado Pago lets the webhook
     * destination be set programmatically per-request instead of only via
     * a one-time dashboard configuration step.
     *
     * @param array<string, string> $metadata `invoiceUrlKey` is promoted to `external_reference`; unused otherwise (Mercado Pago's Preference has no generic metadata bag the way Razorpay's `notes` does).
     * @return array{preferenceId: string, checkoutUrl: string}|null
     */
    public function createPayment(float $amount, string $description, string $callbackUrl, string $notificationUrl, array $metadata = []): ?array
    {
        $invoiceUrlKey = $metadata['invoiceUrlKey'] ?? '';
        $body = [
            'items' => [[
                'title' => $description,
                'quantity' => 1,
                'unit_price' => $amount,
                'currency_id' => strtoupper($this->settings->getSetting('currency_code') ?: 'BRL'),
            ]],
            'external_reference' => $invoiceUrlKey,
            'back_urls' => [
                'success' => $callbackUrl,
                'pending' => $callbackUrl,
                'failure' => $callbackUrl,
            ],
            'notification_url' => $notificationUrl,
            // Auto-redirects the buyer back to back_urls.success once
            // payment is approved, instead of leaving them on Mercado
            // Pago's own confirmation page to click through manually.
            'auto_return' => 'approved',
        ];

        try {
            $response = $this->httpClient->post(self::BASE_URL . '/checkout/preferences', [
                'headers' => [
                    'Authorization' => self::BEARER_PREFIX . $this->accessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);
            /** @var array{id?: string, init_point?: string, sandbox_init_point?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $preferenceId = $data['id'] ?? null;
            $checkoutUrl = $this->isSandbox() ? ($data['sandbox_init_point'] ?? null) : ($data['init_point'] ?? null);
            if (null === $preferenceId || null === $checkoutUrl) {
                $this->logger->error('Mercado Pago createPayment: response missing id or checkout URL.', [
                    'sandbox' => $this->isSandbox(),
                ]);
                return null;
            }

            return ['preferenceId' => $preferenceId, 'checkoutUrl' => $checkoutUrl];
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Mercado Pago createPayment failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * `$providerReference` here must be the Mercado Pago **payment** id
     * (from the webhook's `data.id`), not the preference id — a preference
     * has no `status` of its own; only the payment eventually made against
     * it does (`PaymentClient::get()` / `Resources/Payment.php::$status`).
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        $details = $this->fetchPaymentDetails($providerReference);
        if (null === $details) {
            return new PaymentVerificationResult(false, $providerReference, 'Mercado Pago payment lookup failed.');
        }

        return new PaymentVerificationResult($details['status'] === 'approved', $providerReference, $details['status']);
    }

    /**
     * `GET /v1/payments/{id}`, returning both `status` and
     * `external_reference` in one call — unlike Razorpay/Stripe, Mercado
     * Pago's webhook notification body carries only `data.id`, never the
     * invoice reference itself, so MercadoPagoWebhookHandler needs this
     * combined lookup rather than the shared PaymentVerificationResult
     * (a value type this app's PaymentGatewayInterface contract keeps
     * gateway-agnostic, deliberately not extended just for this one
     * gateway's shape).
     *
     * @return array{status: string, externalReference: string}|null
     */
    public function fetchPaymentDetails(string $paymentId): ?array
    {
        try {
            $response = $this->httpClient->get(self::BASE_URL . '/v1/payments/' . rawurlencode($paymentId), [
                'headers' => ['Authorization' => self::BEARER_PREFIX . $this->accessToken()],
            ]);
            /** @var array{status?: string, external_reference?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return [
                'status' => $data['status'] ?? '',
                'externalReference' => $data['external_reference'] ?? '',
            ];
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('Mercado Pago fetchPaymentDetails failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Requests a refund via `POST /v1/payments/{id}/refunds`.
     * `$providerReference` must be the payment id — same value
     * `verifyPayment()` takes, unlike Razorpay where the payment link id and
     * refund-eligible payment id are two different values.
     */
    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        try {
            $response = $this->httpClient->post(
                self::BASE_URL . '/v1/payments/' . rawurlencode($providerReference) . '/refunds',
                [
                    'headers' => [
                        'Authorization' => self::BEARER_PREFIX . $this->accessToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => ['amount' => $amount],
                    'http_errors' => false,
                ],
            );
            /** @var array{id?: int, status?: string, message?: string, error?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($data['error'])) {
                return new PaymentRefundResult(false, $providerReference, $data['message'] ?? 'Mercado Pago refund request rejected.');
            }

            $refundId = isset($data['id']) ? (string) $data['id'] : '';
            // A successful refund response has no explicit `status: approved`
            // field of its own the way a Payment does — Mercado Pago's own
            // docs confirm a 2xx/201 response with an `id` present IS the
            // success signal for this endpoint.
            return new PaymentRefundResult(
                $refundId !== '',
                $refundId !== '' ? $refundId : $providerReference,
                $data['status'] ?? 'refunded',
            );
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Mercado Pago refund failed.', ['error' => $e->getMessage()]);
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }
    }

    private function accessToken(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_mercado_pago_accessToken') ?: '');
    }

    private function isSandbox(): bool
    {
        return $this->settings->getSetting('gateway_mercado_pago_sandbox') === '1';
    }
}
