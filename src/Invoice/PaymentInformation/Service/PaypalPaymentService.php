<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * PayPal — this app's broadest-reach gateway (PayPal itself operates in
 * 200+ markets across every populated continent). Built against the Orders
 * v2 REST API's redirect flow (`POST /v2/checkout/orders`, hosted "approve"
 * page, `POST /v2/checkout/orders/{id}/capture`) — not PayPal's JS
 * Checkout SDK, which would need client-side integration this app's other
 * gateways don't require.
 *
 * PayPal's own official PHP SDK, `paypal/paypal-server-sdk`
 * (github.com/paypal/PayPal-PHP-Server-SDK), is genuinely first-party and
 * actively maintained (pushed 2026-06-05, not archived — its two
 * predecessors, `paypal/PayPal-PHP-SDK` and `paypal/Checkout-PHP-SDK`, are
 * both archived/deprecated). It was deliberately **not installed** as a
 * composer dependency here, though, for the same reason as Razorpay: its
 * HTTP layer is APIMatic-generated code built on `apimatic/unirest-php`, a
 * different HTTP client from the Guzzle client every other gateway in this
 * app is built and tested against, with no mockable test double for it. It
 * was read directly from GitHub for research purposes only, to
 * ground-truth every URL/field/formula below against real executable code.
 *
 * Confirmed directly from that SDK's source
 * (`Authentication/ClientCredentialsAuthManager.php`,
 * `Controllers/OAuthAuthorizationController.php`,
 * `Controllers/OrdersController.php`, `Controllers/PaymentsController.php`):
 * - OAuth2 token: `POST /v1/oauth2/token`, `Authorization: Basic
 *   base64(clientId:clientSecret)`, form body `grant_type=client_credentials`
 *   — a **fresh token is fetched for every operation** below rather than
 *   cached, since this app's services are stateless per-request objects
 *   with nowhere natural to persist a ~9-hour-lived token between requests.
 * - `POST /v2/checkout/orders`, `POST /v2/checkout/orders/{id}/capture`,
 *   `GET /v2/payments/captures/{id}`, `POST
 *   /v2/payments/captures/{id}/refund`.
 *
 * Confirmed directly against PayPal's own current developer docs (reachable
 * this session, unlike Paystack's): base URLs
 * `https://api-m.paypal.com` (live) / `https://api-m.sandbox.paypal.com`
 * (sandbox — see this class's own `baseUrl()`; unlike every other gateway in
 * this app, PayPal's sandbox setting really is a different base URL, not
 * just a different credential against the same one);
 * `POST /v1/notifications/verify-webhook-signature`'s request shape
 * (`transmission_id`, `transmission_time`, `cert_url`, `auth_algo`,
 * `transmission_sig`, `webhook_id`, `webhook_event`); the
 * `PAYMENT.CAPTURE.COMPLETED` webhook event name; and the capture
 * resource's `invoice_id` field (`developer.paypal.com/docs/api/payments/
 * v2/#captures_get`) — this is what carries this app's own invoice url_key
 * through to the webhook, set as `purchase_units[].invoice_id` at order
 * creation.
 *
 * **Not independently re-confirmed against primary docs this session**: the
 * exact `verification_status: "SUCCESS"` response field/value from
 * `verify-webhook-signature` — the fetched docs page showed the request
 * shape but not a sample response. Based on well-established,
 * consistently-documented general public knowledge instead.
 *
 * Amount is a decimal string (not a subunit integer, unlike every other
 * gateway in this app) — PayPal's Orders v2 API takes `amount.value` as a
 * plain 2-decimal-place string, e.g. `"99.50"`.
 */
