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
 * Worldpay — the orchestration Payments API
 * (`POST /api/payments`), not Card Payments API v7 (confirmed
 * Enterprise-only, not reachable via this app's self-serve sandbox
 * account). Built as a direct HTTP integration — no official Worldpay
 * PHP SDK exists, same situation as Paystack/Razorpay/YooKassa/
 * Robokassa in this codebase.
 *
 * Ground-truthed against the real, downloaded OpenAPI spec
 * (`https://worldpay-portal.eu.redocly.app/access/_bundle/products/payments/@20240601/openapi.yaml`)
 * and Worldpay's own real sequence-diagram docs — not assumed. Key
 * confirmed facts this class relies on:
 *
 * - Headers: `Authorization: Basic base64(username:password)`,
 *   `Content-Type`/`Accept: application/json`,
 *   `WP-Api-Version: 2024-06-01`.
 * - `POST /api/payments` returns `201` (`authorized`,
 *   `3dsDeviceDataRequired`, `fraudHighRisk`, `refused`) or `202`
 *   (`sentForSettlement`, `sentForCancellation`).
 * - The Checkout SDK session is submitted as
 *   `instruction.paymentInstrument: {type: "checkout", sessionHref, cardHolderName, billingAddress}`
 *   — bare `"checkout"`, NOT `"card/checkout"` (that's Verified
 *   Tokens' own, different, discriminator — confirmed directly from
 *   this API's own request example, `components.examples.card-payment-checkout`).
 * - `instruction.settlement.auto: true` charges in one step, matching
 *   how every other gateway in this app behaves (refundable directly
 *   afterward, no separate manual settle step this app ever exercises).
 * - Every response carries a state-dependent `_links.self.href` (the
 *   query URL — what verifyPayment()/refund() actually call) and
 *   `_actions` (available next steps: settle/cancel/refund/etc,
 *   present or absent depending on current state — e.g. no refund
 *   action exists until the payment has actually settled).
 * - 3DS device-data-collection and challenge are handled via two more
 *   actions on the SAME payment resource
 *   (`/api/payments/{linkData}/3dsDeviceData`,
 *   `.../3dsChallenges`), not a separate API call to Worldpay's
 *   standalone (Enterprise-only) 3DS product.
 *
 * `self_href` (the opaque `_links.self.href` query URL) must be
 * persisted immediately after a successful createPayment() call —
 * before this class's caller even returns a response to the browser —
 * since it can't be reconstructed later from anything a webhook
 * payload carries. See WorldpayMerchant's own docblock for why this
 * needed its own per-provider audit entity rather than the generic
 * Merchant table, and WorldpayPaymentController for where that
 * persistence actually happens (kept out of this class, matching
 * every other *PaymentService in this codebase: none of them write to
 * the database themselves).
 */
final class WorldpayPaymentService implements PaymentGatewayInterface
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
        return 'worldpay';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->username() !== '' && $this->password() !== '' && $this->entity() !== '';
    }

    public function isSandbox(): bool
    {
        return $this->settings->getSetting('gateway_worldpay_sandbox') === '1';
    }

    private function baseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://try.access.worldpay.com'
            : 'https://access.worldpay.com';
    }

    /**
     * Initiates a payment (`POST /api/payments`) using a Checkout SDK
     * session. `$transactionReference` is this app's own generated,
     * invoice-correlatable value — echoed back verbatim in webhook
     * `eventDetails.transactionReference`, the correlation key
     * WorldpayWebhookHandler resolves an incoming event against.
     *
     * @param array{
     *     address1: string, city: string, postalCode: string, countryCode: string,
     *     address2?: string, address3?: string, state?: string,
     * } $billingAddress
     * @return array{
     *     outcome: string, paymentId: string, transactionReference: string,
     *     selfHref: ?string, deviceDataCollection: ?array{bin: string, jwt: string, url: string},
     *     supply3dsDeviceDataHref: ?string, raw: array,
     * }|null Null only on a transport/HTTP-level failure — a real
     *     Worldpay rejection (e.g. `refused`) still returns an array,
     *     since the caller needs to inspect `outcome`.
     */
    public function createPayment(
        string $transactionReference,
        string $sessionHref,
        string $cardHolderName,
        array $billingAddress,
        float $amount,
        string $currency,
        string $narrativeLine1,
        string $threeDsReturnUrl,
    ): ?array {
        $body = [
            'transactionReference' => $transactionReference,
            'merchant' => ['entity' => $this->entity()],
            'instruction' => [
                'method' => 'card',
                'settlement' => ['auto' => true],
                'paymentInstrument' => [
                    'type' => 'checkout',
                    'cardHolderName' => $cardHolderName,
                    'sessionHref' => $sessionHref,
                    'billingAddress' => $billingAddress,
                ],
                'narrative' => ['line1' => $narrativeLine1],
                'value' => [
                    'currency' => strtoupper($currency),
                    'amount' => (int) round($amount * 100),
                ],
                'threeDS' => [
                    'challenge' => [
                        'returnUrl' => $threeDsReturnUrl,
                        'preference' => 'challengeRequested',
                    ],
                ],
            ],
        ];

        try {
            $response = $this->httpClient->post($this->baseUrl() . '/api/payments', [
                'headers' => $this->headers(),
                'json' => $body,
                'http_errors' => false,
            ]);
            /**
             * @var array{
             *     outcome?: string, paymentId?: string, transactionReference?: string,
             *     deviceDataCollection?: array{jwt?: string, url?: string, bin?: string},
             *     _links?: array{'self'?: array{href?: string}},
             *     _actions?: array{supply3dsDeviceData?: array{href?: string}},
             * } $data
             */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return $this->parsePaymentResponse($transactionReference, $data);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Worldpay createPayment failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @param array{
     *     outcome?: string, paymentId?: string, transactionReference?: string,
     *     deviceDataCollection?: array{jwt?: string, url?: string, bin?: string},
     *     _links?: array{'self'?: array{href?: string}},
     *     _actions?: array{supply3dsDeviceData?: array{href?: string}},
     * } $data
     * @return array{
     *     outcome: string, paymentId: string, transactionReference: string,
     *     selfHref: ?string, deviceDataCollection: ?array{bin: string, jwt: string, url: string},
     *     supply3dsDeviceDataHref: ?string, raw: array<array-key, mixed>,
     * }
     */
    private function parsePaymentResponse(string $transactionReference, array $data): array
    {
        $ddc = $data['deviceDataCollection'] ?? null;

        return [
            'outcome' => $data['outcome'] ?? '',
            'paymentId' => $data['paymentId'] ?? '',
            'transactionReference' => $data['transactionReference'] ?? $transactionReference,
            'selfHref' => $data['_links']['self']['href'] ?? null,
            'deviceDataCollection' => (null !== $ddc && isset($ddc['jwt'], $ddc['url'], $ddc['bin']))
                ? ['bin' => $ddc['bin'], 'jwt' => $ddc['jwt'], 'url' => $ddc['url']]
                : null,
            'supply3dsDeviceDataHref' => $data['_actions']['supply3dsDeviceData']['href'] ?? null,
            'raw' => $data,
        ];
    }

    /**
     * Supplies the `collectionReference` (the `sessionId`/`dfReferenceId`
     * captured from the browser's `postMessage` after the Device Data
     * Collection form posts to `deviceDataCollection.url`) —
     * `POST` to the href the initial payment response's own
     * `_actions.supply3dsDeviceData` carried.
     *
     * @return array<string, mixed>|null Null only on transport failure;
     *     the parsed response's own `outcome` discriminates
     *     authorized/refused/3dsChallenged/3dsAuthenticationFailed/3dsUnavailable
     *     — see DeviceDataResponse201 in the downloaded spec.
     */
    public function supply3dsDeviceData(string $actionHref, string $collectionReference): ?array
    {
        try {
            $response = $this->httpClient->post($actionHref, [
                'headers' => $this->headers(),
                'json' => ['collectionReference' => $collectionReference],
                'http_errors' => false,
            ]);
            /** @var array<string, mixed> $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return $data;
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Worldpay supply3dsDeviceData failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Resumes the payment after the customer completes a 3DS challenge —
     * `POST` to the href the `3dsChallenged` response's own
     * `_actions.complete3dsChallenge` carried. No request body (per the
     * confirmed spec — `linkData` in the path is the only input).
     *
     * @return array<string, mixed>|null
     */
    public function complete3dsChallenge(string $actionHref): ?array
    {
        try {
            $response = $this->httpClient->post($actionHref, [
                'headers' => $this->headers(),
                'http_errors' => false,
            ]);
            /** @var array<string, mixed> $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return $data;
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Worldpay complete3dsChallenge failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * `$providerReference` is the stored `self_href` (a full URL, not a
     * bare ID) — see WorldpayMerchant::getSelfHref(). `GET`s it
     * directly and parses the current `outcome`.
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        if ($providerReference === '') {
            return new PaymentVerificationResult(false, $providerReference, 'No self_href to verify against.');
        }

        try {
            $response = $this->httpClient->get($providerReference, [
                'headers' => $this->headers(),
                'http_errors' => false,
            ]);
            /** @var array{outcome?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $outcome = $data['outcome'] ?? '';

            return new PaymentVerificationResult(
                paid: in_array($outcome, ['authorized', 'sentForSettlement', 'settled'], true),
                providerReference: $providerReference,
                message: $outcome,
            );
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('Worldpay verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }
    }

    /**
     * Refund is state-dependent HATEOAS, not a fixed URL pattern — a
     * freshly-authorized-but-not-yet-settled payment has NO refund
     * action at all (only cancel/settle), confirmed throughout
     * research. `$providerReference` (the stored `self_href`) is
     * `GET`-ed first to discover whether `_actions.refundPayment` /
     * `.partiallyRefundPayment` is currently present, rather than
     * guessing a refund URL.
     */
    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        if ($providerReference === '') {
            return new PaymentRefundResult(false, $providerReference, 'No self_href to refund against.');
        }

        try {
            $current = $this->httpClient->get($providerReference, [
                'headers' => $this->headers(),
                'http_errors' => false,
            ]);
            /** @var array{_actions?: array<string, array{href?: string}>} $data */
            $data = json_decode((string) $current->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $refundHref = $data['_actions']['refundPayment']['href']
                ?? $data['_actions']['partiallyRefundPayment']['href']
                ?? null;

            if (null === $refundHref) {
                return new PaymentRefundResult(false, $providerReference, 'No refund action available — payment not yet settled.');
            }

            $response = $this->httpClient->post($refundHref, [
                'headers' => $this->headers(),
                'json' => ['value' => ['amount' => (int) round($amount * 100)]],
                'http_errors' => false,
            ]);
            /** @var array{outcome?: string} $refundData */
            $refundData = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return new PaymentRefundResult(
                refunded: in_array($refundData['outcome'] ?? '', ['sentForRefund', 'refunded'], true),
                providerReference: $providerReference,
                message: $refundData['outcome'] ?? '',
            );
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Worldpay refund failed.', ['providerReference' => $providerReference, 'error' => $e->getMessage()]);
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->username() . ':' . $this->password()),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'WP-Api-Version' => '2024-06-01',
        ];
    }

    private function username(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_worldpay_username') ?: '');
    }

    private function password(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_worldpay_password') ?: '');
    }

    /**
     * Not decoded: entity is a text-type setting (see
     * SettingPaymentTrait::activePaymentGateways()), so it is never
     * encrypted on save — matching Adyen's merchantAccount precedent
     * exactly. Running it through decode() would corrupt it.
     */
    private function entity(): string
    {
        return $this->settings->getSetting('gateway_worldpay_entity') ?: '';
    }

    public function tradingName(): string
    {
        return $this->settings->getSetting('gateway_worldpay_tradingName') ?: '';
    }
}
