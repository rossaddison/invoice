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
 * Receives Paystack's webhook notification — JSON POST body
 * `{event, data: {reference, status, metadata}}`, signed via the
 * `X-Paystack-Signature` header. See PaystackSignatureService's docblock
 * for exactly how that signature is ground-truthed.
 *
 * Like every other HMAC-signed gateway in this app, the signature is only
 * the first gate — this handler never trusts the notification body's own
 * `data.status` to decide whether to mark an invoice paid; it always
 * re-confirms via an authenticated `GET /transaction/verify/{reference}`
 * (`PaystackPaymentService::verifyPayment()`) first, the same
 * belt-and-braces pattern already used for every recording step in this
 * app (Robokassa's OpStateExt-equivalent re-check, YooKassa's
 * `GET /payments/{id}`).
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class PaystackWebhookHandler
{
    public function __construct(
        private readonly PaystackPaymentService $paystackPaymentService,
        private readonly PaystackSignatureService $signatureService,
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
        $signature = $request->getHeaderLine('X-Paystack-Signature');
        $secretKey = (string) $this->sR->decode($this->sR->getSetting('gateway_paystack_secretKey') ?: '');

        if (!$this->signatureService->verifyWebhookSignature($rawBody, $signature, $secretKey)) {
            $this->logger->warning('Paystack webhook: invalid signature.');
            return $this->factory->createResponse('invalid signature')->withStatus(400);
        }

        try {
            /** @var array{event?: string, data?: array{reference?: string, metadata?: array<string, string>}} $payload */
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('Paystack webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return $this->factory->createResponse('bad request')->withStatus(400);
        }

        $event = $payload['event'] ?? '';
        $reference = $payload['data']['reference'] ?? '';
        $invoiceUrlKey = $payload['data']['metadata']['invoiceUrlKey'] ?? '';

        if ($event === 'charge.success' && $reference !== '') {
            $this->markInvoicePaidIfVerified($reference, $invoiceUrlKey);
        }

        return $this->factory->createResponse('');
    }

    private function markInvoicePaidIfVerified(string $reference, string $invoiceUrlKey): void
    {
        $verification = $this->paystackPaymentService->verifyPayment($reference);
        if (!$verification->paid) {
            $this->logger->warning('Paystack webhook: payment not confirmed successful on re-check.', [
                'reference' => $reference,
                'status' => $verification->message,
            ]);
            return;
        }

        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Paystack webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — Paystack may redeliver the same notification.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-paystack-' . $reference,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Paystack',
                d: 'paystack',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $reference,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