final class PaypalPaymentService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'paypal';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    /**
     * Creates an order (POST /v2/checkout/orders) and returns its `id`
     * (the provider reference used for `captureOrder()`) alongside the
     * hosted `approve` link to redirect the customer to. Returns null on
     * any failure, logging the detail for a maintainer.
     *
     * @param array<string, string> $metadata `invoiceUrlKey` becomes the order's `invoice_id` — this is how the webhook finds the right invoice.
     * @return array{orderId: string, approveUrl: string}|null
     */
    public function createPayment(
        float $amount,
        string $description,
        string $returnUrl,
        string $cancelUrl,
        array $metadata = [],
    ): ?array {
        $token = $this->accessToken();
        if (null === $token) {
            return null;
        }

        $invoiceUrlKey = $metadata['invoiceUrlKey'] ?? '';
        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => strtoupper($this->settings->getSetting('currency_code') ?: 'USD'),
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'invoice_id' => $invoiceUrlKey,
                'description' => $description,
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'user_action' => 'PAY_NOW',
            ],
        ];

        try {
            $response = $this->httpClient->post($this->baseUrl() . '/v2/checkout/orders', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);
            return $this->parseCreatePaymentResponse($response);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('PayPal createPayment failed.', $this->errorLogContext($e));
            return null;
        }
    }

    /**
     * @return array{orderId: string, approveUrl: string}|null
     */
    private function parseCreatePaymentResponse(ResponseInterface $response): ?array
    {
        /** @var array{id?: string, links?: list<array{rel?: string, href?: string}>} $data */
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $orderId = $data['id'] ?? null;
        $approveUrl = null;
        foreach ($data['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approveUrl = $link['href'] ?? null;
                break;
            }
        }
        if (null === $orderId || null === $approveUrl) {
            $this->logger->error('PayPal createPayment: response missing id or approve link.');
            return null;
        }

        return ['orderId' => $orderId, 'approveUrl' => $approveUrl];
    }

    /**
     * Captures a previously approved order (POST
     * /v2/checkout/orders/{id}/capture) — this is the call that actually
     * moves money; PayPal does not do this automatically once the customer
     * approves on its hosted page. Called from `PaypalPaymentController::
     * paypalComplete()` when the customer's browser returns with a
     * `PayerID` query param (the standard signal they approved rather than
     * cancelled). This is a required side effect of finalizing the
     * transaction, not a "trust the client redirect" shortcut — the
     * response comes from PayPal's own API, authenticated with this app's
     * own credentials, so it's authoritative for this specific call.
     * Marking the invoice paid in this app's own database is still left
     * entirely to `PaypalWebhookHandler`, matching the belt-and-braces
     * convention every other gateway in this app follows.
     *
     * @return array{captureId: string, status: string}|null
     */
    public function captureOrder(string $orderId): ?array
    {
        $token = $this->accessToken();
        if (null === $token) {
            return null;
        }

        try {
            $response = $this->httpClient->post(
                $this->baseUrl() . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ],
                    'http_errors' => false,
                ],
            );
            return $this->parseCaptureOrderResponse($response, $orderId);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('PayPal captureOrder failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return array{captureId: string, status: string}|null
     */
    private function parseCaptureOrderResponse(ResponseInterface $response, string $orderId): ?array
    {
        $rawBody = (string) $response->getBody();

        // http_errors is false on this call (see captureOrder()) so a
        // rejected capture (e.g. DUPLICATE_INVOICE_ID, ORDER_NOT_APPROVED)
        // arrives here as a normal response with a 4xx/5xx status, not an
        // exception — the body is PayPal's error shape, not a capture
        // shape, so it must be handled before attempting to read
        // purchase_units below.
        if ($response->getStatusCode() >= 400) {
            $this->logger->error('PayPal captureOrder rejected by PayPal.', [
                'order_id' => $orderId,
                'http_status' => $response->getStatusCode(),
                ...$this->extractErrorDetail($rawBody),
            ]);
            return null;
        }

        /** @var array{purchase_units?: list<array{payments?: array{captures?: list<array{id?: string, status?: string}>}}>} $data */
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        $capture = $data['purchase_units'][0]['payments']['captures'][0] ?? null;
        $captureId = $capture['id'] ?? null;
        $status = $capture['status'] ?? null;
        if (null === $captureId || null === $status) {
            $this->logger->warning('PayPal captureOrder: response missing capture id or status.', ['order_id' => $orderId]);
            return null;
        }

        return ['captureId' => $captureId, 'status' => $status];
    }

    /**
     * Re-confirms a capture's status via GET /v2/payments/captures/{id} —
     * the mandatory belt-and-braces step PaypalWebhookHandler takes before
     * ever trusting a PAYMENT.CAPTURE.COMPLETED notification enough to mark
     * an invoice paid.
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        $token = $this->accessToken();
        if (null === $token) {
            return new PaymentVerificationResult(false, $providerReference, 'unable to obtain an access token');
        }

        try {
            $response = $this->httpClient->get(
                $this->baseUrl() . '/v2/payments/captures/' . rawurlencode($providerReference),
                ['headers' => ['Authorization' => 'Bearer ' . $token]],
            );
            /** @var array{status?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $status = $data['status'] ?? '';

            return new PaymentVerificationResult($status === 'COMPLETED', $providerReference, $status);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('PayPal verifyPayment failed.', $this->errorLogContext($e));
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $token = $this->accessToken();
        if (null === $token) {
            return new PaymentRefundResult(false, $providerReference, 'unable to obtain an access token');
        }

        $body = [
            'amount' => [
                'currency_code' => strtoupper($this->settings->getSetting('currency_code') ?: 'USD'),
                'value' => number_format($amount, 2, '.', ''),
            ],
        ];

        try {
            $response = $this->httpClient->post(
                $this->baseUrl() . '/v2/payments/captures/' . rawurlencode($providerReference) . '/refund',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $body,
                    'http_errors' => false,
                ],
            );
            return $this->parseRefundResponse($response, $providerReference);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('PayPal refund failed.', $this->errorLogContext($e));
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }
    }

    private function parseRefundResponse(ResponseInterface $response, string $providerReference): PaymentRefundResult
    {
        $rawBody = (string) $response->getBody();
        /** @var array{id?: string, status?: string, name?: string, message?: string} $data */
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['name'])) {
            $detail = $this->extractErrorDetail($rawBody);
            $this->logger->error('PayPal refund rejected by PayPal.', [
                'provider_reference' => $providerReference,
                'http_status' => $response->getStatusCode(),
                ...$detail,
            ]);
            return new PaymentRefundResult(false, $providerReference, $detail['message'] ?? 'PayPal refund request rejected.');
        }

        $refundId = $data['id'] ?? '';
        $status = $data['status'] ?? '';
        return new PaymentRefundResult(
            in_array($status, ['COMPLETED', 'PENDING'], true),
            $refundId !== '' ? $refundId : $providerReference,
            $status,
        );
    }

    /**
     * Calls POST /v1/notifications/verify-webhook-signature, PayPal's own
     * API-side signature verification — unlike every other gateway in this
     * app, PayPal webhook authenticity is confirmed by asking PayPal's API
     * itself, not by computing a local HMAC, since the actual signing
     * mechanism is a per-webhook RSA signature validated against a
     * PayPal-hosted certificate.
     *
     * @param array<string, string> $headers The 5 PAYPAL-* headers off the inbound request: transmissionId, transmissionTime, certUrl, authAlgo, transmissionSig.
     */
    public function verifyWebhookSignature(string $rawBody, array $headers, string $webhookId): bool
    {
        $token = $this->accessToken();
        if (null === $token) {
            return false;
        }

        try {
            /** @var mixed $webhookEvent */
            $webhookEvent = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            $body = [
                'transmission_id' => $headers['transmissionId'] ?? '',
                'transmission_time' => $headers['transmissionTime'] ?? '',
                'cert_url' => $headers['certUrl'] ?? '',
                'auth_algo' => $headers['authAlgo'] ?? '',
                'transmission_sig' => $headers['transmissionSig'] ?? '',
                'webhook_id' => $webhookId,
                'webhook_event' => $webhookEvent,
            ];

            $response = $this->httpClient->post($this->baseUrl() . '/v1/notifications/verify-webhook-signature', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);
            /** @var array{verification_status?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return ($data['verification_status'] ?? '') === 'SUCCESS';
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('PayPal verifyWebhookSignature failed.', $this->errorLogContext($e));
            return false;
        }
    }

    private function accessToken(): ?string
    {
        try {
            $response = $this->httpClient->post($this->baseUrl() . '/v1/oauth2/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId() . ':' . $this->clientSecret()),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => ['grant_type' => 'client_credentials'],
            ]);
            /** @var array{access_token?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $data['access_token'] ?? null;
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('PayPal accessToken request failed.', $this->errorLogContext($e));
            return null;
        }
    }

    /**
     * Extracts PayPal's structured error detail — `name`, `message` (or the
     * first `details[].description`), `issue` (e.g. `DUPLICATE_INVOICE_ID`),
     * and `debug_id` — from an error response body. Written after a live
     * sandbox `captureOrder()` failure could only be diagnosed by manually
     * digging through PayPal's own Developer Dashboard error log; this is
     * what lets the same diagnosis happen directly from this app's own logs
     * instead. `issue`/`debug_id` in particular are what actually pinpoint
     * *why* (e.g. distinguishing "already captured" from "not yet approved"
     * from "instrument declined") — `message` alone is often just PayPal's
     * generic wrapper text ("The requested action could not be performed,
     * semantically incorrect, or failed business validation.").
     *
     * @return array{name: string|null, message: string|null, issue: string|null, debug_id: string|null}
     */
    private function extractErrorDetail(string $rawBody): array
    {
        try {
            /** @var array{name?: string, message?: string, debug_id?: string, details?: list<array{issue?: string, description?: string}>} $data */
            $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['name' => null, 'message' => null, 'issue' => null, 'debug_id' => null];
        }

        return [
            'name' => $data['name'] ?? null,
            'message' => $data['message'] ?? ($data['details'][0]['description'] ?? null),
            'issue' => $data['details'][0]['issue'] ?? null,
            'debug_id' => $data['debug_id'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function errorLogContext(GuzzleException|\JsonException $e): array
    {
        $context = ['error' => $e->getMessage()];
        $response = $e instanceof RequestException ? $e->getResponse() : null;
        if (null !== $response) {
            $context += $this->extractErrorDetail((string) $response->getBody());
        }
        return $context;
    }

    private function baseUrl(): string
    {
        return '1' === $this->settings->getSetting('gateway_paypal_sandbox')
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function clientId(): string
    {
        return $this->settings->getSetting('gateway_paypal_clientId') ?: '';
    }

    private function clientSecret(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_paypal_clientSecret') ?: '');
    }
}
