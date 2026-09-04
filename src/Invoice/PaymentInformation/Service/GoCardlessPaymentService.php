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

/**
 * GoCardless Direct Debit: mandate setup via a hosted "redirect flow", and
 * scheduled payment collection against the resulting mandate. Unlike the
 * card/PSP gateways in this directory, a GoCardless collection isn't
 * synchronous — verifyPayment()/refund() satisfy PaymentGatewayInterface,
 * but the actual mandate + charge-date scheduling happens via
 * createRedirectFlow()/completeRedirectFlow()/scheduleDirectDebitPayment(),
 * driven by Inv::direct_debit_date (see InvService::saveInv()).
 */
final class GoCardlessPaymentService implements PaymentGatewayInterface
{
    private const PAID_STATUSES = ['confirmed', 'paid_out'];

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
            $this->settings->getSetting('gateway_gocardless_accessToken') ?: ''
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
     * Starts a GoCardless "redirect flow" — the hosted pages the customer
     * completes once to authorise a Direct Debit mandate.
     *
     * @return array{redirect_flow_id: string, redirect_url: string}
     */
    public function createRedirectFlow(
        string $description,
        string $sessionToken,
        string $successRedirectUrl,
    ): array {
        $redirectFlow = $this->client()->redirectFlows()->create([
            'params' => [
                'description' => $description,
                'session_token' => $sessionToken,
                'success_redirect_url' => $successRedirectUrl,
            ],
        ]);

        return [
            'redirect_flow_id' => (string) $redirectFlow->id,
            'redirect_url' => (string) $redirectFlow->redirect_url,
        ];
    }

    /**
     * Confirms mandate setup once the customer returns from the GoCardless
     * hosted pages. $sessionToken must be identical to the one used in
     * createRedirectFlow() — GoCardless rejects the completion otherwise.
     *
     * @return array{mandate_id: string, customer_id: string}
     */
    public function completeRedirectFlow(string $redirectFlowId, string $sessionToken): array
    {
        $redirectFlow = $this->client()->redirectFlows()->complete($redirectFlowId, [
            'params' => ['session_token' => $sessionToken],
        ]);

        /** @var object{mandate: string, customer: string} $links */
        $links = $redirectFlow->links;
        return [
            'mandate_id' => $links->mandate,
            'customer_id' => $links->customer,
        ];
    }

    /**
     * Schedules a Direct Debit collection against an existing mandate.
     * $chargeDate is taken as-is — the caller (InvService, once the
     * GoCardless UI lands) is responsible for clamping it to GoCardless's
     * minimum charge-date lead time before calling this.
     *
     * No custom `reference` is sent — GoCardless rejects one with a 422
     * (InvalidApiUsageException, "Custom payment references are not enabled
     * for your scheme identifier") unless that's separately enabled for the
     * account by GoCardless support, and metadata (not reference) is what
     * getPaymentMetadata()/the webhook handler use to resolve a payment back
     * to its invoice, so a custom reference isn't functionally needed here.
     *
     * @param array<string, string> $metadata
     * @return array{id: string, status: string, charge_date: string}
     */
    public function scheduleDirectDebitPayment(
        string $mandateId,
        float $amount,
        string $currency,
        DateTimeImmutable $chargeDate,
        array $metadata = [],
    ): array {
        $payment = $this->client()->payments()->create([
            'params' => [
                // Smallest currency unit, e.g. pence for GBP.
                'amount' => (int) round($amount * 100),
                'currency' => strtoupper($currency),
                'charge_date' => $chargeDate->format('Y-m-d'),
                'links' => ['mandate' => $mandateId],
                'metadata' => $metadata,
            ],
        ]);

        return [
            'id' => (string) $payment->id,
            'status' => (string) $payment->status,
            'charge_date' => (string) $payment->charge_date,
        ];
    }

    /**
     * Webhook events only carry links.payment (the GoCardless payment id),
     * not the metadata scheduleDirectDebitPayment() set — this resolves a
     * webhook event back to the invoice it belongs to.
     *
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
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
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
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }

        return new PaymentRefundResult(true, $providerReference, (string) $refund->status);
    }

    /**
     * Verifies the `Webhook-Signature` header GoCardless sends on every
     * webhook request, using the endpoint secret configured in the
     * GoCardless dashboard. Callers must reject the request when this
     * returns false rather than parsing the body.
     */
    public function isValidWebhookSignature(string $requestBody, string $signatureHeader): bool
    {
        $secret = (string) $this->settings->decode(
            $this->settings->getSetting('gateway_gocardless_webhookSecret') ?: ''
        );
        if ($secret === '') {
            return false;
        }
        return Webhook::isSignatureValid($requestBody, $signatureHeader, $secret);
    }
}
