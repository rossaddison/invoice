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
 * Receives Checkout.com's webhook notification — JSON POST body
 * `{id, type, data: {id, reference, amount, currency, ...}}`, signed via
 * the `Cko-Signature` header. See CheckoutComSignatureService's docblock
 * for exactly how that signature is ground-truthed.
 *
 * `data.reference` is this app's own invoice `url_key` directly — set on
 * the Payment Link at creation time
 * (`CheckoutComPaymentService::createPaymentLink()`'s `reference` field)
 * and echoed back unchanged, the same role Adyen's `merchantReference`
 * plays; no separate metadata lookup needed, unlike Paystack.
 *
 * `payment_captured` is the definitive "money has moved" event —
 * Checkout.com fires `payment_approved` first (authorization only) and
 * `payment_captured` once the authorized amount is actually captured;
 * standard Payment Links auto-capture, so the two normally arrive
 * moments apart, but only the capture event is trusted here, matching
 * every other gateway's "captured/settled", not "authorized", threshold
 * (PayPal's PAYMENT.CAPTURE.COMPLETED, Adyen's AUTHORISATION with
 * success=true after its own auto-capture, Square's payment.updated
 * with status COMPLETED).
 *
 * Like every other HMAC-signed gateway in this app, the signature is
 * only the first gate — this handler never trusts the notification
 * body's own fields to decide whether to mark an invoice paid; it always
 * re-confirms via an authenticated `GET /payments/{id}`
 * (`CheckoutComPaymentService::verifyPayment()`) first, the same
 * belt-and-braces pattern used for every recording step in this app.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class CheckoutComWebhookHandler
{
    public function __construct(
        private readonly CheckoutComPaymentService $checkoutComPaymentService,
        private readonly CheckoutComSignatureService $signatureService,
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
        $signature = $request->getHeaderLine('Cko-Signature');

        if (!$this->signatureService->verifyWebhookSignature(
            $rawBody,
            $signature,
            $this->checkoutComPaymentService->webhookSigningKey(),
        )) {
            $this->logger->warning('Checkout.com webhook: invalid signature.');
            return $this->factory->createResponse('invalid signature')->withStatus(400);
        }

        try {
            /** @var array{type?: string, data?: array{id?: string, reference?: string}} $payload */
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('Checkout.com webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return $this->factory->createResponse('bad request')->withStatus(400);
        }

        $type = $payload['type'] ?? '';
        $paymentId = $payload['data']['id'] ?? '';
        $invoiceUrlKey = $payload['data']['reference'] ?? '';

        if ($type === 'payment_captured' && $paymentId !== '') {
            $this->markInvoicePaidIfVerified($paymentId, $invoiceUrlKey);
        }

        return $this->factory->createResponse('');
    }

    private function markInvoicePaidIfVerified(string $paymentId, string $invoiceUrlKey): void
    {
        $verification = $this->checkoutComPaymentService->verifyPayment($paymentId);
        if (!$verification->paid) {
            $this->logger->warning('Checkout.com webhook: payment not confirmed captured on re-check.', [
                'payment_id' => $paymentId,
                'status' => $verification->message,
            ]);
            return;
        }

        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Checkout.com webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — Checkout.com may redeliver the same notification.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-checkout_com-' . $paymentId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Checkout_Com',
                d: 'checkout_com',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $paymentId,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
