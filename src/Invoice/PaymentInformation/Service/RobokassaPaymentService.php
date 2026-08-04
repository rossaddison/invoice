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
 * Robokassa — Russia/Belarus + CIS Central Asia (Kazakhstan, Kyrgyzstan,
 * Tajikistan, Turkmenistan, Uzbekistan) direct debit/card gateway, the
 * project's first Asia-priority region gateway. Built as a direct HTTP
 * integration against Robokassa's own API — no third-party SDK — per
 * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md.
 *
 * Every endpoint/field/formula this class relies on is ground-truthed
 * against Robokassa's own official OpenAPI specification
 * (https://docs.robokassa.ru/openapi/robokassa.yaml, operationIds
 * `createInvoice`, `getOperationState`, and `createRefund`) — not
 * third-party sources.
 *
 * `createPaymentUrl()` (CreateInvoice) and `verifyPayment()` (OpStateExt)
 * are explicitly documented by Robokassa as production-mode-only
 * (`x-robokassa-environment: testSupported: false`) — there is no
 * test/sandbox variant of either endpoint, confirming what the user
 * independently found by checking Robokassa's own site directly: there is
 * no separate sandbox environment for this API surface. Robokassa's
 * `IsTest=1` flag only applies to the legacy `Merchant/Index.aspx`
 * query-string redirect scheme, which this integration deliberately does not
 * use (per this project's "latest technology" direction). Accordingly there
 * is exactly one login/password1/password2 credential set, no separate
 * test-mode passwords.
 *
 * `verifyPayment()`'s State.Code === 100 ("Операция успешно подтверждена" /
 * "operation successfully confirmed") is the spec's own documented meaning
 * for that code — not a guess.
 *
 * `refund()` (RefundService/Refund/Create) requires a third credential
 * (Password #3) that Robokassa only issues once the Refund API has been
 * separately enabled for the merchant account — the spec's own 401 example
 * is literally "Signature key is not exists or partner have not access to
 * API". If Password #3 isn't configured, refund() reports a clear
 * not-configured message rather than attempting a request that Robokassa
 * would reject anyway. The Refund API is keyed by `OpKey` (the completed
 * operation's key), not `InvId`/`InvoiceID` — this is resolved via a lookup
 * against OpStateExt's own `Info.OpKey` field first, reusing the same
 * MerchantLogin:InvoiceID:Пароль#2-signed call `verifyPayment()` makes.
 * `success: true` on the Refund API only confirms the refund *request* was
 * accepted — Robokassa's own spec notes a real successful refund wasn't
 * exercised during their spec verification, and processing is asynchronous
 * (a separate Refund/GetState endpoint exists to poll final status, not yet
 * wired up here). Only full refunds are requested (no `RefundSum`), matching
 * how this app always calls `refund()` with the full original payment
 * amount (see `PaymentRefundController`).
 */
final class RobokassaPaymentService implements PaymentGatewayInterface
{
    private const string JWT_INVOICE_URL = 'https://services.robokassa.ru/InvoiceServiceWebApi/api/CreateInvoice';
    private const string REFUND_CREATE_URL = 'https://services.robokassa.ru/RefundService/Refund/Create';
    private const string WEB_SERVICE_URL = 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly RobokassaSignatureService $signer,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'robokassa';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->login() !== '' && $this->password1() !== '' && $this->password2() !== '';
    }

    /**
     * Builds a Robokassa-hosted payment page URL for the given invoice via
     * the JWT invoice API (CreateInvoice). Returns null (rather than
     * throwing) on any failure, logging the detail for a maintainer — the
     * caller decides how to surface that to the customer, matching this
     * app's other gateways.
     *
     * The endpoint always answers HTTP 200 and distinguishes success from
     * an application-level error via the `isSuccess` field (per the spec's
     * InvoiceCreateSuccess/InvoiceError schemas) — never via HTTP status.
     */
    public function createPaymentUrl(int $invId, float $outSum, string $description): ?string
    {
        $payload = [
            'MerchantLogin' => $this->login(),
            'InvoiceType' => 'OneTime',
            'Culture' => 'en',
            'InvId' => $invId,
            'OutSum' => $outSum,
            'Description' => $description,
        ];
        [$dataToSign, $signature] = $this->signer->signJwt(
            ['alg' => 'MD5', 'typ' => 'JWT'],
            $payload,
            $this->login(),
            $this->password1(),
        );
        $jwt = $dataToSign . '.' . $signature;

        try {
            $response = $this->httpClient->post(self::JWT_INVOICE_URL, [
                'body' => json_encode($jwt, JSON_THROW_ON_ERROR),
                'headers' => ['Content-Type' => 'application/json'],
            ]);
            /** @var array{isSuccess?: bool, url?: string, message?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            if (($data['isSuccess'] ?? false) !== true) {
                $this->logger->error('Robokassa createPaymentUrl rejected.', ['message' => $data['message'] ?? 'unknown error']);
                return null;
            }
            return $data['url'] ?? null;
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Robokassa createPaymentUrl failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verifies an inbound Result URL callback's SignatureValue against the
     * spec's documented formula (`OutSum:InvId:Пароль#2:Shp_*`, Shp_ params
     * sorted alphabetically) — see RobokassaSignatureService.
     *
     * @param array<string, string> $shpParams
     */
    public function verifyResultUrlCallback(string $outSum, string $invId, string $signatureValue, array $shpParams = []): bool
    {
        return $this->signer->verifyResultUrlSignature($outSum, $invId, $this->password2(), $signatureValue, $shpParams);
    }

    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $xml = $this->fetchOperationStateXml($providerReference);
            if ($xml === null) {
                return new PaymentVerificationResult(false, $providerReference, 'Unable to parse Robokassa response.');
            }
            $stateCode = $xml->State !== null ? (string) $xml->State->Code : '';

            // 100 = "operation successfully confirmed" per the spec's own
            // x-robokassa-state-descriptions for OperationState.Code.
            $paid = $stateCode === '100';

            return new PaymentVerificationResult($paid, $providerReference, 'State code: ' . $stateCode);
        } catch (GuzzleException $e) {
            $this->logger->warning('Robokassa verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }
    }

    /**
     * Requests a full refund via the Refund API (RefundService/Refund/Create)
     * — see this class's docblock for the OpKey lookup and Password #3
     * requirement.
     */
    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        if ($this->password3() === '') {
            return new PaymentRefundResult(
                false,
                $providerReference,
                'Robokassa refund API is not configured (requires a separate Password #3, issued by Robokassa support once the Refund API is enabled for this merchant account) — process this refund via the Robokassa merchant dashboard.',
            );
        }

        try {
            return $this->requestRefund($providerReference);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('Robokassa refund failed.', ['error' => $e->getMessage()]);
            return new PaymentRefundResult(false, $providerReference, $e->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    private function requestRefund(string $providerReference): PaymentRefundResult
    {
        $xml = $this->fetchOperationStateXml($providerReference);
        $opKey = ($xml !== null && $xml->Info !== null) ? (string) $xml->Info->OpKey : '';
        if ($opKey === '') {
            return new PaymentRefundResult(false, $providerReference, 'Unable to look up the Robokassa OpKey for this payment via OpStateExt.');
        }

        [$dataToSign, $signature] = $this->signer->signRefundJwt(['OpKey' => $opKey], $this->password3());
        $jwt = $dataToSign . '.' . $signature;

        // Unlike CreateInvoice (always HTTP 200), the spec documents real
        // 400/401 statuses here with a useful RefundCreateResponse body
        // (e.g. "Signature key is not exists...") — http_errors: false
        // so Guzzle hands back that body instead of throwing it away.
        $response = $this->httpClient->post(self::REFUND_CREATE_URL, [
            'body' => json_encode($jwt, JSON_THROW_ON_ERROR),
            'headers' => ['Content-Type' => 'application/json'],
            'http_errors' => false,
        ]);
        /** @var array{success?: bool, message?: string|null, requestId?: string|null} $data */
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        if (($data['success'] ?? false) !== true) {
            return new PaymentRefundResult(false, $providerReference, $data['message'] ?? 'Robokassa refund request rejected.');
        }

        $requestId = $data['requestId'] ?? $providerReference;
        return new PaymentRefundResult(
            true,
            $requestId,
            'Robokassa refund request accepted (requestId: ' . $requestId . '). Robokassa processes refunds asynchronously — confirm completion via Refund/GetState or the merchant dashboard.',
        );
    }

    /**
     * Shared OpStateExt call for both `verifyPayment()` and `refund()`'s
     * OpKey lookup. Returns null if Robokassa's response can't be parsed as
     * XML; may throw GuzzleException on transport failure (left to callers).
     */
    private function fetchOperationStateXml(string $invoiceId): ?\SimpleXMLElement
    {
        $signature = $this->signer->signOpState($this->login(), $invoiceId, $this->password2());

        $response = $this->httpClient->get(
            self::WEB_SERVICE_URL . '/OpStateExt',
            ['query' => [
                'MerchantLogin' => $this->login(),
                'InvoiceID' => $invoiceId,
                'Signature' => $signature,
            ]],
        );
        $xml = simplexml_load_string((string) $response->getBody());

        return $xml === false ? null : $xml;
    }

    private function login(): string
    {
        return $this->settings->getSetting('gateway_robokassa_login') ?: '';
    }

    private function password1(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_robokassa_password1') ?: '');
    }

    private function password2(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_robokassa_password2') ?: '');
    }

    private function password3(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_robokassa_password3') ?: '');
    }
}
