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
use Ramsey\Uuid\Uuid;

/**
 * Square — built against the Checkout API's Payment Links
 * (`POST /v2/online-checkout/payment-links`, an Order-based hosted
 * checkout page you redirect the customer to), not Square's Web Payments
 * SDK, which would need client-side card-nonce JS integration this app's
 * other gateways don't require.
 *
 * Square's official PHP SDK, `square/square-php-sdk` (github.com/square/
 * square-php-sdk), is genuinely first-party and actively maintained (pushed
 * 2026-07-14, not archived). It was deliberately **not installed** as a
 * composer dependency, for the same reason as Razorpay and PayPal: its HTTP
 * layer is APIMatic-generated code on `apimatic/unirest-php`, a different
 * HTTP client from the Guzzle client every other gateway in this app is
 * built and tested against, with no mockable test double. It was read
 * directly from GitHub for research purposes only, to ground-truth every
 * URL/field/formula below against real executable code.
 *
 * Confirmed directly from the SDK source (`Environments.php`,
 * `SquareClient.php`, `Checkout/PaymentLinks/PaymentLinksClient.php`,
 * `Orders/OrdersClient.php`, `Payments/PaymentsClient.php`,
 * `Refunds/RefundsClient.php`, `Types/Order.php`, `Types/OrderLineItem.php`,
 * `Types/Money.php`, `Refunds/Requests/RefundPaymentRequest.php`):
 * - Base URLs `https://connect.squareup.com` (production) /
 *   `https://connect.squareupsandbox.com` (sandbox) — like PayPal, and
 *   unlike every other gateway added this session, Square's sandbox
 *   setting really is a different base URL, not just a different
 *   credential against the same one.
 * - Auth: `Authorization: Bearer {accessToken}` plus a required
 *   `Square-Version: {date}` header (Square's whole-API date-based
 *   versioning scheme — pinned here to the version confirmed against the
 *   SDK source at the time of writing; Square documents pinned versions as
 *   remaining valid indefinitely, so this isn't a ticking deadline, just
 *   worth bumping occasionally).
 * - `POST /v2/online-checkout/payment-links`, `GET /v2/payments/{id}`,
 *   `GET /v2/orders/{id}`, `POST /v2/refunds`.
 * - `Order.reference_id` is the only place to carry this app's own invoice
 *   url_key through Square's flow — the resulting Payment's own webhook
 *   payload only carries `order_id`, not a reference_id of its own (see
 *   SquareWebhookHandler's docblock), so `getOrderReferenceId()` below
 *   makes a second GET to resolve it back.
 *
 * Confirmed directly against Square's own current developer docs
 * (reachable this session, unlike Paystack's): the webhook signature
 * formula (see SquareSignatureService), the `payment.created`/
 * `payment.updated` event names, and the webhook payload's
 * `data.object.payment.{id, status, order_id}` shape with `status:
 * "COMPLETED"` on success.
 *
 * Amount is always sent in the smallest currency subunit (cents for USD),
 * matching the `(int) round($amount * 100)` pattern used throughout this
 * app. `OrderLineItem.quantity` is a **string** field, confirmed from the
 * SDK's own type — not an integer.
 */
