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
 * Receives Square's `payment.updated` (and `payment.created`) webhook
 * notification, signed via the `x-square-hmacsha256-signature` header —
 * see SquareSignatureService's docblock for exactly how that formula is
 * ground-truthed. Unlike most of this app's HMAC-signed webhooks, Square's
 * formula hashes the notification URL concatenated with the raw body, not
 * the raw body alone — this handler reconstructs that URL from the
 * inbound request's own URI (`$request->getUri()`), which must match
 * exactly what's configured for this webhook subscription in the Square
 * Developer Dashboard. If this app is ever deployed behind a reverse proxy
 * that changes the scheme/host presented to PHP, this reconstruction (and
 * therefore the signature check) would need revisiting — a degradation
 * that fails closed (rejecting a genuine notification), not open.
 *
 * Confirmed directly against Square's own current API docs: the event name
 * (`payment.updated`), and the payload's `data.object.payment.{id, status,
 * order_id}` shape, with `status: "COMPLETED"` on success.
 *
 * The Payment object itself carries no reference back to this app's own
 * invoice url_key — only `order_id` — so this handler makes a second call,
 * `SquarePaymentService::getOrderReferenceId()`, to resolve the Order's own
 * `reference_id` (set at Payment Link creation time). Even after the
 * signature check, this handler still always re-confirms via an
 * authenticated `GET /v2/payments/{id}` (`verifyPayment()`) before marking
 * an invoice paid — the same belt-and-braces pattern used for every other
 * gateway in this app.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class SquareWebhookHandler
{
    public function __construct(
        private readonly SquarePaymentService $squarePaymentService,
        private readonly SquareSignatureService $signatureService,
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
        $signature = $request->getHeaderLine('x-square-hmacsha256-signature');
        $signatureKey = (string) $this->sR->decode($this->sR->getSetting('gateway_square_webhookSecret') ?: '');
        $notificationUrl = (string) $request->getUri();

        if (!$this->signatureService->verifyWebhookSignature($notificationUrl, $rawBody, $signature, $signatureKey)) {
            $this->logger->warning('Square webhook: invalid signature.');
            return $this->factory->createResponse('invalid signature')->withStatus(400);
        }

        try {
            /** @var array{type?: string, data?: array{object?: array{payment?: array{id?: string, order_id?: string}}}} $payload */
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('Square webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return $this->factory->createResponse('bad request')->withStatus(400);
        }

        $eventType = $payload['type'] ?? '';
        $paymentId = $payload['data']['object']['payment']['id'] ?? '';
        $orderId = $payload['data']['object']['payment']['order_id'] ?? '';

        if (in_array($eventType, ['payment.updated', 'payment.created'], true) && $paymentId !== '' && $orderId !== '') {
            $this->markInvoicePaidIfVerified($paymentId, $orderId);
        }

        return $this->factory->createResponse('');
    }

    private function markInvoicePaidIfVerified(string $paymentId, string $orderId): void
    {
        $verification = $this->squarePaymentService->verifyPayment($paymentId);
        if (!$verification->paid) {
            $this->logger->warning('Square webhook: payment not confirmed completed on re-check.', [
                'payment_id' => $paymentId,
                'status' => $verification->message,
            ]);
            return;
        }

        $invoiceUrlKey = $this->squarePaymentService->getOrderReferenceId($orderId) ?? '';
        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Square webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey, 'order_id' => $orderId]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — Square may redeliver the same notification.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-square-' . $paymentId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Square',
                d: 'square',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $paymentId,
                secondary_provider_reference: $orderId,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
