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
 * YooKassa (formerly Yandex.Checkout/Yandex.Kassa) — Russia's other major
 * gateway alongside Robokassa, and this app's second Asia/CIS-adjacent
 * region gateway. Built as a direct HTTP integration — no third-party SDK —
 * consistent with this project's Robokassa decision; YooKassa's own official
 * `yoomoney/yookassa-sdk-php` is additionally now archived (no longer
 * maintained), reinforcing that choice further.
 *
 * Every endpoint/field/mechanism here is ground-truthed by reading that
 * archived official SDK's source directly (github.com/yoomoney/
 * yookassa-sdk-php, read for research purposes only, not installed):
 *
 * - Base URL `https://api.yookassa.ru/v3` (`lib/configuration.json`).
 * - Auth: HTTP Basic, `Authorization: Basic base64(shopId:secretKey)`
 *   (`lib/Client/CurlClient.php`).
 * - `Idempotence-Key` header required on POST (`lib/Client/BaseClient.php`);
 *   a v4 UUID here, matching the SDK's own recommendation.
 * - Paths: `POST /payments`, `GET /payments/{id}`, `POST /refunds`
 *   (`lib/Client/BaseClient.php`'s `PAYMENTS_PATH`/`REFUNDS_PATH` constants).
 * - Payment/refund status enums `pending`/`waiting_for_capture`/`succeeded`/
 *   `canceled` (`lib/Model/PaymentStatus.php`, `RefundStatus.php`).
 * - Refund request body `payment_id`/`amount`/`description`
 *   (`lib/Request/Refunds/CreateRefundRequest.php`).
 * - Redirect confirmation: request `confirmation: {type: "redirect",
 *   return_url}`, response `confirmation.confirmation_url`
 *   (`lib/Model/Confirmation/ConfirmationRedirect.php`).
 *
 * Unlike Robokassa, YooKassa's API itself has a genuine sandbox: a free
 * test shop (own shopId/secretKey) hitting this same base URL. In practice,
 * though, signing up requires a TIN (a registered legal entity), which
 * blocked the user from obtaining test credentials — so this integration
 * remains unverified against a live account, same as Robokassa, just for a
 * different underlying reason.
 *
 * **Webhook authenticity is architecturally different from every other
 * gateway in this app**: YooKassa's notifications carry no HMAC/signature at
 * all — `lib/Helpers/SecurityHelper.php` documents IP-allowlisting as the
 * only mechanism. See YookassaWebhookIpVerifier and YookassaWebhookHandler
 * for how this app treats that IP check as a fast pre-filter only, always
 * re-confirming via an authenticated `GET /payments/{id}` before trusting a
 * notification enough to mark an invoice paid.
 *
 * Only full refunds are requested, matching how this app always calls
 * `refund()` with the whole original payment amount.
 *
 * **Cross-validated against the SDK's own test fixtures and executable
 * logic** (not just its interface/model declarations), since no live
 * account exists to test against directly:
 * `tests/Client/fixtures/createPaymentFixtures.json` confirms
 * `amount.value` is a decimal STRING (`"10.00"`), matching this class's
 * `number_format($amount, 2, '.', '')`. `lib/Common/Exceptions/
 * BadApiRequestException.php` — real executable error-parsing logic, not a
 * guess — confirms the actual HTTP error envelope this class's `refund()`
 * reads from is top-level `{description, code, parameter, retry_after,
 * type}`, not the `{error: {code, description}}` shape a first read of
 * `tests/Client/fixtures/createRefundFixtures.json` suggests (that fixture
 * turned out to be testing the deserializer's tolerance of unknown extra
 * fields, not documenting a real response shape — `lib/Model/Refund.php`
 * has no `error` property at all).
 */
final class YookassaPaymentService implements PaymentGatewayInterface
{
    private const string BASE_URL = 'https://api.yookassa.ru/v3';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'yookassa';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->shopId() !== '' && $this->secretKey() !== '';
    }

    /**
     * Creates a payment via `POST /payments` and returns YooKassa's own
     * payment `id` (the provider reference for later `verifyPayment()`/
     * `refund()` calls — YooKassa generates this itself, unlike Robokassa
     * where the merchant's own InvId doubles as the reference) alongside the
     * hosted confirmation page URL to redirect the customer to. Returns null
     * on any failure, logging the detail for a maintainer.
     *
     * @param array<string, string> $metadata Round-tripped verbatim onto the payment object and its webhook notifications — this is how the webhook finds the right invoice.
     * @return array{paymentId: string, confirmationUrl: string}|null
     */
    public function createPayment(float $amount, string $description, string $returnUrl, array $metadata = []): ?array
    {
        $body = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => strtoupper($this->settings->getSetting('currency_code') ?: 'RUB'),
            ],
            'capture' => true,
            'description' => $description,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $returnUrl,
            ],
            'metadata' => $metadata,
        ];

        try {
            $response = $this->httpClient->post(self::BASE_URL . '/payments', [
                'headers' => [
                    'Authorization' => $this->authorizationHeader(),
                    'Idempotence-Key' => Uuid::uuid4()->toString(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);
            /** @var array{id?: string, confirmation?: array{confirmation_url?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $paymentId = $data['id'] ?? null;
            $confirmationUrl = $data['confirmation']['confirmation_url'] ?? null;
            if ($paymentId === null || $confirmationUrl === null) {
                $this->logger->error('YooKassa createPayment: missing id/confirmation_url in response.');
                return null;
            }

            return ['paymentId' => $paymentId, 'confirmationUrl' => $confirmationUrl];
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('YooKassa createPayment failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $response = $this->httpClient->get(self::BASE_URL . '/payments/' . $providerReference, [
                'headers' => ['Authorization' => $this->authorizationHeader()],
            ]);
            /** @var array{status?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $status = $data['status'] ?? '';

            return new PaymentVerificationResult($status === 'succeeded', $providerReference, $status);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('YooKassa verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $body = [
            'payment_id' => $providerReference,
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => strtoupper($this->settings->getSetting('currency_code') ?: 'RUB'),
            ],
        ];

        try {
            $response = $this->httpClient->post(self::BASE_URL . '/refunds', [
                'headers' => [
                    'Authorization' => $this->authorizationHeader(),
                    'Idempotence-Key' => Uuid::uuid4()->toString(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'http_errors' => false,
            ]);
            /** @var array{id?: string, status?: string, code?: string, description?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $status = $data['status'] ?? '';

            if ($status !== 'succeeded' && $status !== 'pending') {
                $message = $data['description'] ?? $data['code'] ?? 'YooKassa refund request rejected.';
                return new PaymentRefundResult(false, $providerReference, $message);
            }

            return new PaymentRefundResult(true, $data['id'] ?? $providerReference, $status);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('YooKassa refund failed.', ['error' => $e->getMessage()]);
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }
    }

    private function authorizationHeader(): string
    {
        return 'Basic ' . base64_encode($this->shopId() . ':' . $this->secretKey());
    }

    private function shopId(): string
    {
        return $this->settings->getSetting('gateway_yookassa_shopId') ?: '';
    }

    private function secretKey(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_yookassa_secretKey') ?: '');
    }
}
