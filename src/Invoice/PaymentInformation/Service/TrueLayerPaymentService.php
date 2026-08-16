<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Log\LoggerInterface;
use TrueLayer\Client as TrueLayerClient;
use TrueLayer\Constants\Countries;
use TrueLayer\Constants\CustomerSegments;
use TrueLayer\Constants\PaymentCurrencies;
use TrueLayer\Constants\PaymentStatus;
use TrueLayer\Constants\ReleaseChannels;
use TrueLayer\Exceptions\Exception as TrueLayerException;
use TrueLayer\Interfaces\Client\ClientInterface;

/**
 * Built against TrueLayer's Payments V3 API (the current version — V2, aka
 * "Single Immediate Payments", is officially deprecated per
 * docs.truelayer.com/docs/migrating-to-the-payments-api-v3, confirmed
 * 2026-08-16), a UK/EU Open Banking payment-initiation provider, via
 * TrueLayer's own official `truelayer/client` composer package (not just
 * `truelayer/signing`, which it depends on internally) — genuinely
 * installed as a real dependency rather than hand-rolling raw HTTP calls,
 * since it correctly handles authentication/token caching, request
 * signing, idempotency-key retry, and typed request/response objects, the
 * same reasoning that justified installing checkout/checkout-sdk-php
 * (42k+ installs, actively maintained, confirmed via Packagist 2026-08-16).
 * It requires a PSR-18 HTTP client — this app already depends on Guzzle 7,
 * which implements PSR-18 natively.
 *
 * Every field/endpoint/limitation below was ground-truthed directly
 * against the vendored SDK's own source and README — confirmed 2026-08-16:
 *
 * - `useProduction(bool)` toggles sandbox/live — sandbox is the SDK's own
 *   default.
 * - `PaymentCurrencies` only defines GBP/EUR — no other currency is
 *   supported at all.
 * - The beneficiary is always `externalAccount()` with an IBAN, paying
 *   directly into this business's own bank account — deliberately not
 *   TrueLayer's `merchantAccount()` concept, which needs a separate
 *   funded-account setup step this app has no use for. The IBAN (and this
 *   business's own account-holder name) is resolved via
 *   `CompanyRepository`/`CompanyPrivate::getIban()` — the same pattern
 *   this app's existing Tink Open Banking integration already uses
 *   (`OpenBankingPaymentService::initiateTinkPayment()`), not a new
 *   Settings field.
 * - **Refunds are only supported for settled merchant-account payments**
 *   (confirmed directly in the SDK's own README, "Refunds" section) — since
 *   this integration never uses a merchant account, `refund()` always
 *   returns a clear "not supported" result rather than attempting a call
 *   guaranteed to fail. Refunds for TrueLayer payments must be issued
 *   manually (a normal bank transfer back to the customer) outside this
 *   app, same as this app's own documented Amazon Pay refund limitation.
 * - Terminal status for a payment into an `external_account` beneficiary
 *   is `executed`, confirmed via TrueLayer's own support docs — `settled`
 *   only ever appears for payments into a TrueLayer `merchant_account`.
 * - Webhooks are verified via `TrueLayer\Webhook::configure()`, which only
 *   needs the sandbox/production flag (not this app's own client
 *   credentials) since it checks the incoming signature against
 *   TrueLayer's own published JWKS, not a shared secret — see
 *   `TrueLayerWebhookHandler`, not this class.
 */
