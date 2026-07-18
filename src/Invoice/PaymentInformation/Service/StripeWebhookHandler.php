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
use Stripe\Event;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Json\Json;

/**
 * Receives Stripe's signed payment_intent.* events. This is the sole writer
 * for Stripe payment status — verifies the signature, then marks the invoice
 * paid/failed. Extracted out of PaymentInformationController to keep it
 * under SonarQube's per-class method count (php:S1448) and to give the
 * guard-clause chain room to stay under the per-method return count
 * (php:S1142) without collapsing it into a single long method.
 *
 * The webhook route itself is CSRF-exempt and unauthenticated by design; see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 */
final class StripeWebhookHandler
{
    public function __construct(
        private readonly StripePaymentService $stripePaymentService,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly OnlinePaymentRecorderService $recorder,
        private readonly DataResponseFactoryInterface $factory,
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        $event = $this->stripePaymentService->verifyWebhookSignature(
            $request->getBody()->getContents(),
            $request->getHeaderLine('Stripe-Signature'),
        );
        if (null === $event) {
            return $this->factory->createResponse(Json::encode(['error' => 'invalid signature']))
                ->withStatus(400);
        }

        $context = $this->resolveContext($event);
        if (null === $context) {
            return $this->acknowledged();
        }

        $this->applyEvent($event, $context);
        return $this->acknowledged();
    }

    private function acknowledged(): Response
    {
        return $this->factory->createResponse(Json::encode(['received' => true]));
    }

    /**
     * Resolves the event down to "an invoice we should actually act on", or
     * null for every case where the webhook is correctly a no-op: an
     * irrelevant event type, an invoice we can't find, or one already
     * processed (Stripe may redeliver the same event).
     */
    private function resolveContext(Event $event): ?StripeWebhookInvoiceContext
    {
        if (!in_array($event->type, ['payment_intent.succeeded', 'payment_intent.payment_failed'], true)) {
            return null;
        }

        /** @var \Stripe\PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;
        $invoiceUrlKey = (string) ($paymentIntent->metadata['invoice_url_key'] ?? '');
        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Stripe webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return null;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        return 0.00 === $invoiceAmountRecord->getBalance()
            ? null
            : new StripeWebhookInvoiceContext($invoice, $invoiceAmountRecord, $invoiceUrlKey);
    }

    private function applyEvent(Event $event, StripeWebhookInvoiceContext $context): void
    {
        $invoice              = $context->invoice;
        $invoiceAmountRecord  = $context->invoiceAmountRecord;
        $invoiceNumber        = $invoice->getNumber() ?? 'unknown';
        $succeeded            = $event->type === 'payment_intent.succeeded';
        $balance              = $invoiceAmountRecord->getBalance() ?? 0.00;

        // Write the payment/merchant audit record BEFORE finalising the
        // invoice's own status/balance. If this throws, the invoice is left
        // exactly as it was (balance untouched) so the idempotency guard in
        // resolveContext() won't skip a future retry — the alternative order
        // risks an invoice showing "paid" with no payment record if this
        // insert fails after the invoice fields already committed.
        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-' . $event->type,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $balance,
                invoice_payment_method: $succeeded ? 4 : 5,
                invoice_number: $invoiceNumber,
                driver: 'Stripe',
                d: 'stripe',
                invoice_url_key: $context->invoiceUrlKey,
                response: $succeeded,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
            ),
        );

        if ($succeeded) {
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
