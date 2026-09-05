<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use RossAddison\BitPayClient\BitPayClient;
use RossAddison\BitPayClient\Exception\BitPayApiException;
use RossAddison\BitPayClient\Model\CreateInvoiceRequest;

/**
 * BitPay — a cryptocurrency (Bitcoin and other chains) payment gateway, this
 * app's first non-fiat integration. Built against the hand-written
 * `rossaddison/bitpay-client` package (POS facade only) rather than BitPay's
 * own official `bitpay/sdk` — that SDK requires `symfony/console ^7.3.1`
 * across its entire published version history, which conflicts outright
 * with this app's own root `>=8.1.6` no-ceiling pin (confirmed via
 * `composer require bitpay/sdk --dry-run --with-all-dependencies`, not just
 * assumed). See `rossaddison/bitpay-client`'s own README for the full
 * ground-truthing of every BitPay API behavior this class relies on.
 *
 * The POS facade needs only a single token (no ECDSA client-identity
 * key-pairing, unlike BitPay's merchant facade) — confirmed directly
 * against BitPay's own OpenAPI reference: `X-Identity`/`X-Signature` are
 * "optional for this endpoint when using the public facade, and required
 * when using a `merchant` facade token" on both invoice-creation and
 * invoice-lookup. That token doubles as the webhook HMAC secret (see
 * `verifyWebhookSignature()`).
 *
 * `complete` — not `confirmed` — is the correct "settled, safe to mark
 * paid" bar for this app's accounting: BitPay's own docs state invoices are
 * only credited to the merchant account once they reach `complete` (see
 * `RossAddison\BitPayClient\Model\Invoice::isSettled()`'s own docblock for
 * the full status lifecycle). `verifyPayment()` here always re-fetches the
 * invoice from BitPay rather than trusting any caller-supplied status, the
 * same belt-and-braces pattern every gateway in this app follows.
 *
 * Refunds are always reported unsupported — BitPay's `/refunds` endpoint
 * genuinely requires the merchant facade (ECDSA key-pairing), which this
 * integration deliberately doesn't implement (see the composer-conflict
 * reasoning above); a BitPay refund must be issued manually via the
 * merchant dashboard, the same documented limitation this app already
 * accepts for TrueLayer (`TrueLayerPaymentService::refund()`), for an
 * unrelated reason.
 */