final class SquarePaymentService implements PaymentGatewayInterface
{
    private const string SQUARE_VERSION = '2026-07-15';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'square';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->accessToken() !== '' && $this->locationId() !== '';
    }

    /**
     * Creates a Payment Link (POST /v2/online-checkout/payment-links) and
     * returns its own `id` (only used for logging/lookup — verifyPayment()
     * and refund() both operate on the resulting Payment's own id, not
     * this) alongside the hosted `url` to redirect the customer to.
     * `$metadata['invoiceUrlKey']` becomes the underlying Order's
     * `reference_id` — this is how the webhook eventually finds the right
     * invoice, via getOrderReferenceId(). Returns null on any failure,
     * logging the detail for a maintainer.
     *
     * @param array<string, string> $metadata Only `invoiceUrlKey` is used, promoted to the Order's reference_id.
     * @return array{paymentLinkId: string, paymentLinkUrl: string}|null
     */
    public function createPayment(float $amount, string $description, string $returnUrl, array $metadata = []): ?array
    {
        $invoiceUrlKey = $metadata['invoiceUrlKey'] ?? '';
        $currency = strtoupper($this->settings->getSetting('currency_code') ?: 'USD');
        $body = [
            'idempotency_key' => Uuid::uuid4()->toString(),
            'description' => $description,
            'order' => [
                'location_id' => $this->locationId(),
                'reference_id' => $invoiceUrlKey,
                'line_items' => [[
                    'name' => $description,
                    'quantity' => '1',
                    'base_price_money' => [
                        'amount' => (int) round($amount * 100),
                        'currency' => $currency,
                    ],
                ]],
            ],
            'checkout_options' => [
                'redirect_url' => $returnUrl,
            ],
        ];

        try {
            $response = $this->httpClient->post($this->baseUrl() . '/v2/online-checkout/payment-links', [
                'headers' => $this->headers(),
                'json' => $body,
            ]);
            /** @var array{payment_link?: array{id?: string, url?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $paymentLinkId = $data['payment_link']['id'] ?? null;
            $paymentLinkUrl = $data['payment_link']['url'] ?? null;
            if (null === $paymentLinkId || null === $paymentLinkUrl) {
                $this->logger->error('Square createPayment: response missing payment_link id or url.');
                return null;
            }

            return ['paymentLinkId' => $paymentLinkId, 'paymentLinkUrl' => $paymentLinkUrl];
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Square createPayment failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $response = $this->httpClient->get(
                $this->baseUrl() . '/v2/payments/' . rawurlencode($providerReference),
                ['headers' => $this->headers()],
            );
            /** @var array{payment?: array{status?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $status = $data['payment']['status'] ?? '';

            return new PaymentVerificationResult($status === 'COMPLETED', $providerReference, $status);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('Square verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }
    }

    /**
     * Resolves the invoice url_key carried through Square's flow — the
     * Order's own `reference_id`, set at Payment Link creation time (see
     * createPayment()) but not echoed on the Payment webhook payload
     * itself, only its `order_id`.
     */
    public function getOrderReferenceId(string $orderId): ?string
    {
        try {
            $response = $this->httpClient->get(
                $this->baseUrl() . '/v2/orders/' . rawurlencode($orderId),
                ['headers' => $this->headers()],
            );
            /** @var array{order?: array{reference_id?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $data['order']['reference_id'] ?? null;
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('Square getOrderReferenceId failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $currency = strtoupper($this->settings->getSetting('currency_code') ?: 'USD');
        $body = [
            'idempotency_key' => Uuid::uuid4()->toString(),
            'payment_id' => $providerReference,
            'amount_money' => [
                'amount' => (int) round($amount * 100),
                'currency' => $currency,
            ],
        ];

        try {
            $response = $this->httpClient->post($this->baseUrl() . '/v2/refunds', [
                'headers' => $this->headers(),
                'json' => $body,
                'http_errors' => false,
            ]);
            /** @var array{refund?: array{id?: string, status?: string}, errors?: list<array{detail?: string}>} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($data['errors'])) {
                return new PaymentRefundResult(false, $providerReference, $data['errors'][0]['detail'] ?? 'Square refund request rejected.');
            }

            $refundId = $data['refund']['id'] ?? '';
            $status = $data['refund']['status'] ?? '';
            return new PaymentRefundResult(
                in_array($status, ['PENDING', 'COMPLETED'], true),
                $refundId !== '' ? $refundId : $providerReference,
                $status,
            );
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Square refund failed.', ['error' => $e->getMessage()]);
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken(),
            'Square-Version' => self::SQUARE_VERSION,
            'Content-Type' => 'application/json',
        ];
    }

    private function baseUrl(): string
    {
        return '1' === $this->settings->getSetting('gateway_square_sandbox')
            ? 'https://connect.squareupsandbox.com'
            : 'https://connect.squareup.com';
    }

    private function accessToken(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_square_accessToken') ?: '');
    }

    private function locationId(): string
    {
        return $this->settings->getSetting('gateway_square_locationId') ?: '';
    }
}
