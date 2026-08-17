<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\Setting\SettingRepository as sR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use TrueLayer\Exceptions\Exception as TrueLayerException;
use TrueLayer\Interfaces\Webhook\PaymentExecutedEventInterface;
use TrueLayer\Webhook as TrueLayerWebhook;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Receives TrueLayer's `payment_executed` webhook notification, verified
 * via TrueLayer's own official `truelayer/client` SDK
 * (`TrueLayer\Webhook::configure()->handler(...)->execute()`) — signature
 * checking, JWKS fetching, and event-type dispatch are all handled by the
 * SDK itself rather than hand-rolled, matching
 * `TrueLayerPaymentService`'s own reasoning for using the official client.
 * Only `useProduction()` is needed to configure webhook verification (not
 * this app's own client credentials), since it checks the incoming
 * signature against TrueLayer's own published JWKS, not a shared secret.
 *
 * `metadata.invoice_url_key` is this app's own invoice url_key, set at
 * payment-creation time (`TrueLayerPaymentService::createPayment()`) and
 * echoed back unchanged — `payment_executed` webhooks carry no top-level
 * reference field at all (confirmed directly against TrueLayer's webhook
 * payload spec), unlike Checkout.com's `data.reference` or Adyen's
 * `merchantReference`, so metadata is the only correlation mechanism here.
 *
 * `executed` is the terminal success status for a payment into an
 * `external_account` beneficiary (this app never uses TrueLayer's
 * `merchant_account` concept) — see TrueLayerPaymentService's own docblock.
 *
 * Like every other signed-webhook gateway in this app, the signature is
 * only the first gate — this handler never trusts the notification body's
 * own fields to decide whether to mark an invoice paid; it always
 * re-confirms via an authenticated `GET /v3/payments/{id}`
 * (`TrueLayerPaymentService::verifyPayment()`) first, the same
 * belt-and-braces pattern used for every recording step in this app.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 *
 * `markInvoicePaidIfVerified()` is public — see its own docblock —
 * because `TrueLayerPaymentController::trueLayerComplete()` also calls it
 * directly as a synchronous fallback, not just this class's own `handle()`.
 */
final class TrueLayerWebhookHandler
{
    public function __construct(
        private readonly TrueLayerPaymentService $trueLayerPaymentService,
        private readonly sR $sR,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly OnlinePaymentRecorderService $recorder,
        private readonly DataResponseFactoryInterface $factory,
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        $rawBody = (string) $request->getBody();
        $paymentId = null;
        $invoiceUrlKey = null;

        try {
            TrueLayerWebhook::configure()
                ->httpClient($this->trueLayerPaymentService->httpClientForWebhook())
                ->useProduction(!$this->trueLayerPaymentService->isSandboxForWebhook())
                ->create()
                ->handler(function (PaymentExecutedEventInterface $event) use (&$paymentId, &$invoiceUrlKey): void {
                    $paymentId = $event->getPaymentId();
                    /** @var array{invoice_url_key?: string} $metadata */
                    $metadata = $event->getMetadata();
                    $invoiceUrlKey = $metadata['invoice_url_key'] ?? null;
                })
                ->path($request->getUri()->getPath())
                ->headers($this->flattenedHeaders($request))
                ->body($rawBody)
                ->execute();
        } catch (TrueLayerException $e) {
            $this->logger->warning('TrueLayer webhook: verification or handling failed.', [
                'error' => $e->getMessage(),
                'previous' => $e->getPrevious()?->getMessage(),
            ]);
            return $this->factory->createResponse('invalid signature')->withStatus(400);
        }

        if (is_string($paymentId) && $paymentId !== '') {
            $this->markInvoicePaidIfVerified($paymentId, $invoiceUrlKey ?? '');
        }

        return $this->factory->createResponse('');
    }

    /**
     * `ServerRequestInterface::getHeaders()` returns `array<string, string[]>`
     * — each header name maps to an array of values, to support genuinely
     * multi-value headers. The SDK's own `WebhookInterface::headers()`
     * declares `array<string, string>` instead — a flat map of single
     * string values — confirmed directly against
     * `vendor/truelayer/client/src/Services/Webhooks/Webhook.php`'s own
     * docblock. Passing the raw PSR-7 array straight through crashed deep
     * inside the SDK's own JWS signer
     * (`AbstractJws::header(): Argument #2 ($value) must be of type
     * string, array given`), confirmed live 2026-08-16 — every webhook
     * delivery attempt failed with a 400 as a result, so no TrueLayer
     * payment could ever actually get marked paid. `getHeaderLine()` is
     * PSR-7's own built-in flattening (comma-joins multiple values),
     * exactly what's needed here.
     *
     * @return array<string, string>
     */
    private function flattenedHeaders(Request $request): array
    {
        $headers = [];
        foreach (array_keys($request->getHeaders()) as $name) {
            $name = (string) $name;
            $headers[$name] = $request->getHeaderLine($name);
        }
        return $headers;
    }

    /**
     * Public — also called directly by `TrueLayerPaymentController::
     * trueLayerComplete()` as a synchronous fallback for when TrueLayer's
     * own webhook delivery is delayed, or — confirmed live 2026-08-17 —
     * paused entirely by TrueLayer itself after an earlier run of
     * delivery failures, with no way for this app to force a resend.
     * Calling this from two places is safe without extra guarding: the
     * `0.00 === balance` check a few lines down is already the same
     * idempotency guard that protects against TrueLayer's own webhook
     * redelivery, so a webhook arriving either before or after the
     * redirect-triggered call is a no-op the second time either way.
     */
    public function markInvoicePaidIfVerified(string $paymentId, string $invoiceUrlKey): void
    {
        $verification = $this->trueLayerPaymentService->verifyPayment($paymentId);
        if (!$verification->paid) {
            $this->logger->warning('TrueLayer webhook: payment not confirmed executed on re-check.', [
                'payment_id' => $paymentId,
                'status' => $verification->message,
            ]);
            return;
        }

        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('TrueLayer webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — TrueLayer may redeliver the same notification
            // (documented jittered-exponential-backoff retry for up to 72
            // hours on any non-2XX response).
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-truelayer-' . $paymentId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'TrueLayer',
                d: 'truelayer',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $paymentId,
            ),
        );

        $invoice->setStatusId(4);
        $invoice->setPaymentMethod(4);
        $this->iR->save($invoice);
        $invoiceAmountRecord->setBalance(0);
        $invoiceAmountRecord->setPaid($invoiceAmountRecord->getTotal() ?? 0.00);
        $this->iaR->save($invoiceAmountRecord);
    }
}