final class BitPayPaymentService implements PaymentGatewayInterface
{
    /**
     * The human-readable reason `createPayment()` last failed for, if any —
     * read by `BitPayPaymentController::bitPayInForm()` so the person
     * setting up this gateway (almost always the site admin testing it,
     * not a paying customer, since a broken gateway wouldn't be enabled
     * for real customers yet) sees *why* it failed, not just a generic
     * "please try again" — the same gap every gateway in this app
     * otherwise has (the real reason only ever reached `runtime/logs/
     * app.log`, confirmed live 2026-09-05 against a real BitPay sandbox
     * account whose merchant setup wasn't yet complete: BitPay's own
     * `POST /invoices` responded 500 with `{"error":"Account not setup
     * completely yet."}`, and the only visible symptom otherwise was a
     * bare not-found page with zero diagnostic value).
     */
    private string $lastErrorMessage = '';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        /**
         * Injectable for tests only — a real Guzzle client with a
         * MockHandler stack. Production never passes this; BitPayClient
         * builds its own default Guzzle client when omitted. Matches
         * TrueLayerPaymentService's own convention.
         */
        private readonly ?HttpClientInterface $httpClient = null,
    ) {
    }

    /**
     * The reason `createPayment()` last returned null, or '' if it hasn't
     * failed (yet) on this instance. See this class's own property
     * docblock for why this exists.
     */
    public function lastErrorMessage(): string
    {
        return $this->lastErrorMessage;
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'bitpay';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->posToken() !== '';
    }

    public function isSandbox(): bool
    {
        return $this->settings->getSetting('gateway_bitpay_sandbox') === '1';
    }

    /**
     * Creates a BitPay invoice and returns its id (used for later
     * verification) alongside the hosted checkout URL to redirect the
     * customer to. `$orderId` is this app's own invoice url_key, echoed
     * back unchanged on the webhook notification's own `orderId` field
     * (confirmed directly against BitPay's own IPN payload documentation)
     * — that's how `BitPayWebhookHandler` resolves the invoice.
     * `$buyerEmail` is genuinely optional; BitPay's POS facade doesn't
     * require it, unlike Paystack.
     *
     * Returns null on any failure, logging the detail for a maintainer.
     *
     * @return array{invoiceId: string, url: string}|null
     */
    public function createPayment(
        float $balance,
        string $currency,
        string $orderId,
        string $redirectUrl,
        string $notificationUrl,
        string $buyerEmail = '',
    ): ?array {
        try {
            $invoice = $this->client()->createInvoice(new CreateInvoiceRequest(
                price: $balance,
                currency: strtoupper($currency),
                orderId: $orderId,
                redirectUrl: $redirectUrl,
                notificationUrl: $notificationUrl,
                buyerEmail: $buyerEmail !== '' ? $buyerEmail : null,
            ));
        } catch (BitPayApiException $e) {
            $this->lastErrorMessage = $this->extractApiErrorMessage($e);
            $this->logger->error('BitPay createInvoice failed.', [
                'error' => $e->getMessage(),
                'status_code' => $e->statusCode,
            ]);
            return null;
        } catch (GuzzleException $e) {
            $this->lastErrorMessage = $e->getMessage();
            $this->logger->error('BitPay createInvoice failed (transport).', ['error' => $e->getMessage()]);
            return null;
        }

        return ['invoiceId' => $invoice->id, 'url' => $invoice->url];
    }

    /**
     * Authoritatively confirms a BitPay invoice's status by asking BitPay
     * directly — never trusts a webhook's own body. See this class's own
     * docblock for why `complete`, not `confirmed`, is the settled bar.
     */
    #[\Override]
    public function verifyPayment(string $providerReference): PaymentVerificationResult
    {
        try {
            $invoice = $this->client()->getInvoice($providerReference);
        } catch (BitPayApiException $e) {
            $this->logger->warning('BitPay verifyPayment failed.', [
                'error' => $e->getMessage(),
                'status_code' => $e->statusCode,
            ]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        } catch (GuzzleException $e) {
            $this->logger->warning('BitPay verifyPayment failed (transport).', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, $providerReference, $e->getMessage());
        }

        return new PaymentVerificationResult($invoice->isSettled(), $providerReference, $invoice->status);
    }

    /**
     * Always unsupported — see this class's own docblock. BitPay's
     * `/refunds` endpoint requires the merchant facade (ECDSA
     * key-pairing), which this POS-facade-only integration doesn't
     * implement.
     */
    #[\Override]
    public function refund(string $providerReference, float $amount): PaymentRefundResult
    {
        $this->logger->warning('BitPay refund attempted — not supported via the POS facade.', [
            'invoice_id' => $providerReference,
        ]);

        return new PaymentRefundResult(
            refunded: false,
            providerReference: $providerReference,
            message: 'BitPay refunds require the merchant facade (ECDSA key-pairing), which this '
                . 'integration does not implement; issue this refund manually via the BitPay merchant dashboard.',
        );
    }

    /**
     * Delegates to `BitPayClient::verifyWebhookSignature()` — see that
     * method's own docblock (and `rossaddison/bitpay-client`'s README) for
     * the full ground-truthing of the `x-signature` HMAC algorithm, and the
     * one caveat that isn't independently confirmed against a real
     * BitPay-signed webhook.
     */
    public function verifyWebhookSignature(string $rawBody, string $signatureHeader): bool
    {
        $result = $this->client()->verifyWebhookSignature($rawBody, $signatureHeader);
        if (!$result) {
            $this->logSignatureDiagnostics($rawBody, $signatureHeader);
        }
        return $result;
    }

    /**
     * TEMPORARY — remove once the real formula is confirmed against a live
     * BitPay-signed webhook (see `RossAddison\BitPayClient\BitPayClient::
     * verifyWebhookSignature()`'s own docblock: the reencode-based formula
     * it implements was never independently confirmed, and confirmed live
     * 2026-09-05 to reject every real BitPay webhook delivery). Logs
     * several candidate HMAC formulas against the SAME real raw body and
     * received signature so the actual correct one can be identified from
     * a real retry, rather than guessed a third time.
     */
    private function logSignatureDiagnostics(string $rawBody, string $signatureHeader): void
    {
        $token = $this->posToken();
        $reencoded = $this->reencodeForDiagnostics($rawBody);

        $candidates = [
            'raw_base64' => base64_encode(hash_hmac('sha256', $rawBody, $token, true)),
            'raw_hex' => hash_hmac('sha256', $rawBody, $token),
        ];
        if (is_string($reencoded)) {
            $candidates['reencoded_base64'] = base64_encode(hash_hmac('sha256', $reencoded, $token, true));
            $candidates['reencoded_hex'] = hash_hmac('sha256', $reencoded, $token);
        }

        $this->logger->warning('BitPay webhook signature diagnostics.', [
            'received_signature' => $signatureHeader,
            'candidates' => $candidates,
            'raw_body' => $rawBody,
        ]);
    }

    private function client(): BitPayClient
    {
        return new BitPayClient(
            token: $this->posToken(),
            http: $this->httpClient,
            baseUri: $this->isSandbox() ? BitPayClient::TEST_BASE_URI : BitPayClient::PRODUCTION_BASE_URI,
        );
    }

    private function posToken(): string
    {
        return (string) $this->settings->decode($this->settings->getSetting('gateway_bitpay_posToken') ?: '');
    }

    /**
     * TEMPORARY — see `logSignatureDiagnostics()`'s own docblock. Parses
     * then re-encodes $rawBody with JSON_UNESCAPED_SLASHES|
     * JSON_UNESCAPED_UNICODE — the same transform `BitPayClient::
     * verifyWebhookSignature()` applies — or returns null when $rawBody
     * isn't a JSON object/array.
     */
    private function reencodeForDiagnostics(string $rawBody): ?string
    {
        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $reencoded = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $reencoded !== false ? $reencoded : null;
    }

    /**
     * BitPay's own error responses are consistently `{"error": "..."}` —
     * confirmed live, e.g. `{"error":"Account not setup completely yet."}`
     * for a merchant account with an unfinished setup step. Falls back to
     * the exception's own generic "BitPay API responded {status}: {body}"
     * message when the body isn't that shape (or isn't valid JSON at all),
     * so `lastErrorMessage()` is never empty on a real failure.
     */
    private function extractApiErrorMessage(BitPayApiException $e): string
    {
        try {
            $decoded = json_decode($e->responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            $this->logger->warning('BitPay error body was not valid JSON.', ['error' => $jsonException->getMessage()]);
            return $e->getMessage();
        }

        if (!is_array($decoded) || !isset($decoded['error']) || !is_string($decoded['error']) || $decoded['error'] === '') {
            return $e->getMessage();
        }

        return $decoded['error'];
    }
}