final class TrueLayerPaymentService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly CompanyRepository $compR,
        private readonly LoggerInterface $logger,
        /**
         * Injectable for tests only — a real Guzzle client with a
         * MockHandler stack. Production never passes this; a default
         * Guzzle client is built when omitted, matching
         * CheckoutComPaymentService's own convention.
         */
        private readonly ?HttpClientInterface $httpClient = null,
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'truelayer';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->signingKid() !== ''
            && $this->privateKey() !== '';
    }

    public function isSandbox(): bool
    {
        return $this->settings->getSetting('gateway_truelayer_sandbox') === '1';
    }

    /**
     * Creates a Payments V3 payment and returns its Hosted Payments Page
     * redirect URL, or null on any failure (logged either way). `$urlKey`
     * is this app's own invoice url_key — carried through in `metadata`
     * (not a top-level "reference" field; `payment_executed` webhooks
     * don't echo one back, confirmed directly against TrueLayer's webhook
     * payload spec) so `TrueLayerWebhookHandler` can resolve the invoice.
     * `$customerName`/`$customerEmail` are the paying customer's own
     * details (distinct from the beneficiary, which is always this
     * business's own account).
     */
    public function createPayment(
        float $balance,
        string $currency,
        string $urlKey,
        string $customerName,
        string $customerEmail,
        string $returnUrl,
    ): ?string {
        $upperCurrency = strtoupper($currency);
        if (!in_array($upperCurrency, [PaymentCurrencies::GBP, PaymentCurrencies::EUR], true)) {
            $this->logger->error('TrueLayer only supports GBP or EUR.', ['currency' => $currency]);
            return null;
        }

        $beneficiaryDetails = $this->resolveBeneficiary();
        if ($beneficiaryDetails === null) {
            $this->logger->error('TrueLayer payment creation failed: no active company IBAN configured.');
            return null;
        }

        try {
            $client = $this->client();

            $beneficiary = $client->beneficiary()->externalAccount()
                ->reference(substr($customerName !== '' ? $customerName : $urlKey, 0, 18))
                ->accountHolderName($beneficiaryDetails['name'])
                ->accountIdentifier(
                    $client->accountIdentifier()->iban()->iban($beneficiaryDetails['iban']),
                );

            // The SDK's own ClientInterface::providerFilter() declares a
            // return type of ProviderFilterInterface, but
            // UserSelectedProviderSelectionInterface::filter() type-hints
            // the concrete ProviderFilter class, not the interface — a
            // real inconsistency in the SDK's own type declarations
            // (confirmed 2026-08-16), not anything this app controls.
            // Explicit @var is the structural fix, matching
            // CheckoutComPaymentService::buildApi()'s own docblock for the
            // same class of untyped/mistyped third-party SDK return.
            /** @var \TrueLayer\Entities\Provider\ProviderSelection\ProviderFilter $filter */
            $filter = $client->providerFilter()
                ->countries([Countries::GB])
                ->customerSegments([CustomerSegments::RETAIL])
                ->releaseChannel(ReleaseChannels::GENERAL_AVAILABILITY);

            $providerSelection = $client->providerSelection()->userSelected()->filter($filter);

            $paymentMethod = $client->paymentMethod()->bankTransfer()
                ->beneficiary($beneficiary)
                ->providerSelection($providerSelection);

            $user = $client->user()->name($customerName);
            if ($customerEmail !== '') {
                $user->email($customerEmail);
            }

            $payment = $client->payment()
                ->user($user)
                ->amountInMinor((int) round($balance * 100))
                ->currency($upperCurrency)
                ->metadata(['invoice_url_key' => $urlKey])
                ->paymentMethod($paymentMethod)
                ->create();

            return $payment->hostedPaymentsPage()->returnUri($returnUrl)->toUrl();
        } catch (TrueLayerException $e) {
            $this->logger->error('TrueLayer payment creation failed.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Authoritatively confirms a payment's status by asking TrueLayer
     * directly. `executed` is the terminal success status for a payment
     * into an `external_account` beneficiary (not `settled`, which only
     * applies to TrueLayer's own `merchant_account` concept — see this
     * class's own docblock).
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $payment = $this->client()->getPayment($providerReference);
        } catch (TrueLayerException $e) {
            $this->logger->warning('TrueLayer verifyPayment failed.', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(
                paid: false,
                providerReference: $providerReference,
                message: $e->getMessage(),
            );
        }

        $status = $payment->getStatus();

        return new PaymentVerificationResult(
            paid: $status === PaymentStatus::EXECUTED,
            providerReference: $providerReference,
            message: $status,
        );
    }

    /**
     * Always unsupported for this integration — see this class's own
     * docblock. TrueLayer only allows refunding settled merchant-account
     * payments; this integration pays directly into an external account
     * and has no merchant account, so a TrueLayer refund call would always
     * fail regardless of the payment's own state.
     */
    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $this->logger->warning('TrueLayer refund attempted — not supported for external-account payments.', [
            'payment_id' => $providerReference,
        ]);

        return new PaymentRefundResult(
            refunded: false,
            providerReference: $providerReference,
            message: 'TrueLayer only supports refunds for settled merchant-account payments; '
                . 'this integration pays directly into an external account, so this payment '
                . 'must be refunded manually via a normal bank transfer.',
        );
    }

    public function isSandboxForWebhook(): bool
    {
        return $this->isSandbox();
    }

    public function httpClientForWebhook(): HttpClientInterface
    {
        return $this->httpClient();
    }

    private function client(): ClientInterface
    {
        return TrueLayerClient::configure()
            ->clientId($this->clientId())
            ->clientSecret($this->clientSecret())
            ->keyId($this->signingKid())
            ->pem($this->privateKey())
            ->useProduction(!$this->isSandbox())
            ->httpClient($this->httpClient())
            ->create();
    }

    private function httpClient(): HttpClientInterface
    {
        return $this->httpClient ?? new GuzzleClient();
    }

    /**
     * Resolves this business's own IBAN (and account-holder name — the
     * receiving account's own holder name, distinct from the paying
     * customer) to receive payment into. Same "active Company → active
     * CompanyPrivate → IBAN" chain this app's existing Tink Open Banking
     * integration already uses
     * (OpenBankingPaymentService::initiateTinkPayment()), not a new
     * Settings field.
     *
     * @return array{iban: string, name: string}|null
     */
    private function resolveBeneficiary(): ?array
    {
        $company = $this->compR->repoCompanyActivequery();
        if (!$company instanceof Company) {
            return null;
        }

        $name = $company->getName();
        if ($name === null || $name === '') {
            return null;
        }

        /** @var CompanyPrivate $companyPrivate */
        foreach ($company->getCompanyPrivates() as $companyPrivate) {
            if ($companyPrivate->isActiveToday()) {
                $iban = $companyPrivate->getIban();
                return $iban !== null ? ['iban' => $iban, 'name' => $name] : null;
            }
        }

        return null;
    }

    private function clientId(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_truelayer_clientId') ?: '');
    }

    private function clientSecret(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_truelayer_clientSecret') ?: '');
    }

    private function signingKid(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_truelayer_signingKid') ?: '');
    }

    private function privateKey(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_truelayer_privateKey') ?: '');
    }
}
