<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Payment\PaymentService;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\WorldpayMerchant\WorldpayMerchantRepository;
use App\Invoice\WorldpayMerchant\WorldpayMerchantService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;

/**
 * Receives Worldpay's HMAC-signed Events webhook. Unlike every other
 * gateway's webhook handler in this app, Worldpay's HATEOAS design
 * means the WorldpayMerchant audit row already exists by the time this
 * fires — WorldpayPaymentController persists a provisional row
 * (successful: false) immediately after a successful
 * POST /api/payments, since the `self_href` it needs for later
 * verifyPayment()/refund() calls can't be reconstructed from anything
 * a webhook payload carries. This handler's job is to look that row up
 * via `eventDetails.transactionReference` and flip it to confirmed —
 * not to create a fresh row the way AdyenWebhookHandler/every other
 * handler here does. See WorldpayMerchant's own docblock.
 *
 * `settled` is the event this app treats as "actually paid" — matches
 * this app's existing paid-status semantics and the confirmed finding
 * (throughout research) that a refund action only becomes available
 * once a payment has settled, not merely authorized.
 *
 * Defensive re-confirm on top of the HMAC signature (matches
 * Paystack/Razorpay/Mercado Pago's belt-and-braces pattern even though
 * those also have signatures): before settling, this handler calls
 * WorldpayPaymentService::verifyPayment() against the stored
 * `self_href` rather than trusting the webhook body's own outcome
 * field alone.
 *
 * The webhook route itself is CSRF-exempt and unauthenticated by
 * design; see routes-payment-information.php and
 * App\Middleware\CsrfExemptMiddleware.
 */
final class WorldpayWebhookHandler
{
    public function __construct(
        private readonly WorldpayPaymentService $worldpayPaymentService,
        private readonly WorldpaySignatureService $signatureService,
        private readonly WorldpayMerchantRepository $worldpayMerchantRepository,
        private readonly WorldpayMerchantService $worldpayMerchantService,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly PaymentService $paymentService,
        private readonly InvPaymentSettlementService $invPaymentSettlementService,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        $rawBody = $request->getBody()->getContents();
        $signatureHeader = $request->getHeaderLine('Event-Signature');
        $secret = (string) $this->sR->decode($this->sR->getSetting('gateway_worldpay_webhookSecret') ?: '');

        if (!$this->signatureService->verifyWebhookSignature($rawBody, $signatureHeader, $secret)) {
            $this->logger->warning('Worldpay webhook: invalid or missing signature.');
            return $this->responseFactory->createResponse(401);
        }

        $decoded = $this->decode($rawBody);
        if (null === $decoded) {
            return $this->responseFactory->createResponse(200);
        }

        $context = $this->resolveContext($decoded);
        if (null !== $context) {
            $this->applyEvent($context);
        }

        return $this->responseFactory->createResponse(200);
    }

