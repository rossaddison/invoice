<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\WorldpayMerchant\WorldpayMerchant;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\Payment\PaymentService;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\PaymentInformation\Service\WorldpayPaymentService;
use App\Invoice\PaymentInformation\Service\WorldpaySignatureService;
use App\Invoice\PaymentInformation\Service\WorldpayWebhookHandler;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\WorldpayMerchant\WorldpayMerchantRepository;
use App\Invoice\WorldpayMerchant\WorldpayMerchantService;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

/**
 * Exercises WorldpayWebhookHandler::handle() end-to-end against its
 * public contract: signature rejection, an event type this app doesn't
 * act on, a transactionReference with no matching WorldpayMerchant row,
 * the belt-and-braces re-confirm disagreeing with a `settled` event,
 * and both the settled/failed outcomes actually flowing through to
 * InvPaymentSettlementService/invoice status. WorldpayPaymentService,
 * InvRepository, InvAmountRepository, SettingRepository,
 * WorldpayMerchantRepository, WorldpayMerchantService, PaymentService,
 * and InvPaymentSettlementService are `final`, mockable here only
 * because Tests/bootstrap.php enables DG\BypassFinals — same
 * convention as AdyenWebhookHandlerTest. Inv/InvAmount/WorldpayMerchant
 * are plain (non-final) Cycle entities, mocked directly.
 */
final class WorldpayWebhookHandlerTest extends TestCase
{
    private const string SECRET = 'whsec_test';

    private function signedRequest(string $body): ServerRequestInterface
    {
        $signature = hash_hmac('sha256', $body, self::SECRET);
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getBody')->willReturn(\GuzzleHttp\Psr7\Utils::streamFor($body));
        $request->method('getHeaderLine')->willReturn("keyid1/SHA256/{$signature}");

        return $request;
    }

    private function unsignedRequest(string $body): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getBody')->willReturn(\GuzzleHttp\Psr7\Utils::streamFor($body));
        $request->method('getHeaderLine')->willReturn('keyid1/SHA256/wrong');

