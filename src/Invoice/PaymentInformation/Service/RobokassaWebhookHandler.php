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
 * Receives Robokassa's "Result URL" payment-confirmation callback — a plain
 * form POST (OutSum, InvId, SignatureValue, and any Shp_ custom params this
 * app sent when the invoice was created), not a modern signed-JSON webhook
 * like Stripe/Adyen. Extracted out of a dedicated RobokassaPaymentController
 * following the same pattern as StripeWebhookHandler/GoCardlessWebhookHandler.
 *
 * The signature-verification formula this relies on (via
 * RobokassaPaymentService::verifyResultUrlCallback()) is ground-truthed
 * against Robokassa's own official OpenAPI specification
 * (https://docs.robokassa.ru/openapi/robokassa.yaml, `paymentResult`
 * webhook) — see RobokassaSignatureService's docblock.
 *
 * The route is CSRF-exempt and unauthenticated by design — see
 * routes-payment-information.php and App\Middleware\CsrfExemptMiddleware.
 * Acknowledges with Robokassa's documented `OK{InvId}` response format
 * (pattern `^OK\d+$` per the spec's ResultUrlAccepted response) regardless
 * of whether the invoice was found, matching Robokassa's own expectation
 * that a non-"OK..." response means "retry the callback later".
 */
final class RobokassaWebhookHandler
{
    public function __construct(
        private readonly RobokassaPaymentService $robokassaPaymentService,
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
        /** @var array<string, string> $body */
        $body = (array) $request->getParsedBody();

        $outSum = $body['OutSum'] ?? '';
        $invId = $body['InvId'] ?? '';
        $signatureValue = $body['SignatureValue'] ?? '';
        $invoiceUrlKey = $body['Shp_invoiceUrlKey'] ?? '';

        $shpParams = [];
        foreach ($body as $key => $value) {
            if (str_starts_with($key, 'Shp_')) {
                $shpParams[$key] = $value;
            }
        }

        if (!$this->robokassaPaymentService->verifyResultUrlCallback($outSum, $invId, $signatureValue, $shpParams)) {
            $this->logger->warning('Robokassa webhook: invalid signature.', ['inv_id' => $invId]);
            return $this->factory->createResponse('bad sign')->withStatus(400);
        }

        $this->markInvoicePaidIfFound($invId, $invoiceUrlKey);

        return $this->factory->createResponse('OK' . $invId);
    }

    private function markInvoicePaidIfFound(string $invId, string $invoiceUrlKey): void
    {
        $invoice = $invoiceUrlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey) : null;
        if (null === $invoice) {
            $this->logger->warning('Robokassa webhook: invoice not found for url_key.', ['url_key' => $invoiceUrlKey]);
            return;
        }

        /** @var \App\Infrastructure\Persistence\InvAmount\InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00)) {
            // Already recorded — Robokassa may redeliver the same callback.
            return;
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $this->recorder->record(
            new PaymentRecordContext(
                reference: $invoiceNumber . '-robokassa-' . $invId,
                invoice_id: (string) $invoiceAmountRecord->reqInvId(),
                balance: $invoiceAmountRecord->getBalance() ?? 0.00,
                invoice_payment_method: 4,
                invoice_number: $invoiceNumber,
                driver: 'Robokassa',
                d: 'robokassa',
                invoice_url_key: $invoiceUrlKey,
                response: true,
                sandbox_url_array: $this->sR->sandboxUrlArray(),
                provider_reference: $invId,
            ),
        );

        $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($invoice, $invoiceAmountRecord);
    }
}
