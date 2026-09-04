<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\PaymentInformation\Service\AdyenPaymentService;
use App\Invoice\PaymentInformation\Service\AdyenWebhookHandler;
use App\Invoice\PaymentInformation\Service\OnlinePaymentRecorderService;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

/**
 * Exercises AdyenWebhookHandler::handle() end-to-end against its public
 * contract, mirroring StripeWebhookHandlerTest's coverage of
 * resolveContext()/applyEvent(): signature rejection, an event code we don't
 * act on, invoice lookup misses, the already-processed idempotency guard, and
 * both the succeeded/failed status transitions. InvRepository/
 * InvAmountRepository/SettingRepository/OnlinePaymentRecorderService/
 * AdyenPaymentService are `final`, mockable here only because
 * Tests/bootstrap.php enables DG\BypassFinals. Inv/InvAmount are plain
 * (non-final) Cycle entities, so they're mocked directly.
 */
final class AdyenWebhookHandlerTest extends TestCase
{
    private function makeRequest(string $payload): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getBody')->willReturn(\GuzzleHttp\Psr7\Utils::streamFor($payload));

        return $request;
    }

    private function makeResponseFactory(): ResponseFactoryInterface
    {
        return new HttpFactory();
    }

    private function makeHandler(
        ?array $verifiedItem,
        ?InvAmount $invoiceAmount,
        OnlinePaymentRecorderService $recorder,
        InvRepository $iR,
        ?InvPaymentSettlementService $invPaymentSettlementService = null,
    ): AdyenWebhookHandler {
        $adyenPaymentService = $this->createStub(AdyenPaymentService::class);
        $adyenPaymentService->method('verifyWebhookNotification')->willReturn($verifiedItem);

        $iaR = $this->createStub(InvAmountRepository::class);
        $iaR->method('repoInvquery')->willReturn($invoiceAmount);

        $sR = $this->createStub(SettingRepository::class);
        $sR->method('sandboxUrlArray')->willReturn([]);

        if ($invPaymentSettlementService === null) {
            $invPaymentSettlementService = $this->createMock(InvPaymentSettlementService::class);
            $invPaymentSettlementService->expects(self::never())->method('markInvoicePaidAndAdjustStock');
        }

        return new AdyenWebhookHandler(
            $adyenPaymentService,
            $iR,
            $iaR,
            $sR,
            $invPaymentSettlementService,
            $recorder,
            $this->makeResponseFactory(),
            new NullLogger(),
        );
    }

    /** Convenience overload for tests that never reach the recorder. */
    private function makeHandlerNoRecorder(?array $verifiedItem, ?Inv $invoice, ?InvAmount $invoiceAmount): AdyenWebhookHandler
    {
        $recorder = $this->createMock(OnlinePaymentRecorderService::class);
        $recorder->expects(self::never())->method('record');

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::never())->method('save');

        return $this->makeHandler($verifiedItem, $invoiceAmount, $recorder, $iR);
    }

    public function testHandleReturns401WhenSignatureInvalid(): void
    {
        $handler = $this->makeHandlerNoRecorder(null, null, null);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('invalid signature', (string) $response->getBody());
    }

    public function testHandleAcknowledgesIrrelevantEventCode(): void
    {
        $item = ['eventCode' => 'REFUND', 'merchantReference' => 'inv-key'];
        $handler = $this->makeHandlerNoRecorder($item, null, null);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('[accepted]', (string) $response->getBody());
    }

    public function testHandleAcknowledgesWhenInvoiceNotFound(): void
    {
        $item = ['eventCode' => 'AUTHORISATION', 'merchantReference' => 'missing-key'];
        $handler = $this->makeHandlerNoRecorder($item, null, null);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('[accepted]', (string) $response->getBody());
    }

    public function testHandleAcknowledgesWhenInvoiceAlreadyProcessed(): void
    {
        $invoice = $this->createStub(Inv::class);
        $invoiceAmount = $this->createStub(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(0.00);

        $item = ['eventCode' => 'AUTHORISATION', 'merchantReference' => 'inv-key'];
        $handler = $this->makeHandlerNoRecorder($item, $invoice, $invoiceAmount);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testHandleMarksInvoicePaidOnSuccessfulAuthorisation(): void
    {
        $invoice = $this->createMock(Inv::class);
        $invoice->method('getNumber')->willReturn('INV-001');
        // setStatusId/setPaymentMethod/save and the InvAmount balance/paid
        // settlement are InvPaymentSettlementService's own responsibility
        // now (covered by InvPaymentSettlementServiceTest) — this test only
        // needs to confirm the handler hands off the right objects.

        $invoiceAmount = $this->createMock(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(50.0);
        $invoiceAmount->method('reqInvId')->willReturn(7);

        $recorder = $this->createMock(OnlinePaymentRecorderService::class);
        $recorder->expects(self::once())->method('record')->with(self::callback(
            static function (PaymentRecordContext $ctx): bool {
                return $ctx->driver === 'Adyen'
                    && $ctx->d === 'adyen'
                    && $ctx->response === true
                    && $ctx->invoice_payment_method === 4
                    && $ctx->balance === 50.0
                    && $ctx->invoice_id === '7'
                    && $ctx->provider_reference === 'psp_1'
                    && $ctx->invoice_url_key === 'inv-key';
            },
        ))->willReturn($this->createStub(ResponseInterface::class));

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::never())->method('save');

        $invPaymentSettlementService = $this->createMock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->expects(self::once())
            ->method('markInvoicePaidAndAdjustStock')->with($invoice, $invoiceAmount);

        $item = [
            'eventCode' => 'AUTHORISATION',
            'merchantReference' => 'inv-key',
            'pspReference' => 'psp_1',
            'success' => 'true',
        ];
        $handler = $this->makeHandler($item, $invoiceAmount, $recorder, $iR, $invPaymentSettlementService);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('[accepted]', (string) $response->getBody());
    }

    public function testHandleMarksInvoiceFailedOnUnsuccessfulAuthorisation(): void
    {
        $invoice = $this->createMock(Inv::class);
        $invoice->method('getNumber')->willReturn('INV-002');
        $invoice->expects(self::once())->method('setStatusId')->with(6);
        $invoice->expects(self::never())->method('setPaymentMethod');

        $invoiceAmount = $this->createMock(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(50.0);
        $invoiceAmount->method('reqInvId')->willReturn(7);
        $invoiceAmount->expects(self::never())->method('setBalance');
        $invoiceAmount->expects(self::never())->method('setPaid');

        $recorder = $this->createMock(OnlinePaymentRecorderService::class);
        $recorder->expects(self::once())->method('record')->with(self::callback(
            static fn (PaymentRecordContext $ctx): bool => $ctx->response === false && $ctx->invoice_payment_method === 5,
        ))->willReturn($this->createStub(ResponseInterface::class));

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::once())->method('save')->with($invoice);

        $item = [
            'eventCode' => 'AUTHORISATION',
            'merchantReference' => 'inv-key',
            'pspReference' => 'psp_2',
            'success' => 'false',
        ];
        $handler = $this->makeHandler($item, $invoiceAmount, $recorder, $iR);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
    }
}
