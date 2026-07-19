<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\Setting\SettingRepository as sR;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;

/**
 * Receives Adyen's HMAC-signed AUTHORISATION webhook notifications. This is
 * the sole writer for Adyen payment status — verifies the signature, then
 * marks the invoice paid/failed. Mirrors StripeWebhookHandler's shape
 * exactly (guard-clause chain kept under SonarQube's per-method return
 * limit, php:S1142) and lives as its own class so it never counts toward
 * PaymentInformationController's method total (php:S1448).
 *
 * The webhook route itself is CSRF-exempt and unauthenticated by design;
 * see routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 *
 * Uses the raw PSR-17 ResponseFactoryInterface, not Yii3's
 * DataResponseFactoryInterface — Adyen's classic webhook acknowledgment
 * requires the literal plaintext body "[accepted]" (still relied on by
 * webhook endpoints configured under the legacy model), and the
 * DataResponse factory always JSON-encodes its content, which would send
 * "[accepted]" with surrounding quotes instead.
 */
final class AdyenWebhookHandler
{
    public function __construct(
        private readonly AdyenPaymentService $adyenPaymentService,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly OnlinePaymentRecorderService $recorder,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        $item = $this->adyenPaymentService->verifyWebhookNotification($request->getBody()->getContents());
        if (null === $item) {
            // 401, not 400 — matches Adyen's own official example
            // (adyen-examples/adyen-php-online-payments) for HMAC failures.
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write('invalid signature');
            return $response;
        }

        $context = $this->resolveContext($item);
        if (null === $context) {
            return $this->accepted();
        }

        $this->applyEvent($context);
        return $this->accepted();
    }

    private function accepted(): Response
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('[accepted]');
        return $response;
    }

    /**
     * Resolves a verified notification item down to "an invoice we should
     * actually act on", or null for every case where the webhook is
     * correctly a no-op: an event we don't handle, an invoice we can't
     * find, or one already processed (Adyen redelivers notifications).
     */
    private function resolveContext(array $item): ?AdyenWebhookContext
    {
        if (($item['eventCode'] ?? '') !== 'AUTHORISATION') {
            return null;
        }

        $invoiceUrlKey = (string) ($item['merchantReference'] ?? '');
        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Adyen webhook: invoice not found for merchantReference.', ['merchantReference' => $invoiceUrlKey]);
            return null;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        return 0.00 === $invoiceAmountRecord->getBalance()
            ? null
            : new AdyenWebhookContext(
                $invoice,
                $invoiceAmountRecord,
                $invoiceUrlKey,
                (string) ($item['pspReference'] ?? ''),
                ($item['success'] ?? '') === 'true',
            );
    }

    private function applyEvent(AdyenWebhookContext $context): void
    {
        $invoice             = $context->invoice;
        $invoiceAmountRecord = $context->invoiceAmountRecord;
        $invoiceNumber       = $invoice->getNumber() ?? 'unknown';
        $balance             = $invoiceAmountRecord->getBalance() ?? 0.00;

        // Write the payment/merchant audit record BEFORE finalising the
        // invoice's own status/balance — same ordering as StripeWebhookHandler,
        // for the same reason: a failure here must leave the invoice
        // unpaid/retryable rather than "paid" with no audit trail.
        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-' . $context->pspReference,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $balance,
                invoice_payment_method: $context->succeeded ? 4 : 5,
                invoice_number: $invoiceNumber,
                driver: 'Adyen',
                d: 'adyen',
                invoice_url_key: $context->invoiceUrlKey,
                response: $context->succeeded,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $context->pspReference,
            ),
        );

        if ($context->succeeded) {
            $invoice->setStatusId(4);
            $invoice->setPaymentMethod(4);
            $this->iR->save($invoice);
            $invoiceAmountRecord->setBalance(0);
            $invoiceAmountRecord->setPaid($invoiceAmountRecord->getTotal() ?? 0.00);
            $this->iaR->save($invoiceAmountRecord);
        } else {
            $invoice->setStatusId(6);
            $this->iR->save($invoice);
        }
    }
}
