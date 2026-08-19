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
 * Receives YooKassa's webhook notification — JSON POST body
 * `{type, event, object}` (see YooKassaPaymentService's docblock for the
 * event-type constants ground-truthed against the official
 * `yoomoney/yookassa-sdk-php` SDK, cross-checked against both the stale
 * GitHub mirror and YooMoney's current actively-maintained source).
 *
 * Unlike every other gateway webhook in this app, YooKassa's notifications
 * carry no cryptographic signature — see YookassaWebhookIpVerifier's
 * docblock. Accordingly this handler treats the IP check as only a fast
 * pre-filter (reject obviously-wrong-origin requests cheaply) and NEVER
 * trusts the notification body's own `object.status` to decide whether to
 * mark an invoice paid — it always re-confirms via an authenticated
 * `GET /payments/{id}` (`YookassaPaymentService::verifyPayment()`) first,
 * the same server-to-server confirmation this app already applies for
 * Robokassa (OpStateExt) and every HMAC-signature-verified gateway's own
 * belt-and-braces recording step.
 *
 * `REMOTE_ADDR` caveat: if this app is deployed behind a reverse proxy that
 * does not forward the real client IP transparently, `REMOTE_ADDR` will be
 * the proxy's own address and the IP check will always fail closed (safe,
 * but noisy) — the GET-confirmation step is what actually protects invoice
 * state either way, so this is a defense-in-depth degradation, not a
 * security hole.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class YookassaWebhookHandler
{
    public function __construct(
        private readonly YookassaPaymentService $yookassaPaymentService,
        private readonly YookassaWebhookIpVerifier $ipVerifier,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly InvPaymentSettlementService $invPaymentSettlementService,
        private readonly OnlinePaymentRecorderService $recorder,
        private readonly DataResponseFactoryInterface $factory,
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        $remoteAddress = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? '');
        if (!$this->ipVerifier->isTrusted($remoteAddress)) {
            $this->logger->warning('YooKassa webhook: untrusted source IP.', ['ip' => $remoteAddress]);
            return $this->factory->createResponse('untrusted source')->withStatus(400);
        }

        try {
            /** @var array{event?: string, object?: array{id?: string, metadata?: array<string, string>}} $payload */
            $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('YooKassa webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return $this->factory->createResponse('bad request')->withStatus(400);
        }

        $event = $payload['event'] ?? '';
        $paymentId = $payload['object']['id'] ?? '';
        $invoiceUrlKey = $payload['object']['metadata']['invoiceUrlKey'] ?? '';

        if ($event === 'payment.succeeded' && $paymentId !== '') {
            $this->markInvoicePaidIfVerified($paymentId, $invoiceUrlKey);
        }

        return $this->factory->createResponse('');
    }

    private function markInvoicePaidIfVerified(string $paymentId, string $invoiceUrlKey): void
    {
        $verification = $this->yookassaPaymentService->verifyPayment($paymentId);
        if (!$verification->paid) {
            $this->logger->warning('YooKassa webhook: payment not confirmed paid on re-check.', [
                'payment_id' => $paymentId,
                'status' => $verification->message,
            ]);
            return;
        }

        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('YooKassa webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — YooKassa may redeliver the same notification.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-yookassa-' . $paymentId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Yookassa',
                d: 'yookassa',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $paymentId,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
