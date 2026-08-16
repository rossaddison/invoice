<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use Checkout\CheckoutApi;
use Checkout\CheckoutApiException;
use Checkout\CheckoutArgumentException;
use Checkout\CheckoutSdk;
use Checkout\CheckoutSdkBuilder;
use Checkout\Environment;
use Checkout\HttpClientBuilderInterface;
use Checkout\Payments\Links\PaymentLinkRequest;
use Checkout\Payments\Links\PaymentLinksClient;
use Checkout\Payments\PaymentsClient;
use Checkout\Payments\RefundRequest;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Built against Checkout.com's Payment Links API (`POST /payment-links`,
 * an Order-based hosted checkout page redirecting to
 * `pay.sandbox.checkout.com/link/{id}` — the same conceptual role
 * Square/Razorpay/Mercado Pago's own hosted-link products play in this
 * app), via the official `checkout/checkout-sdk-php` package
 * (github.com/checkout/checkout-sdk-php, actively maintained, v5.3.0
 * released 2026-08-06 — confirmed 2026-08-11) — **installed as a real
 * composer dependency**, unlike Razorpay/PayPal/Square/Mercado Pago,
 * because its own HTTP layer genuinely is `guzzlehttp/guzzle` (^7.4,
 * confirmed directly from the SDK's own composer.json), the same client
 * every mockable gateway in this app (Stripe, Mollie, GoCardless) is
 * already built and tested against — the same reasoning that kept
 * Razorpay/PayPal/Square/Mercado Pago SDK-free cuts the other way here.
 *
 * Every endpoint, field, and auth detail below was ground-truthed
 * directly against that SDK's real executable source (`ApiClient.php`,
 * `SdkAuthorization.php`, `StaticKeysSdkCredentials.php`,
 * `Environment.php`, `Payments/Links/PaymentLinksClient.php`,
 * `Payments/PaymentsClient.php`) — confirmed 2026-08-11:
 *
 * - Base URLs: `api.sandbox.checkout.com` / `api.checkout.com` —
 *   genuinely a different base URL per environment, like PayPal/Square,
 *   not just a different credential.
 * - Auth: `Authorization: Bearer {secretKey}` — a single static secret
 *   key (`sk_...`/`sk_sbox_...`), matching the SDK's own validated
 *   format (confirmed via `CheckoutStaticKeysSdkBuilder`'s own regex).
 *   `publicKey` is genuinely optional (the SDK's own
 *   `validatePublicKey()` returns early on empty) — only needed for
 *   client-side tokenization this app's hosted-redirect flow never
 *   uses, but the Settings field is kept for parity/future use.
 * - `POST /payment-links`: the created link's redirect URL is
 *   `_links.redirect.href` in the response (confirmed via Checkout.com's
 *   own current API reference, since the SDK itself returns a raw
 *   array here with no typed response model).
 * - `GET /payments/{id}` for verification, `POST /payments/{id}/refunds`
 *   for refunds.
 *
 * Not independently re-confirmed via a raw example response this
 * session: the exact `status` string casing for a fully captured
 * payment (`'Captured'`, based on Checkout.com's own general
 * documentation conventions, not a fetched raw JSON example) — flagged
 * here the same way PayPal's own docblock flags its one unconfirmed
 * field.
 */
final class CheckoutComPaymentService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        /**
         * Injectable for tests only — a real Guzzle client with a
         * MockHandler stack, wrapped in the tiny HttpClientBuilderInterface
         * adapter below. Production never passes this; the SDK builds its
         * own default Guzzle client when omitted.
         */
        private readonly ?GuzzleClientInterface $httpClient = null,
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'checkout_com';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->secretKey() !== '';
    }

    public function isSandbox(): bool
    {
        return $this->settings->getSetting('gateway_checkout_com_sandbox') === '1';
    }

    /**
     * Creates a Payment Link and returns its hosted redirect URL, or null
     * on any failure (logged either way).
     */
    public function createPaymentLink(
        float $balance,
        string $currency,
        string $reference,
        string $description,
        string $returnUrl,
    ): ?string {
        $request = new PaymentLinkRequest();
        $request->amount = (int) round($balance * 100);
        $request->currency = strtoupper($currency);
        $request->reference = $reference;
        $request->description = $description;
        $request->return_url = $returnUrl;
        $processingChannelId = $this->processingChannelId();
        if ($processingChannelId !== '') {
            $request->processing_channel_id = $processingChannelId;
        }

        try {
            /** @var array{_links?: array{redirect?: array{href?: string}}} $response */
            $response = $this->paymentLinksClient()->createPaymentLink($request);
        } catch (CheckoutApiException|CheckoutArgumentException $e) {
            $this->logger->error('Checkout.com payment link creation failed.', $this->errorLogContext($e));
            return null;
        }

        $redirectUrl = $response['_links']['redirect']['href'] ?? null;
        if (!is_string($redirectUrl) || $redirectUrl === '') {
            $this->logger->error('Checkout.com payment link response missing redirect URL.', [
                'response' => $response,
            ]);
            return null;
        }

        return $redirectUrl;
    }

    /**
     * Authoritatively confirms a payment's status by asking Checkout.com
     * directly, rather than trusting the webhook body's own `status`
     * field alone — the same belt-and-braces re-check every gateway in
     * this app performs before ever marking an invoice paid.
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $payment = $this->paymentsClient()->getPaymentDetails($providerReference);
        } catch (CheckoutApiException|CheckoutArgumentException $e) {
            $this->logger->warning('Checkout.com verifyPayment failed.', $this->errorLogContext($e));
            return new PaymentVerificationResult(
                paid: false,
                providerReference: $providerReference,
                message: $e->getMessage(),
            );
        }

        $status = (string) ($payment['status'] ?? '');

        return new PaymentVerificationResult(
            paid: $status === 'Captured',
            providerReference: $providerReference,
            message: $status,
        );
    }

    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $request = new RefundRequest();
        $request->amount = (int) round($amount * 100);

        try {
            $response = $this->paymentsClient()->refundPayment($providerReference, $request);

            return new PaymentRefundResult(
                refunded: true,
                providerReference: (string) ($response['action_id'] ?? $providerReference),
                message: 'Refund accepted.',
            );
        } catch (CheckoutApiException|CheckoutArgumentException $e) {
            $this->logger->error('Checkout.com refund failed.', $this->errorLogContext($e) + [
                'payment_id' => $providerReference,
            ]);
            return new PaymentRefundResult(
                refunded: false,
                providerReference: $providerReference,
                message: $e->getMessage(),
            );
        }
    }

    public function webhookSigningKey(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_checkout_com_webhookSecret') ?: '');
    }

    /**
     * The SDK's own fluent builder methods (staticKeys(), secretKey(),
     * environment(), publicKey(), httpClientBuilder(), build()) declare no
     * return types at all — every one of them genuinely returns `$this`
     * (or, for build(), a real CheckoutApi), confirmed by reading each
     * method's actual source, but PHP/Psalm has no way to know that from
     * the SDK's own untyped signatures. Explicit @var annotations at each
     * step are the structural fix (this project's own convention:
     * @psalm-suppress is never the answer, see
     * docs/CYCLE_ORM_PSALM_LIFECYCLE_SAFE_ENTITIES.md for the same
     * reasoning applied to a different untyped-third-party-library case).
     */
    private function buildApi(): CheckoutApi
    {
        /** @var CheckoutSdkBuilder $sdkBuilder */
        $sdkBuilder = CheckoutSdk::builder();
        $builder = $sdkBuilder->staticKeys();
        $builder->secretKey($this->secretKey());
        $builder->environment($this->isSandbox() ? Environment::sandbox() : Environment::production());

        $publicKey = $this->publicKey();
        if ($publicKey !== '') {
            $builder->publicKey($publicKey);
        }

        // Accounts on Checkout.com's newer Unified Payments platform are
        // scoped to an account-specific hostname prefix (shown on the
        // account's own "Connection Details" page as e.g.
        // https://{subdomain}.api.sandbox.checkout.com) — every request
        // sent to the plain, unprefixed api.sandbox.checkout.com host gets
        // rejected with a 401 even with an otherwise fully valid secret
        // key, confirmed live against a real sandbox account during the
        // August 2026 "Pay Now" investigation. Optional: accounts not on
        // that platform have no subdomain to enter and this is skipped.
        $environmentSubdomain = $this->environmentSubdomain();
        if ($environmentSubdomain !== '') {
            $builder->environmentSubdomain($environmentSubdomain);
        }

        if ($this->httpClient !== null) {
            $builder->httpClientBuilder($this->testHttpClientBuilder($this->httpClient));
        }

        return $builder->build();
    }

    private function paymentLinksClient(): PaymentLinksClient
    {
        return $this->buildApi()->getPaymentLinksClient();
    }

    private function paymentsClient(): PaymentsClient
    {
        return $this->buildApi()->getPaymentsClient();
    }

    private function testHttpClientBuilder(GuzzleClientInterface $client): HttpClientBuilderInterface
    {
        return new class ($client) implements HttpClientBuilderInterface {
            public function __construct(private readonly GuzzleClientInterface $client)
            {
            }

            #[\Override]
            public function getClient(): GuzzleClientInterface
            {
                return $this->client;
            }
        };
    }

    private function secretKey(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_checkout_com_secretKey') ?: '');
    }

    private function publicKey(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_checkout_com_publicKey') ?: '');
    }

    /**
     * Required, not optional, whenever the account has more than one
     * processing channel (or none marked default) — confirmed live
     * against a real sandbox account, whose Payment Link creation calls
     * failed with a `processing_channel_id_required` (422) API error
     * until this was set.
     */
    private function processingChannelId(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_checkout_com_processingChannelId') ?: '');
    }

    /**
     * Not encrypted — the 'environmentSubdomain' field is 'text' type, not
     * 'password' (see SettingPaymentTrait::checkoutComGatewayFields()), so
     * unlike secretKey()/publicKey()/processingChannelId() this is never
     * passed through decode().
     */
    private function environmentSubdomain(): string
    {
        return $this->settings->getSetting('gateway_checkout_com_environmentSubdomain') ?: '';
    }

    /**
     * @return array<string, mixed>
     */
    private function errorLogContext(CheckoutApiException|CheckoutArgumentException $e): array
    {
        if (!$e instanceof CheckoutApiException) {
            return ['error' => $e->getMessage()];
        }

        return [
            'error' => $e->getMessage(),
            'error_details' => $e->error_details,
        ];
    }
}
