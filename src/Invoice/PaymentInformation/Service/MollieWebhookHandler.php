<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\PaymentInformationQueryHelper;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\Setting\SettingRepository as sR;
use Mollie\Api\Exceptions\ApiException as MollieException;
use Mollie\Api\MollieApiClient as MollieClient;
use Mollie\Api\Resources\Payment as MolliePayment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Receives Mollie's classic per-payment webhook — a plain form POST
 * carrying only `id` (ground-truthed against the official
 * `mollie/mollie-api-php` SDK's own
 * `docs/recipes/payments/handle-webhook.md`, matching the API version
 * already pinned in this project, v3.13.1). This is distinct from Mollie's
 * newer, HMAC-signed "next-gen webhooks" system
 * (`Mollie\Api\Webhooks\SignatureValidator`, `docs/webhooks.md`) — that
 * mechanism covers account-wide subscriptions to newer resource types
 * (Payment Links, Payouts, Disputes, Balance events, …), not one-off
 * Payment resources created directly via `payments->create()`, which is
 * what this app does. The classic webhook carries no signature at all;
 * authenticity comes from calling back `GET /payments/{id}` with this
 * app's own API key and trusting only that response, never the POST body
 * itself — the same "re-confirm via an authenticated GET" shape already
 * used for Robokassa (OpStateExt) and YooKassa (`GET /payments/{id}`).
 *
 * Before this handler existed, this app had no webhook for Mollie at
 * all — payment confirmation depended entirely on the customer's browser
 * completing the redirect back to `PaymentInformationController::
 * mollieComplete()`, which itself did a fragile reverse lookup
 * (`$mollie->payments->page()`, no filter — Mollie's API defaults to the
 * 50 most recent payments store-wide) to find the right payment by
 * metadata. If the customer never returned, or 50+ other payments happened
 * meanwhile, the invoice was silently never marked paid. This handler
 * closes that gap, matching every other gateway in this app
 * (Stripe/Adyen/GoCardless/Robokassa/YooKassa all have a webhook as the
 * authoritative source). `mollieComplete()`'s own paid-handling now guards
 * on the balance already being 0 before re-recording, since this webhook
 * may now fire before or after that redirect completes (Mollie's own docs:
 * "the webhook may be called after the customer has already been
 * redirected").
 *
 * Per Mollie's own documented rules: always answer 200 (even when the
 * payment isn't paid, or the lookup fails — Mollie retries otherwise, and
 * a bad payment id won't be fixed by a retry); process within ~2 seconds;
 * the same webhook may be redelivered for the same payment.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class MollieWebhookHandler
{
    public function __construct(
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly InvPaymentSettlementService $invPaymentSettlementService,
        private readonly OnlinePaymentRecorderService $recorder,
        private readonly DataResponseFactoryInterface $factory,
        private readonly Logger $logger,
        private MollieClient $mollieClient = new MollieClient(),
    ) {
    }

    public function handle(Request $request): Response
    {
        $body = (array) $request->getParsedBody();
        $paymentId = (string) ($body['id'] ?? '');
        if ($paymentId === '') {
            $this->logger->warning('Mollie webhook: missing id.');
            return $this->factory->createResponse('missing id')->withStatus(400);
        }

        if (!PaymentInformationQueryHelper::mollieSetTestOrLiveApiKey($this->sR, $this->mollieClient)) {
            $this->logger->error('Mollie webhook: gateway not configured.');
            // Still 200 — Mollie's own rule is to always acknowledge;
            // retrying won't fix a missing API key.
            return $this->factory->createResponse('OK');
        }

        return $this->fetchAndProcessPayment($paymentId);
    }

    private function fetchAndProcessPayment(string $paymentId): Response
    {
        try {
            $payment = $this->mollieClient->payments->get($paymentId);
        } catch (MollieException $e) {
            $this->logger->warning('Mollie webhook: unable to fetch payment.', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return $this->factory->createResponse('OK');
        }

        if ($payment->isPaid() && !$payment->hasRefunds() && !$payment->hasChargebacks()) {
            $this->markInvoicePaidIfFound($payment);
        }

        return $this->factory->createResponse('OK');
    }

    private function markInvoicePaidIfFound(MolliePayment $payment): void
    {
        /** @var \stdClass $payment->metadata */
        $metadata = $payment->metadata;
        $invoiceUrlKey = isset($metadata->invoice_url_key) ? (string) $metadata->invoice_url_key : '';
        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Mollie webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — Mollie may redeliver the same webhook, or
            // mollieComplete()'s own redirect-time check may have already
            // marked this paid.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-' . $payment->status,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Mollie',
                d: 'mollie',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $payment->id,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
