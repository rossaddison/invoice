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
 * `createInvoice` and `getOperationState`) — not third-party sources.
 *
 * Both `createPaymentUrl()` (CreateInvoice) and `verifyPayment()`
 * (OpStateExt) are explicitly documented by Robokassa as
 * production-mode-only (`x-robokassa-environment: testSupported: false`) —
 * there is no test/sandbox variant of either endpoint, confirming what the
 * user independently found by checking Robokassa's own site directly:
 * there is no separate sandbox environment for this API surface. Robokassa's
 * `IsTest=1` flag only applies to the legacy `Merchant/Index.aspx`
 * query-string redirect scheme, which this integration deliberately does not
 * use (per this project's "latest technology" direction). Accordingly there
 * is exactly one credential set (`login`/`password1`/`password2`), no
 * separate test-mode passwords.
 *
 * `verifyPayment()`'s State.Code === 100 ("Операция успешно подтверждена" /
 * "operation successfully confirmed") is the spec's own documented meaning
 * for that code — not a guess.
 *
 * Robokassa has no documented refund API; `refund()` always reports
 * unrefunded with a message directing the merchant to Robokassa's own
 * dashboard, the same honest limitation this app already accepts for Amazon
 * Pay refunds.
 */
final class RobokassaPaymentService implements PaymentGatewayInterface
{
    private const string JWT_INVOICE_URL = 'https://services.robokassa.ru/InvoiceServiceWebApi/api/CreateInvoice';
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
        $signature = $this->signer->signOpState($this->login(), $providerReference, $this->password2());

        try {
            $response = $this->httpClient->get(
                self::WEB_SERVICE_URL . '/OpStateExt',
                ['query' => [
                    'MerchantLogin' => $this->login(),
                    'InvoiceID' => $providerReference,
                    'Signature' => $signature,
                ]],
            );
            $xml = simplexml_load_string((string) $response->getBody());
            if ($xml === false) {
                return new PaymentVerificationResult(false, $providerReference, 'Unable to parse Robokassa response.');
            }
            $stateCode = (string) $xml->State->Code;

            // 100 = "operation successfully confirmed" per the spec's own
            // x-robokassa-state-descriptions for OperationState.Code.
            $paid = $stateCode === '100';

            return new PaymentVerificationResult($paid, $providerReference, 'State code: ' . $stateCode);
        } catch (GuzzleException $e) {
            $this->logger->warning('Robokassa verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        return new PaymentRefundResult(
            false,
            $providerReference,
            'Robokassa has no documented refund API — process this refund via the Robokassa merchant dashboard.',
        );
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
}
