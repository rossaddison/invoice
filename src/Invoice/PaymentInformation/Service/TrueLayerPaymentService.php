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
use TrueLayer\Exceptions\ApiResponseUnsuccessfulException;
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
     * payload spec) so `TrueLayerWebhookHandler`/`resolveInvoiceUrlKey()`
     * can resolve the invoice. `$customerName`/`$customerEmail` are the
     * paying customer's own details (distinct from the beneficiary, which
     * is always this business's own account). The return URL is not a
     * parameter — see `returnUrl()`'s own docblock for why it's always the
     * fixed, manually-configured Setting value instead.
     */
    public function createPayment(
        float $balance,
        string $currency,
        string $urlKey,
        string $customerName,
        string $customerEmail,
    ): ?string {
        $setup = $this->resolvePaymentSetup($currency);
        if ($setup === null) {
            return null;
        }

        try {
            return $this->submitPayment($setup, $balance, $urlKey, $customerName, $customerEmail);
        } catch (TrueLayerException $e) {
            $this->logger->error('TrueLayer payment creation failed.', $this->errorLogContext($e));
            return null;
        }
    }

    /**
     * Everything `createPayment()` needs to know is valid/available before
     * it's safe to even build a TrueLayer SDK client — bundled into one
     * call, rather than createPayment() itself running each guard clause
     * in sequence, to stay under SonarQube's per-method return-count limit
     * (php:S1142). The `!== null` chain below short-circuits the same way
     * a sequence of `if (...) { return null; }` guards would, without each
     * one contributing its own `return` — currency validation, the Return
     * Url Setting, and the account-identifier inputs are resolved by their
     * own small dedicated methods (each already under the limit on its
     * own), and only the combined success/failure is returned here.
     *
     * @return array{currency: string, returnUrl: string, accountIdentifierInputs: array{name: string, iban: string, sortCode: string, accountNumber: string}}|null
     */
    private function resolvePaymentSetup(string $currency): ?array
    {
        $upperCurrency = $this->validateCurrency($currency);
        if ($upperCurrency === null) {
            return null;
        }

        $returnUrl = $this->requireReturnUrl();
        $accountIdentifierInputs = $returnUrl !== null ? $this->resolveAccountIdentifierInputs($upperCurrency) : null;
        if ($returnUrl === null || $accountIdentifierInputs === null) {
            return null;
        }

        return [
            'currency' => $upperCurrency,
            'returnUrl' => $returnUrl,
            'accountIdentifierInputs' => $accountIdentifierInputs,
        ];
    }

    private function validateCurrency(string $currency): ?string
    {
        $upperCurrency = strtoupper($currency);
        if (!in_array($upperCurrency, [PaymentCurrencies::GBP, PaymentCurrencies::EUR], true)) {
            $this->logger->error('TrueLayer only supports GBP or EUR.', ['currency' => $currency]);
            return null;
        }
        return $upperCurrency;
    }

    private function requireReturnUrl(): ?string
    {
        $returnUrl = $this->returnUrl();
        if ($returnUrl === '') {
            $this->logger->error('TrueLayer payment creation failed: no Return Url configured.');
            return null;
        }
        return $returnUrl;
    }

    /**
     * Resolves the beneficiary and validates the currency-specific fields
     * it needs are actually present — TrueLayer rejects IBAN-identified
     * beneficiaries for GBP payments outright ("GBP payments are not
     * supported to iban accounts", confirmed live 2026-08-16), so GBP
     * needs sort code + account number and EUR needs an IBAN, never the
     * other. Both currency branches' missing-field checks are combined
     * into one guard (rather than one `if`/`return` per currency) to stay
     * under SonarQube's per-method return-count limit (php:S1142).
     *
     * @return array{name: string, iban: string, sortCode: string, accountNumber: string}|null
     */
    private function resolveAccountIdentifierInputs(string $upperCurrency): ?array
    {
        $beneficiaryDetails = $this->resolveBeneficiary();
        if ($beneficiaryDetails === null) {
            $this->logger->error('TrueLayer payment creation failed: no active company found.');
            return null;
        }

        $missingGbpFields = $upperCurrency === PaymentCurrencies::GBP
            && ($beneficiaryDetails['sortCode'] === '' || $beneficiaryDetails['accountNumber'] === '');
        $missingEurFields = $upperCurrency === PaymentCurrencies::EUR && $beneficiaryDetails['iban'] === '';

        if ($missingGbpFields || $missingEurFields) {
            $this->logger->error(
                $missingGbpFields
                    ? 'TrueLayer payment creation failed: no active company BACS sort code/account number configured for GBP.'
                    : 'TrueLayer payment creation failed: no active company IBAN configured for EUR.',
            );
            return null;
        }

        return $beneficiaryDetails;
    }

    /**
     * Builds and submits the actual TrueLayer payment — all input
     * validation already happened in `resolvePaymentSetup()`, so this
     * method has exactly one `return`, well under SonarQube's per-method
     * limit (php:S1142). Left to throw `TrueLayerException` outward
     * (building the client itself, or the `create()` API call, can both
     * throw) — `createPayment()`'s own `try`/`catch` handles it.
     *
     * @param array{currency: string, returnUrl: string, accountIdentifierInputs: array{name: string, iban: string, sortCode: string, accountNumber: string}} $setup
     *
     * @throws TrueLayerException
     */
    private function submitPayment(
        array $setup,
        float $balance,
        string $urlKey,
        string $customerName,
        string $customerEmail,
    ): string {
        $client = $this->client();
        $upperCurrency = $setup['currency'];
        $beneficiaryDetails = $setup['accountIdentifierInputs'];

        // Picking the identifier type by currency, not defaulting to IBAN
        // for everything — see resolveAccountIdentifierInputs()'s own
        // docblock for why.
        if ($upperCurrency === PaymentCurrencies::GBP) {
            // This app stores the sort code as XX-XX-XX (see
            // CompanyPrivateFormFields::companyPrivateBacsSortCodeField()'s
            // own docblock) — TrueLayer wants a plain 6-digit string with
            // no separators at all ("Value must be 6 digits without
            // spaces.", confirmed live 2026-08-16). Stripping any
            // non-digit characters from both fields covers the dashes
            // here and any accidental spaces a user might type into the
            // account number field too.
            $sortCode = preg_replace('/\D/', '', $beneficiaryDetails['sortCode']) ?? '';
            $accountNumber = preg_replace('/\D/', '', $beneficiaryDetails['accountNumber']) ?? '';
            $accountIdentifier = $client->accountIdentifier()->sortCodeAccountNumber()
                ->sortCode($sortCode)
                ->accountNumber($accountNumber);
        } else {
            $accountIdentifier = $client->accountIdentifier()->iban()->iban($beneficiaryDetails['iban']);
        }

        $beneficiary = $client->beneficiary()->externalAccount()
            ->reference(substr($customerName !== '' ? $customerName : $urlKey, 0, 18))
            ->accountHolderName($beneficiaryDetails['name'])
            ->accountIdentifier($accountIdentifier);

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

        return $payment->hostedPaymentsPage()->returnUri($setup['returnUrl'])->toUrl();
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
            $this->logger->warning('TrueLayer verifyPayment failed.', $this->errorLogContext($e));
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
     * Resolves the invoice url_key carried in `metadata.invoice_url_key`
     * for a given payment. Needed because `trueLayerComplete()` is reached
     * via a fixed, no-suffix return URL (see `returnUrl()`'s own docblock)
     * rather than a per-invoice route parameter — TrueLayer appends only
     * `?payment_id=...` to that return URL, confirmed directly against
     * TrueLayer's own docs, so the invoice itself has to be resolved from
     * the payment's own metadata, not the URL.
     */
    public function resolveInvoiceUrlKey(string $providerReference): ?string
    {
        try {
            $payment = $this->client()->getPayment($providerReference);
        } catch (TrueLayerException $e) {
            $this->logger->warning('TrueLayer resolveInvoiceUrlKey failed.', $this->errorLogContext($e));
            return null;
        }

        /** @var array{invoice_url_key?: string} $metadata */
        $metadata = $payment->getMetadata();

        return $metadata['invoice_url_key'] ?? null;
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
     * Resolves this business's own account-holder name plus both possible
     * account-identifier shapes (IBAN and BACS sort code/account number —
     * CompanyPrivate already has dedicated fields for both) to receive
     * payment into. `createPayment()` picks whichever one the payment's
     * own currency actually requires — TrueLayer rejects IBAN-identified
     * beneficiaries for GBP payments outright, confirmed live 2026-08-16.
     * Same "active Company → active CompanyPrivate" chain this app's
     * existing Tink Open Banking integration already uses
     * (OpenBankingPaymentService::initiateTinkPayment()), not a new
     * Settings field.
     *
     * @return array{iban: string, sortCode: string, accountNumber: string, name: string}|null
     */
    private function resolveBeneficiary(): ?array
    {
        $company = $this->compR->repoCompanyActivequery();
        $name = $company instanceof Company ? $company->getName() : null;
        // Combined into one guard, rather than one `if`/`return` per
        // check, to stay under SonarQube's per-method return-count limit
        // (php:S1142) — the `instanceof` re-check right here (not just
        // relied on via $name above) is what lets Psalm narrow $company
        // to Company for the rest of this method.
        if (!$company instanceof Company || $name === null || $name === '') {
            return null;
        }

        /** @var CompanyPrivate $companyPrivate */
        foreach ($company->getCompanyPrivates() as $companyPrivate) {
            if ($companyPrivate->isActiveToday()) {
                return [
                    'iban' => $companyPrivate->getIban() ?? '',
                    'sortCode' => $companyPrivate->getBacsSortCode() ?? '',
                    'accountNumber' => $companyPrivate->getBacsAccountNumber() ?? '',
                    'name' => $name,
                ];
            }
        }

        return null;
    }

    /**
     * A fixed URL manually entered in Settings, never dynamically
     * generated per-invoice — TrueLayer requires
     * `authorization_flow.redirect.return_uri` to exactly match a Redirect
     * URI pre-registered in Console (Settings > Redirect URIs), confirmed
     * directly against TrueLayer's own docs. Dynamically generating it via
     * this app's own UrlGeneratorInterface would risk it varying between
     * requests depending on the Locale middleware's per-request default
     * argument state (the same mechanism behind the entire August 2026
     * Checkout.com "Pay Now" redirect-loop investigation) — a fixed
     * Setting sidesteps that risk entirely rather than merely arguing it
     * away. Not encrypted — the 'returnUrl' field is 'text' type, matching
     * Amazon Pay's own gateway_amazon_pay_returnUrl precedent for this
     * exact class of problem.
     */
    private function returnUrl(): string
    {
        return $this->settings->getSetting('gateway_truelayer_returnUrl') ?: '';
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

    /**
     * The SDK's plain TrueLayerException (thrown for e.g. a signer
     * failure) only ever has a short getMessage() to go on. Its own
     * ApiResponseUnsuccessfulException — thrown whenever TrueLayer's API
     * itself returns a non-2xx response — carries far more: the exact
     * field-level validation errors, TrueLayer's own trace_id (needed if
     * this ever needs raising with their support), and a human-readable
     * detail string. Extracting all of it here, rather than just
     * getMessage(), is what actually made an "Invalid Parameters"-class
     * response diagnosable in practice — same reasoning as
     * CheckoutComPaymentService's own errorLogContext().
     *
     * @return array<string, mixed>
     */
    private function errorLogContext(TrueLayerException $e): array
    {
        if (!$e instanceof ApiResponseUnsuccessfulException) {
            return ['error' => $e->getMessage()];
        }

        return [
            'error' => $e->getMessage(),
            'status_code' => $e->getStatusCode(),
            'title' => $e->getTitle(),
            'type' => $e->getType(),
            'detail' => $e->getDetail(),
            'trace_id' => $e->getTraceId(),
            'errors' => $e->getErrors(),
        ];
    }
}
