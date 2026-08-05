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
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Receives PayPal's `PAYMENT.CAPTURE.COMPLETED` webhook notification.
 * Unlike every other gateway's webhook in this app, PayPal's signature
 * verification is itself an API call
 * (`PaypalPaymentService::verifyWebhookSignature()`, `POST
 * /v1/notifications/verify-webhook-signature`) rather than a local HMAC
 * computation — PayPal validates its own per-message RSA signature against
 * a PayPal-hosted certificate server-side, so this app never has to
 * implement that cryptography itself. See PaypalPaymentService's own
 * docblock for exactly how the event name and payload shape are
 * ground-truthed.
 *
 * Even after that API-verified signature check, this handler still always
 * re-confirms via an authenticated `GET /v2/payments/captures/{id}`
 * (`PaypalPaymentService::verifyPayment()`) before marking an invoice
 * paid — the same belt-and-braces pattern used for every other gateway in
 * this app.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class PaypalWebhookHandler
{
    public function __construct(
        private readonly PaypalPaymentService $paypalPaymentService,
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
        $rawBody = $request->getBody()->getContents();
        $webhookId = (string) $this->sR->decode($this->sR->getSetting('gateway_paypal_webhookId') ?: '');
        $headers = [
            'transmissionId' => $request->getHeaderLine('PAYPAL-TRANSMISSION-ID'),
            'transmissionTime' => $request->getHeaderLine('PAYPAL-TRANSMISSION-TIME'),
            'certUrl' => $request->getHeaderLine('PAYPAL-CERT-URL'),
            'authAlgo' => $request->getHeaderLine('PAYPAL-AUTH-ALGO'),
            'transmissionSig' => $request->getHeaderLine('PAYPAL-TRANSMISSION-SIG'),
        ];

        if (!$this->paypalPaymentService->verifyWebhookSignature($rawBody, $headers, $webhookId)) {
            $this->logger->warning('PayPal webhook: signature verification failed.');
            return $this->factory->createResponse('invalid signature')->withStatus(400);
        }

        try {
            /** @var array{event_type?: string, resource?: array{id?: string, invoice_id?: string}} $payload */
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('PayPal webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return $this->factory->createResponse('bad request')->withStatus(400);
        }

        $eventType = $payload['event_type'] ?? '';
        $captureId = $payload['resource']['id'] ?? '';
        $invoiceUrlKey = $payload['resource']['invoice_id'] ?? '';

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED' && $captureId !== '') {
            $this->markInvoicePaidIfVerified($captureId, $invoiceUrlKey);
        }

        return $this->factory->createResponse('');
    }

    private function markInvoicePaidIfVerified(string $captureId, string $invoiceUrlKey): void
    {
        $verification = $this->paypalPaymentService->verifyPayment($captureId);
        if (!$verification->paid) {
            $this->logger->warning('PayPal webhook: capture not confirmed completed on re-check.', [
                'capture_id' => $captureId,
                'status' => $verification->message,
            ]);
            return;
        }

        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('PayPal webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — PayPal may redeliver the same notification.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-paypal-' . $captureId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Paypal',
                d: 'paypal',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $captureId,
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