        return $request;
    }

    private function makeSettingRepository(): SettingRepository
    {
        $sR = $this->createStub(SettingRepository::class);
        $sR->method('getSetting')->willReturn('enc-secret');
        $sR->method('decode')->willReturn(self::SECRET);

        return $sR;
    }

    private function makeHandler(
        ?WorldpayMerchant $worldpayMerchant,
        ?InvAmount $invoiceAmount,
        ?Inv $invoice,
        WorldpayMerchantService $worldpayMerchantService,
        PaymentService $paymentService,
        InvRepository $iR,
        ?InvPaymentSettlementService $invPaymentSettlementService = null,
        ?PaymentVerificationResult $verification = null,
    ): WorldpayWebhookHandler {
        $worldpayMerchantRepository = $this->createStub(WorldpayMerchantRepository::class);
        $worldpayMerchantRepository->method('repoWorldpayMerchantByTransactionReference')->willReturn($worldpayMerchant);

        $worldpayPaymentService = $this->createStub(WorldpayPaymentService::class);
        $worldpayPaymentService->method('verifyPayment')->willReturn(
            $verification ?? new PaymentVerificationResult(true, 'https://try.access.worldpay.com/api/payments/opaque', 'settled'),
        );

        $iaR = $this->createStub(InvAmountRepository::class);
        $iaR->method('repoInvquery')->willReturn($invoiceAmount);

        if (null !== $invoice) {
            $iR->method('repoInvUnLoadedquery')->willReturn($invoice);
        }

        if ($invPaymentSettlementService === null) {
            $invPaymentSettlementService = $this->createMock(InvPaymentSettlementService::class);
            $invPaymentSettlementService->expects(self::never())->method('markInvoicePaidAndAdjustStock');
        }

        return new WorldpayWebhookHandler(
            $worldpayPaymentService,
            new WorldpaySignatureService(),
            $worldpayMerchantRepository,
            $worldpayMerchantService,
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $paymentService,
            $invPaymentSettlementService,
            new HttpFactory(),
            new NullLogger(),
        );
    }

    /** Convenience overload for tests that never reach a persistence write. */
    private function makeHandlerNoWrites(?WorldpayMerchant $worldpayMerchant, ?Inv $invoice, ?InvAmount $invoiceAmount): WorldpayWebhookHandler
    {
        $worldpayMerchantService = $this->createMock(WorldpayMerchantService::class);
        $worldpayMerchantService->expects(self::never())->method('saveWorldpayMerchantViaPaymentHandler');

        $paymentService = $this->createMock(PaymentService::class);
        $paymentService->expects(self::never())->method('addPaymentViaPaymentHandler');

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::never())->method('save');

        return $this->makeHandler($worldpayMerchant, $invoiceAmount, $invoice, $worldpayMerchantService, $paymentService, $iR);
    }

    public function testHandleReturns401WhenSignatureInvalid(): void
    {
        $handler = $this->makeHandlerNoWrites(null, null, null);

        $response = $handler->handle($this->unsignedRequest('{}'));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testHandleAcknowledgesAnEventTypeThisAppDoesNotActOn(): void
    {
        $body = json_encode(['eventDetails' => ['type' => 'tokenCreated', 'transactionReference' => 'inv-1-abc']]);
        $handler = $this->makeHandlerNoWrites(null, null, null);

        $response = $handler->handle($this->signedRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleAcknowledgesWhenNoMatchingWorldpayMerchantRow(): void
    {
        $body = json_encode(['eventDetails' => ['type' => 'settled', 'transactionReference' => 'inv-unknown']]);
        $handler = $this->makeHandlerNoWrites(null, null, null);

        $response = $handler->handle($this->signedRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleAcknowledgesWhenInvoiceAlreadyProcessed(): void
    {
        $worldpayMerchant = $this->createStub(WorldpayMerchant::class);
        $worldpayMerchant->method('reqInvId')->willReturn(7);
        $invoice = $this->createStub(Inv::class);
        $invoiceAmount = $this->createStub(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(0.00);

        $body = json_encode(['eventDetails' => ['type' => 'settled', 'transactionReference' => 'inv-1-abc']]);
        $handler = $this->makeHandlerNoWrites($worldpayMerchant, $invoice, $invoiceAmount);

        $response = $handler->handle($this->signedRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleDoesNotSettleWhenReconfirmationDisagrees(): void
    {
        $worldpayMerchant = $this->createStub(WorldpayMerchant::class);
        $worldpayMerchant->method('reqInvId')->willReturn(7);
        $worldpayMerchant->method('getSelfHref')->willReturn('https://try.access.worldpay.com/api/payments/opaque');
        $invoice = $this->createStub(Inv::class);
        $invoiceAmount = $this->createStub(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(50.0);

        $worldpayMerchantService = $this->createMock(WorldpayMerchantService::class);
        $worldpayMerchantService->expects(self::never())->method('saveWorldpayMerchantViaPaymentHandler');
        $paymentService = $this->createMock(PaymentService::class);
        $paymentService->expects(self::never())->method('addPaymentViaPaymentHandler');
        $iR = $this->createMock(InvRepository::class);
        $iR->expects(self::never())->method('save');

        $body = json_encode(['eventDetails' => ['type' => 'settled', 'transactionReference' => 'inv-1-abc']]);
        $handler = $this->makeHandler(
            $worldpayMerchant,
            $invoiceAmount,
            $invoice,
            $worldpayMerchantService,
            $paymentService,
            $iR,
            null,
            new PaymentVerificationResult(false, 'https://try.access.worldpay.com/api/payments/opaque', 'refused'),
        );

        $response = $handler->handle($this->signedRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * $invoice/$invoiceAmount are createMock() configured only with
     * willReturn(), never their own expects() — their correctness is
     * still verified via $invPaymentSettlementService's
     * ->with($invoice, $invoiceAmount) expectation below, which still
     * runs and still fails the test if unmet. Same pattern already
     * used by AdyenWebhookHandlerTest's equivalent test — see
     * InvItemServiceCreditTest's own docblock for the fuller
     * explanation of why this is safe.
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testHandleMarksInvoicePaidOnSettledEvent(): void
    {
        $worldpayMerchant = $this->createStub(WorldpayMerchant::class);
        $worldpayMerchant->method('reqInvId')->willReturn(7);
        $worldpayMerchant->method('getSelfHref')->willReturn('https://try.access.worldpay.com/api/payments/opaque');
        $worldpayMerchant->method('getTransactionReference')->willReturn('inv-1-abc');
        $worldpayMerchant->method('getReference')->willReturn('INV-0001-inv-1-abc');
        $worldpayMerchant->method('getPaymentId')->willReturn('payI-123');

        $invoice = $this->createMock(Inv::class);
        $invoice->method('reqId')->willReturn(7);
        $invoice->method('getNumber')->willReturn('INV-0001');

        $invoiceAmount = $this->createMock(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(50.0);

        $worldpayMerchantService = $this->createMock(WorldpayMerchantService::class);
        $worldpayMerchantService->expects(self::once())->method('saveWorldpayMerchantViaPaymentHandler')
            ->with($worldpayMerchant, self::callback(
                static fn(array $a): bool => $a['merchant_response_successful'] === true
                    && $a['merchant_response_transaction_reference'] === 'inv-1-abc',
            ));

        $paymentService = $this->createMock(PaymentService::class);
        $paymentService->expects(self::once())->method('addPaymentViaPaymentHandler')
            ->with(self::isInstanceOf(\App\Infrastructure\Persistence\Payment\Payment::class), self::callback(
                static fn(array $a): bool => $a['inv_id'] === 7 && $a['amount'] === 50.0,
            ));

        $iR = $this->createMock(InvRepository::class);
        $iR->expects(self::never())->method('save');

        $invPaymentSettlementService = $this->createMock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->expects(self::once())
            ->method('markInvoicePaidAndAdjustStock')->with($invoice, $invoiceAmount);

        $body = json_encode(['eventDetails' => ['type' => 'settled', 'transactionReference' => 'inv-1-abc']]);
        $handler = $this->makeHandler($worldpayMerchant, $invoiceAmount, $invoice, $worldpayMerchantService, $paymentService, $iR, $invPaymentSettlementService);

        $response = $handler->handle($this->signedRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleMarksInvoiceFailedOnRefusedEvent(): void
    {
        $worldpayMerchant = $this->createStub(WorldpayMerchant::class);
        $worldpayMerchant->method('reqInvId')->willReturn(7);
        $worldpayMerchant->method('getTransactionReference')->willReturn('inv-1-abc');
        $worldpayMerchant->method('getReference')->willReturn('INV-0001-inv-1-abc');

        $invoice = $this->createMock(Inv::class);
        $invoice->method('reqId')->willReturn(7);
        $invoice->expects(self::once())->method('setStatusId')->with(6);

        $invoiceAmount = $this->createStub(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(50.0);

        $worldpayMerchantService = $this->createMock(WorldpayMerchantService::class);
        $worldpayMerchantService->expects(self::once())->method('saveWorldpayMerchantViaPaymentHandler')
            ->with($worldpayMerchant, self::callback(
                static fn(array $a): bool => $a['merchant_response_successful'] === false,
            ));

        $paymentService = $this->createMock(PaymentService::class);
        $paymentService->expects(self::never())->method('addPaymentViaPaymentHandler');

        $iR = $this->createMock(InvRepository::class);
        $iR->expects(self::once())->method('save')->with($invoice);

        $body = json_encode(['eventDetails' => ['type' => 'refused', 'transactionReference' => 'inv-1-abc']]);
        $handler = $this->makeHandler($worldpayMerchant, $invoiceAmount, $invoice, $worldpayMerchantService, $paymentService, $iR);

        $response = $handler->handle($this->signedRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }
}
