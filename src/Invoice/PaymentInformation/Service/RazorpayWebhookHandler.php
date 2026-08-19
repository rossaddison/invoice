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
 * Receives Razorpay's `payment_link.paid` webhook notification, signed via
 * the `X-Razorpay-Signature` header (see RazorpaySignatureService's
 * docblock for exactly how that header name and formula are ground-truthed).
 *
 * Confirmed directly from Razorpay's own current API docs
 * (`razorpay.com/docs/webhooks/payment-links/`, fetched this session): the
 * event name `payment_link.paid` and the payload's nesting —
 * `payload.payment_link.entity.{id, reference_id, status}` and
 * `payload.payment.entity.{id, status}`.
 *
 * Like every other HMAC-signed gateway in this app, the signature is only
 * the first gate — this handler never trusts the notification body's own
 * `payment_link.entity.status` to decide whether to mark an invoice paid; it
 * always re-confirms via an authenticated `GET /v1/payment_links/{id}`
 * (`RazorpayPaymentService::verifyPayment()`) first, the same
 * belt-and-braces pattern already used for Robokassa/YooKassa/Paystack.
 *
 * The underlying `payment.entity.id` (not the payment_link id) is what gets
 * stored as the Merchant audit record's provider_reference — Razorpay
 * refunds are per-payment, not per-payment-link, so this is the value
 * `RazorpayPaymentService::refund()` actually needs later.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class RazorpayWebhookHandler
{
    public function __construct(
        private readonly RazorpayPaymentService $razorpayPaymentService,
        private readonly RazorpaySignatureService $signatureService,
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
        $signature = $request->getHeaderLine('X-Razorpay-Signature');
        $webhookSecret = (string) $this->sR->decode($this->sR->getSetting('gateway_razorpay_webhookSecret') ?: '');

        if (!$this->signatureService->verifyWebhookSignature($rawBody, $signature, $webhookSecret)) {
            $this->logger->warning('Razorpay webhook: invalid signature.');
            return $this->factory->createResponse('invalid signature')->withStatus(400);
        }

        try {
            /** @var array{event?: string, payload?: array{payment_link?: array{entity?: array{id?: string, reference_id?: string}}, payment?: array{entity?: array{id?: string}}}} $payload */
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('Razorpay webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return $this->factory->createResponse('bad request')->withStatus(400);
        }

        $event = $payload['event'] ?? '';
        $paymentLinkId = $payload['payload']['payment_link']['entity']['id'] ?? '';
        $invoiceUrlKey = $payload['payload']['payment_link']['entity']['reference_id'] ?? '';
        $paymentId = $payload['payload']['payment']['entity']['id'] ?? '';

        if ($event === 'payment_link.paid' && $paymentLinkId !== '' && $paymentId !== '') {
            $this->markInvoicePaidIfVerified($paymentLinkId, $paymentId, $invoiceUrlKey);
        }

        return $this->factory->createResponse('');
    }

    private function markInvoicePaidIfVerified(string $paymentLinkId, string $paymentId, string $invoiceUrlKey): void
    {
        $verification = $this->razorpayPaymentService->verifyPayment($paymentLinkId);
        if (!$verification->paid) {
            $this->logger->warning('Razorpay webhook: payment link not confirmed paid on re-check.', [
                'payment_link_id' => $paymentLinkId,
                'status' => $verification->message,
            ]);
            return;
        }

        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Razorpay webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — Razorpay may redeliver the same notification.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-razorpay-' . $paymentId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Razorpay',
                d: 'razorpay',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $paymentId,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
