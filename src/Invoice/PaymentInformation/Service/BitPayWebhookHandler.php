<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\Setting\SettingRepository as sR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Receives BitPay's IPN/webhook notification — a JSON POST body carrying
 * the invoice's own fields flat at the top level (`id`, `orderId`,
 * `status`, ...), no `event`/`data` envelope, confirmed directly against
 * BitPay's own IPN payload documentation. Signed via the `x-signature`
 * header — see `BitPayPaymentService::verifyWebhookSignature()` /
 * `RossAddison\BitPayClient\BitPayClient::verifyWebhookSignature()`'s own
 * docblock for exactly how that's ground-truthed (and its one
 * not-independently-confirmed caveat).
 *
 * Like every other signed-webhook gateway in this app, the signature is
 * only the first gate — this handler never trusts the notification body's
 * own `status` field to decide whether to mark an invoice paid; it always
 * re-confirms via an authenticated `GET /invoices/{id}`
 * (`BitPayPaymentService::verifyPayment()`) first, the same
 * belt-and-braces pattern already used for every recording step in this
 * app (Paystack's `GET /transaction/verify/{reference}` re-check,
 * TrueLayer's `GET /v3/payments/{id}` re-check). `verifyPayment()` only
 * reports paid once the invoice reaches BitPay's own `complete` status —
 * see `BitPayPaymentService`'s own docblock for why that, not `confirmed`,
 * is the correct settled bar.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class BitPayWebhookHandler
{
    public function __construct(
        private readonly BitPayPaymentService $bitPayPaymentService,
        private readonly sR $sR,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly InvPaymentSettlementService $invPaymentSettlementService,
        private readonly OnlinePaymentRecorderService $recorder,
        private readonly DataResponseFactoryInterface $factory,
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        $rawBody = $request->getBody()->getContents();
        $signature = $request->getHeaderLine('x-signature');

        if (!$this->bitPayPaymentService->verifyWebhookSignature($rawBody, $signature)) {
            $this->logger->warning('BitPay webhook: invalid signature.');
            return $this->factory->createResponse('invalid signature')->withStatus(400);
        }

        try {
            /** @var array{id?: string, orderId?: string} $payload */
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('BitPay webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return $this->factory->createResponse('bad request')->withStatus(400);
        }

        $invoiceId = $payload['id'] ?? '';
        $invoiceUrlKey = $payload['orderId'] ?? '';

        if ($invoiceId !== '') {
            $this->markInvoicePaidIfVerified($invoiceId, $invoiceUrlKey);
        }

        return $this->factory->createResponse('');
    }

    private function markInvoicePaidIfVerified(string $invoiceId, string $invoiceUrlKey): void
    {
        $verification = $this->bitPayPaymentService->verifyPayment($invoiceId);
        if (!$verification->paid) {
            $this->logger->warning('BitPay webhook: invoice not confirmed complete on re-check.', [
                'invoice_id' => $invoiceId,
                'status' => $verification->message,
            ]);
            return;
        }

        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('BitPay webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — BitPay may redeliver the same notification
            // on every status transition it retries.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-bitpay-' . $invoiceId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'BitPay',
                d: 'bitpay',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $invoiceId,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
