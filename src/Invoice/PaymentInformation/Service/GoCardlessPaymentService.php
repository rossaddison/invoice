<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;
use GoCardlessPro\Client;
use GoCardlessPro\Core\Exception\GoCardlessProException;
use GoCardlessPro\Environment;
use GoCardlessPro\Webhook;
use Psr\Log\LoggerInterface;

final class GoCardlessPaymentService implements PaymentGatewayInterface
{
    private const array PAID_STATUSES = ['confirmed', 'paid_out'];

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'gocardless';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->accessToken() !== '';
    }

    private function accessToken(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_gocardless_accessToken') ?: '',
        );
    }

    private function environment(): string
    {
        return $this->settings->getSetting('gateway_gocardless_sandbox') === '1'
            ? Environment::SANDBOX
            : Environment::LIVE;
    }

    private function client(): Client
    {
        return new Client([
            'access_token' => $this->accessToken(),
            'environment' => $this->environment(),
        ]);
    }

    /**
     * Creates a Billing Request and its hosted authorisation flow.
     *
     * @return array{
     *     billing_request_id: string,
     *     billing_request_flow_id: string,
     *     redirect_url: string
     * }
     */
    public function createBillingRequestFlow(
        string $invoiceUrlKey,
        string $successRedirectUrl,
    ): array {
        $client = $this->client();

        $billingRequest = $client->billingRequests()->create([
            'params' => [
                'mandate_request' => [
                    'scheme' => 'bacs',
                ],
                'metadata' => [
                    'invoice_url_key' => $invoiceUrlKey,
                ],
            ],
        ]);

        $billingRequestFlow = $client->billingRequestFlows()->create([
            'params' => [
                'redirect_uri' => $successRedirectUrl,
                'links' => [
                    'billing_request' => (string) $billingRequest->id,
                ],
            ],
        ]);

        return [
            'billing_request_id' => (string) $billingRequest->id,
            'billing_request_flow_id' => (string) $billingRequestFlow->id,
            'redirect_url' => (string) $billingRequestFlow->authorisation_url,
        ];
    }

/**
 * Retrieves a fulfilled Billing Request.
 *
 * @return array{mandate_id: non-empty-string, invoice_url_key: non-empty-string}|null
 */
public function getCompletedBillingRequest(string $billingRequestId): ?array
{
    $billingRequest = $this->client()->billingRequests()->get($billingRequestId);

    if ($billingRequest->status !== 'fulfilled') {
        return null;
    }

    // The GoCardless SDK response exposes links as mixed.
    $links = $billingRequest->links ?? null;

    if (!is_object($links)) {
        $this->logger->warning(
            'GoCardless Billing Request has no mandate link.',
            ['billing_request_id' => $billingRequestId]
        );

        return null;
    }

    $candidate = $links->mandate_request_mandate ?? null;

    if (!is_string($candidate) || $candidate === '') {
        $this->logger->warning(
            'GoCardless Billing Request has no mandate link.',
            ['billing_request_id' => $billingRequestId]
        );

        return null;
    }

    $metadata = (array) ($billingRequest->metadata ?? []);
    $invoiceUrlKey = $metadata['invoice_url_key'] ?? null;

    if (!is_string($invoiceUrlKey) || $invoiceUrlKey === '') {
        $this->logger->error(
            'GoCardless Billing Request is missing its invoice metadata.',
            ['billing_request_id' => $billingRequestId]
        );

        return null;
    }

    return [
        'mandate_id' => $candidate,
        'invoice_url_key' => $invoiceUrlKey,
    ];
}

    /**
     * Schedules a Direct Debit collection against an existing mandate.
     *
     * @param array<string, string> $metadata
     *
     * @return array{id: string, status: string, charge_date: string}
     */
    public function scheduleDirectDebitPayment(
        string $mandateId,
        float $amount,
        string $currency,
        DateTimeImmutable $chargeDate,
        array $metadata = [],
        ?string $idempotencyKey = null,
    ): array {
        $params = [
            'amount' => (int) round($amount * 100),
            'currency' => strtoupper($currency),
            'charge_date' => $chargeDate->format('Y-m-d'),
            'links' => ['mandate' => $mandateId],
            'metadata' => $metadata,
        ];

        $payment = $this->client()->payments()->create([
            'params' => $params,
            'headers' => $idempotencyKey !== null
                ? ['Idempotency-Key' => $idempotencyKey]
                : [],
        ]);

        return [
            'id' => (string) $payment->id,
            'status' => (string) $payment->status,
            'charge_date' => (string) $payment->charge_date,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getPaymentMetadata(string $paymentId): array
    {
        /** @var array<string, string> */
        return (array) $this->client()->payments()->get($paymentId)->metadata;
    }

    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $payment = $this->client()->payments()->get($providerReference);
        } catch (GoCardlessProException $e) {
            $this->logger->error('GoCardless verifyPayment failed', ['exception' => $e]);

            return new PaymentVerificationResult(
                false,
                $providerReference,
                $e->getMessage(),
            );
        }

        $status = (string) $payment->status;

        return new PaymentVerificationResult(
            in_array($status, self::PAID_STATUSES, true),
            $providerReference,
            $status,
        );
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        try {
            $refund = $this->client()->refunds()->create([
                'params' => [
                    'amount' => (int) round($amount * 100),
                    'links' => ['payment' => $providerReference],
                ],
            ]);
        } catch (GoCardlessProException $e) {
            $this->logger->error('GoCardless refund failed', ['exception' => $e]);

            return new PaymentRefundResult(
                false,
                $providerReference,
                $e->getMessage(),
            );
        }

        return new PaymentRefundResult(
            true,
            $providerReference,
            (string) $refund->status,
        );
    }

    public function isValidWebhookSignature(
        string $requestBody,
        string $signatureHeader,
    ): bool {
        $secret = (string) $this->settings->decode(
            $this->settings->getSetting('gateway_gocardless_webhookSecret') ?: '',
        );

        if ($secret === '') {
            return false;
        }

        return Webhook::isSignatureValid(
            $requestBody,
            $signatureHeader,
            $secret,
        );
    }
}
