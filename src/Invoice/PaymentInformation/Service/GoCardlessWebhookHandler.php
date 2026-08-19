<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\Setting\SettingRepository as sR;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Yiisoft\Json\Json;

/**
 * Receives GoCardless's HMAC-signed webhook notifications — the sole writer
 * for GoCardless payment status. Mirrors AdyenWebhookHandler's shape, with
 * one structural difference: GoCardless batches multiple events into a
 * single request body (`{"events": [...]}`), so handle() loops over all of
 * them rather than processing exactly one. Lives as its own class so it
 * never counts toward a controller's method total (php:S1448).
 *
 * The webhook route itself is CSRF-exempt and unauthenticated by design;
 * see routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 *
 * Unlike Adyen/Stripe, a GoCardless webhook event carries only
 * links.payment (the GoCardless payment id) — not our own invoice
 * reference — so resolveContext() makes an extra API call
 * (GoCardlessPaymentService::getPaymentMetadata()) to read back the
 * invoice_url_key set in scheduleDirectDebitPayment()'s metadata.
 */
final class GoCardlessWebhookHandler
{
    /** @var list<string> */
    private const array SUCCESS_ACTIONS = ['confirmed', 'paid_out'];

    /** @var list<string> */
    private const array FAILURE_ACTIONS = ['failed', 'cancelled', 'charged_back'];

    public function __construct(
        private readonly GoCardlessPaymentService $goCardlessPaymentService,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly InvPaymentSettlementService $invPaymentSettlementService,
        private readonly OnlinePaymentRecorderService $recorder,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        $rawBody = $request->getBody()->getContents();
        $signature = $request->getHeaderLine('Webhook-Signature');
        if (!$this->goCardlessPaymentService->isValidWebhookSignature($rawBody, $signature)) {
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write('invalid signature');
            return $response;
        }

        /** @var array{events?: list<array{resource_type?: string, action?: string, links?: array{payment?: string}}>} $payload */
        $payload = (array) Json::decode($rawBody);
        foreach ($payload['events'] ?? [] as $event) {
            $this->processEvent($event);
        }

        return $this->responseFactory->createResponse(200);
    }

    /**
     * @param array{resource_type?: string, action?: string, links?: array{payment?: string}} $event
     */
    private function processEvent(array $event): void
    {
        if (($event['resource_type'] ?? '') !== 'payments') {
            return;
        }
        $action = $event['action'] ?? '';
        $succeeded = in_array($action, self::SUCCESS_ACTIONS, true);
        $failed = in_array($action, self::FAILURE_ACTIONS, true);
        if (!$succeeded && !$failed) {
            // Not-yet-actionable transitional state (created, submitted, etc.)
            return;
        }

        $paymentId = $event['links']['payment'] ?? '';
        $context = $paymentId !== '' ? $this->resolveContext($paymentId, $succeeded) : null;
        if (null === $context) {
            return;
        }

        $this->applyEvent($context);
    }

    private function resolveContext(string $paymentId, bool $succeeded): ?GoCardlessWebhookContext
    {
        $metadata = $this->goCardlessPaymentService->getPaymentMetadata($paymentId);
        $invoiceUrlKey = $metadata['invoice_url_key'] ?? '';
        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning(
                'GoCardless webhook: invoice not found for payment metadata.',
                ['paymentId' => $paymentId],
            );
            return null;
        }

        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        return 0.00 === $invoiceAmountRecord->getBalance()
            ? null
            : new GoCardlessWebhookContext($invoice, $invoiceAmountRecord, $invoiceUrlKey, $paymentId, $succeeded);
    }

    private function applyEvent(GoCardlessWebhookContext $context): void
    {
        $invoice = $context->invoice;
        $invoiceAmountRecord = $context->invoiceAmountRecord;
        $invoiceNumber = $invoice->getNumber() ?? 'unknown';
        $balance = $invoiceAmountRecord->getBalance() ?? 0.00;

        // Write the payment/merchant audit record BEFORE finalising the
        // invoice's own status/balance — same ordering as
        // AdyenWebhookHandler/StripeWebhookHandler, for the same reason: a
        // failure here must leave the invoice unpaid/retryable rather than
        // "paid" with no audit trail.
        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-' . $context->paymentId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $balance,
                invoice_payment_method: $context->succeeded ? 4 : 5,
                invoice_number: $invoiceNumber,
                driver: 'GoCardless',
                d: 'gocardless',
                invoice_url_key: $context->invoiceUrlKey,
                response: $context->succeeded,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $context->paymentId,
            ),
        );

        if ($context->succeeded) {
            $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
        } else {
            $invoice->setStatusId(6);
            $this->iR->save($invoice);
        }
    }
}
