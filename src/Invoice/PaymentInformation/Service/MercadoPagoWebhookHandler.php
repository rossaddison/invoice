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
 * Receives Mercado Pago's Checkout Pro payment webhook, signed via the
 * `x-signature`/`x-request-id` headers (see MercadoPagoSignatureService's
 * docblock for exactly how that formula is ground-truthed).
 *
 * Confirmed directly from Mercado Pago's own current docs (fetched this
 * session): the POST body shape is
 * `{id, live_mode, type, date_created, user_id, api_version, action, data: {id}}`,
 * with `data.id` (the **payment** id, not this app's own webhook payload
 * id) also echoed as a `data.id` query-string parameter — used in the
 * signature manifest, per MercadoPagoSignatureService. Unlike
 * Razorpay/Stripe, the notification body carries no invoice reference of
 * its own; `type: "payment"` and `data.id` are all it gives, so this
 * handler always looks up the full payment via
 * `MercadoPagoPaymentService::fetchPaymentDetails()` for both the status
 * and the `external_reference` this app set at checkout-creation time.
 *
 * Like every other HMAC-signed gateway in this app, the signature is only
 * the first gate — this handler never trusts the notification's own `type`/
 * `action` to decide whether to mark an invoice paid; it always re-confirms
 * via that same authenticated `GET /v1/payments/{id}` call, the same
 * belt-and-braces pattern already used for Robokassa/YooKassa/Paystack/
 * Razorpay. Reacts to `type === 'payment'` regardless of the specific
 * `action` string (`payment.created`/`payment.updated`/any future variant)
 * — the re-confirmation step is what actually gates whether an invoice gets
 * marked paid, not the action name.
 *
 * The payment id (not the preference id) is what gets stored as the
 * Merchant audit record's provider_reference — Mercado Pago refunds are
 * per-payment, so this is the value `MercadoPagoPaymentService::refund()`
 * actually needs later.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 * Mercado Pago expects an HTTP 200/201 within 22 seconds or it retries
 * (every 15 minutes for the first 3 attempts, then longer intervals).
 */
final class MercadoPagoWebhookHandler
{
    public function __construct(
        private readonly MercadoPagoPaymentService $mercadoPagoPaymentService,
        private readonly MercadoPagoSignatureService $signatureService,
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
        $xSignature = $request->getHeaderLine('x-signature');
        $xRequestId = $request->getHeaderLine('x-request-id');
        /** @var array<string, string> $queryParams */
        $queryParams = $request->getQueryParams();
        $dataIdFromQuery = $queryParams['data.id'] ?? null;
        $webhookSecret = (string) $this->sR->decode($this->sR->getSetting('gateway_mercado_pago_webhookSecret') ?: '');

        if (!$this->signatureService->verifyWebhookSignature($xSignature, $xRequestId !== '' ? $xRequestId : null, $dataIdFromQuery, $webhookSecret)) {
            $this->logger->warning('Mercado Pago webhook: invalid signature.');
            return $this->factory->createResponse('invalid signature')->withStatus(400);
        }

        try {
            /** @var array{type?: string, data?: array{id?: string}} $payload */
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('Mercado Pago webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return $this->factory->createResponse('bad request')->withStatus(400);
        }

        $type = $payload['type'] ?? '';
        $paymentId = $payload['data']['id'] ?? '';

        if ($type === 'payment' && $paymentId !== '') {
            $this->markInvoicePaidIfVerified($paymentId);
        }

        return $this->factory->createResponse('');
    }

    private function markInvoicePaidIfVerified(string $paymentId): void
    {
        $details = $this->mercadoPagoPaymentService->fetchPaymentDetails($paymentId);
        if (null === $details || $details['status'] !== 'approved') {
            $this->logger->warning('Mercado Pago webhook: payment not confirmed approved on re-check.', [
                'payment_id' => $paymentId,
                'status' => $details['status'] ?? 'lookup failed',
            ]);
            return;
        }

        $invoiceUrlKey = $details['externalReference'];
        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Mercado Pago webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — Mercado Pago may redeliver the same notification.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        // driver must be exactly 'Mercado_Pago' — the literal
        // SettingPaymentTrait::activePaymentGateways() array key, not a
        // human-readable display string. PaymentRefundController's refund
        // dropdown (resources/views/invoice/payment/index.php) passes that
        // same key verbatim as the route's {gateway} argument, and
        // MerchantRepository::repoMerchantLatestSuccessfulByInvIdAndDriver()
        // does an exact-string WHERE match against whatever gets stored
        // here — same convention every other gateway's own recorded
        // 'gateway' value already follows.
        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-mercado_pago-' . $paymentId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Mercado_Pago',
                d: 'mercado_pago',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $paymentId,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
