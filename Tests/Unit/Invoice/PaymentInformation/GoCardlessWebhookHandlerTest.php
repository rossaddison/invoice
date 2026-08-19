<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\PaymentInformation\Service\GoCardlessPaymentService;
use App\Invoice\PaymentInformation\Service\GoCardlessWebhookHandler;
use App\Invoice\PaymentInformation\Service\OnlinePaymentRecorderService;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

/**
 * Exercises GoCardlessWebhookHandler::handle() against its public contract,
 * mirroring AdyenWebhookHandlerTest's coverage. One structural difference
 * from Adyen/Stripe: a GoCardless webhook body is `{"events": [...]}` — a
 * batch, not a single event — and event payloads carry only
 * links.payment (the GoCardless payment id), never our own invoice
 * reference directly, so resolveContext() goes through
 * GoCardlessPaymentService::getPaymentMetadata() instead. InvRepository/
 * InvAmountRepository/SettingRepository/OnlinePaymentRecorderService/
 * GoCardlessPaymentService are `final`, mockable here only because
 * Tests/bootstrap.php enables DG\BypassFinals. Inv/InvAmount are plain
 * (non-final) Cycle entities, so they're mocked directly.
 */
final class GoCardlessWebhookHandlerTest extends TestCase
{
    private function makeRequest(string $payload, string $signature = 'sig'): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getBody')->willReturn(\GuzzleHttp\Psr7\Utils::streamFor($payload));
        $request->method('getHeaderLine')->willReturn($signature);

        return $request;
    }

    private function makeResponseFactory(): ResponseFactoryInterface
    {
        return new HttpFactory();
    }

    private function makeHandler(
        bool $validSignature,
        array $metadata,
        ?InvAmount $invoiceAmount,
        OnlinePaymentRecorderService $recorder,
        InvRepository $iR,
        ?InvPaymentSettlementService $invPaymentSettlementService = null,
    ): GoCardlessWebhookHandler {
        $goCardlessPaymentService = $this->createStub(GoCardlessPaymentService::class);
        $goCardlessPaymentService->method('isValidWebhookSignature')->willReturn($validSignature);
        $goCardlessPaymentService->method('getPaymentMetadata')->willReturn($metadata);

        $iaR = $this->createStub(InvAmountRepository::class);
        $iaR->method('repoInvquery')->willReturn($invoiceAmount);

        $sR = $this->createStub(SettingRepository::class);
        $sR->method('sandboxUrlArray')->willReturn([]);

        if ($invPaymentSettlementService === null) {
            $invPaymentSettlementService = $this->createMock(InvPaymentSettlementService::class);
            $invPaymentSettlementService->expects(self::never())->method('markInvoicePaidAndAdjustStock');
        }

        return new GoCardlessWebhookHandler(
            $goCardlessPaymentService,
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
    private function makeHandlerNoRecorder(bool $validSignature, array $metadata, ?Inv $invoice, ?InvAmount $invoiceAmount): GoCardlessWebhookHandler
    {
        $recorder = $this->createMock(OnlinePaymentRecorderService::class);
        $recorder->expects(self::never())->method('record');

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::never())->method('save');

        return $this->makeHandler($validSignature, $metadata, $invoiceAmount, $recorder, $iR);
    }

    public function testHandleReturns401WhenSignatureInvalid(): void
    {
        $handler = $this->makeHandlerNoRecorder(false, [], null, null);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('invalid signature', (string) $response->getBody());
    }

    public function testHandleAcknowledgesIrrelevantResourceType(): void
    {
        $body = (string) json_encode(['events' => [
            ['resource_type' => 'mandates', 'action' => 'active', 'links' => ['payment' => 'PM1']],
        ]]);
        $handler = $this->makeHandlerNoRecorder(true, [], null, null);

        $response = $handler->handle($this->makeRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleAcknowledgesNotYetActionableAction(): void
    {
        $body = (string) json_encode(['events' => [
            ['resource_type' => 'payments', 'action' => 'submitted', 'links' => ['payment' => 'PM1']],
        ]]);
        $handler = $this->makeHandlerNoRecorder(true, [], null, null);

        $response = $handler->handle($this->makeRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleAcknowledgesWhenInvoiceNotFound(): void
    {
        $body = (string) json_encode(['events' => [
            ['resource_type' => 'payments', 'action' => 'confirmed', 'links' => ['payment' => 'PM1']],
        ]]);
        $handler = $this->makeHandlerNoRecorder(true, ['invoice_url_key' => 'missing-key'], null, null);

        $response = $handler->handle($this->makeRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleAcknowledgesWhenInvoiceAlreadyProcessed(): void
    {
        $invoice = $this->createStub(Inv::class);
        $invoiceAmount = $this->createStub(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(0.00);

        $body = (string) json_encode(['events' => [
            ['resource_type' => 'payments', 'action' => 'confirmed', 'links' => ['payment' => 'PM1']],
        ]]);
        $handler = $this->makeHandlerNoRecorder(true, ['invoice_url_key' => 'inv-key'], $invoice, $invoiceAmount);

        $response = $handler->handle($this->makeRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleMarksInvoicePaidOnConfirmedPayment(): void
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
                return $ctx->driver === 'GoCardless'
                    && $ctx->d === 'gocardless'
                    && $ctx->response === true
                    && $ctx->invoice_payment_method === 4
                    && $ctx->balance === 50.0
                    && $ctx->invoice_id === '7'
                    && $ctx->provider_reference === 'PM1'
                    && $ctx->invoice_url_key === 'inv-key';
            },
        ))->willReturn($this->createStub(ResponseInterface::class));

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::never())->method('save');

        $invPaymentSettlementService = $this->createMock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->expects(self::once())
            ->method('markInvoicePaidAndAdjustStock')->with($invoice, $invoiceAmount);

        $body = (string) json_encode(['events' => [
            ['resource_type' => 'payments', 'action' => 'confirmed', 'links' => ['payment' => 'PM1']],
        ]]);
        $handler = $this->makeHandler(
            true,
            ['invoice_url_key' => 'inv-key'],
            $invoiceAmount,
            $recorder,
            $iR,
            $invPaymentSettlementService,
        );

        $response = $handler->handle($this->makeRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleMarksInvoiceFailedOnFailedPayment(): void
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
            static fn(PaymentRecordContext $ctx): bool => $ctx->response === false && $ctx->invoice_payment_method === 5,
        ))->willReturn($this->createStub(ResponseInterface::class));

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::once())->method('save')->with($invoice);

        $body = (string) json_encode(['events' => [
            ['resource_type' => 'payments', 'action' => 'failed', 'links' => ['payment' => 'PM2']],
        ]]);
        $handler = $this->makeHandler(true, ['invoice_url_key' => 'inv-key'], $invoiceAmount, $recorder, $iR);

        $response = $handler->handle($this->makeRequest($body));

        self::assertSame(200, $response->getStatusCode());
    }
}