    /**
     * Returns null on malformed JSON rather than throwing — the webhook
     * still gets a 200 (nothing left to retry), just logged.
     *
     * @return array{eventDetails?: array{type?: string, transactionReference?: string}}|null
     */
    private function decode(string $rawBody): ?array
    {
        try {
            /** @var array{eventDetails?: array{type?: string, transactionReference?: string}} $decoded */
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            return $decoded; // NOSONAR php:S1488 — the @var annotation above needs the assignment
        } catch (\JsonException $e) {
            $this->logger->warning('Worldpay webhook: malformed JSON body.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * First half of resolveContext()'s guard-clause chain — split out
     * purely to keep both this method and resolveContext() itself under
     * SonarQube's php:S1142 return-count cap (3), not for reuse
     * elsewhere.
     *
     * @param array{eventDetails?: array{type?: string, transactionReference?: string}} $decoded
     * @return array{eventType: string, worldpayMerchant: \App\Infrastructure\Persistence\WorldpayMerchant\WorldpayMerchant}|null
     */
    private function resolveEventAndMerchant(array $decoded): ?array
    {
        $eventType = $decoded['eventDetails']['type'] ?? '';
        $transactionReference = $decoded['eventDetails']['transactionReference'] ?? '';
        if ($eventType === '' || $transactionReference === '') {
            return null;
        }

        $worldpayMerchant = $this->worldpayMerchantRepository
            ->repoWorldpayMerchantByTransactionReference($transactionReference);
        if (null === $worldpayMerchant) {
            $this->logger->warning('Worldpay webhook: no matching WorldpayMerchant for transactionReference.', [
                'transactionReference' => $transactionReference,
            ]);
            return null;
        }

        return ['eventType' => $eventType, 'worldpayMerchant' => $worldpayMerchant];
    }

    /**
     * Second half of resolveContext()'s guard-clause chain — see
     * resolveEventAndMerchant() docblock for why this is split out.
     *
     * @param array{eventDetails?: array{type?: string, transactionReference?: string}} $decoded
     */
    private function resolveContext(array $decoded): ?WorldpayWebhookContext
    {
        $found = $this->resolveEventAndMerchant($decoded);
        if (null === $found) {
            return null;
        }

        $invoice = $this->iR->repoInvUnLoadedquery($found['worldpayMerchant']->reqInvId());
        if (null === $invoice) {
            return null;
        }

        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === $invoiceAmountRecord->getBalance()) {
            return null;
        }

        return new WorldpayWebhookContext($invoice, $invoiceAmountRecord, $found['worldpayMerchant'], $found['eventType']);
    }

    private function applyEvent(WorldpayWebhookContext $context): void
    {
        if ($context->eventType !== 'settled') {
            if (in_array($context->eventType, ['refused', 'error', 'expired'], true)) {
                $this->markFailed($context);
            }
            return;
        }

        // Belt-and-braces: re-confirm against Worldpay directly rather than
        // trusting the webhook body's own outcome field alone.
        $verification = $this->worldpayPaymentService->verifyPayment(
            $context->worldpayMerchant->getSelfHref() ?? '',
        );
        if (!$verification->paid) {
            $this->logger->warning('Worldpay webhook: settled event received but re-confirmation disagreed.', [
                'transactionReference' => $context->worldpayMerchant->getTransactionReference(),
                'message' => $verification->message,
            ]);
            return;
        }

        $this->markSettled($context);
    }

    private function markSettled(WorldpayWebhookContext $context): void
    {
        $invoice = $context->invoice;
        $invoiceAmountRecord = $context->invoiceAmountRecord;
        $balance = $invoiceAmountRecord->getBalance() ?? 0.00;
        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        // Write the payment ledger row + flip the audit record BEFORE
        // finalising the invoice's own status/balance — same ordering as
        // every other webhook handler here, for the same reason: a
        // failure here must leave the invoice unpaid/retryable rather
        // than "paid" with no audit trail.
        $this->paymentService->addPaymentViaPaymentHandler(
            new Payment(),
            [
                'inv_id' => $invoice->reqId(),
                'payment_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'amount' => $balance,
                'payment_method_id' => 4,
                'note' => "\u{26A1} Transaction ref: " . ($context->worldpayMerchant->getTransactionReference() ?? '')
                    . "\nProvider: Worldpay",
            ],
        );

        $this->worldpayMerchantService->saveWorldpayMerchantViaPaymentHandler(
            $context->worldpayMerchant,
            [
                'inv_id' => $invoice->reqId(),
                'merchant_response_successful' => true,
                'merchant_response_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'merchant_response' => sprintf('Worldpay payment settled for invoice %s.', $invoiceNumber),
                'merchant_response_reference' => $context->worldpayMerchant->getReference(),
                'merchant_response_transaction_reference' => $context->worldpayMerchant->getTransactionReference(),
                'merchant_response_payment_id' => $context->worldpayMerchant->getPaymentId(),
                'merchant_response_self_href' => $context->worldpayMerchant->getSelfHref(),
            ],
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }

    private function markFailed(WorldpayWebhookContext $context): void
    {
        $this->worldpayMerchantService->saveWorldpayMerchantViaPaymentHandler(
            $context->worldpayMerchant,
            [
                'inv_id' => $context->invoice->reqId(),
                'merchant_response_successful' => false,
                'merchant_response_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'merchant_response' => 'Worldpay payment ' . $context->eventType . '.',
                'merchant_response_reference' => $context->worldpayMerchant->getReference(),
                'merchant_response_transaction_reference' => $context->worldpayMerchant->getTransactionReference(),
                'merchant_response_payment_id' => $context->worldpayMerchant->getPaymentId(),
                'merchant_response_self_href' => $context->worldpayMerchant->getSelfHref(),
            ],
        );

        $context->invoice->setStatusId(6);
        $this->iR->save($context->invoice);
    }
}
