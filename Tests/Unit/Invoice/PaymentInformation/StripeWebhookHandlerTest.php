<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\PaymentInformation\Service\OnlinePaymentRecorderService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\PaymentInformation\Service\StripeWebhookHandler;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use Stripe\Event;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Json\Json;

/**
 * Exercises StripeWebhookHandler::handle() end-to-end against its public
 * contract, covering the branching resolveContext()/applyEvent() do
 * internally: signature rejection, irrelevant event types, invoice lookup
 * misses, the already-processed idempotency guard, and both the
 * succeeded/failed status transitions. InvRepository/InvAmountRepository/
 * SettingRepository/OnlinePaymentRecorderService are `final`, mockable here
 * only because Tests/bootstrap.php enables DG\BypassFinals. Inv/InvAmount are
 * plain (non-final) Cycle entities, so they're mocked directly.
 */
final class StripeWebhookHandlerTest extends TestCase
{
    private function makeRequest(string $payload, string $signature = 'sig'): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getBody')->willReturn(\GuzzleHttp\Psr7\Utils::streamFor($payload));
        $request->method('getHeaderLine')->willReturn($signature);

        return $request;
    }

    private function makeResponseFactory(): DataResponseFactoryInterface
    {
        $factory = $this->createStub(DataResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturnCallback(
            static fn(mixed $data = null): ResponseInterface => new Response(200, [], (string) $data),
        );

        return $factory;
    }

    /** @param array<string, mixed> $paymentIntent */
    private function fakeEvent(string $type, array $paymentIntent): Event
    {
        return Event::constructFrom([
            'id' => 'evt_1',
            'type' => $type,
            'data' => ['object' => ['object' => 'payment_intent'] + $paymentIntent],
        ]);
    }

    private function makeHandler(
        ?Event $verifiedEvent,
        ?InvAmount $invoiceAmount,
        OnlinePaymentRecorderService $recorder,
        InvRepository $iR,
        ?InvPaymentSettlementService $invPaymentSettlementService = null,
    ): StripeWebhookHandler {
        $stripePaymentService = $this->createStub(StripePaymentService::class);
        $stripePaymentService->method('verifyWebhookSignature')->willReturn($verifiedEvent);

        $iaR = $this->createStub(InvAmountRepository::class);
        $iaR->method('repoInvquery')->willReturn($invoiceAmount);

        $sR = $this->createStub(SettingRepository::class);
        $sR->method('sandboxUrlArray')->willReturn([]);

        if ($invPaymentSettlementService === null) {
            $invPaymentSettlementService = $this->createMock(InvPaymentSettlementService::class);
            $invPaymentSettlementService->expects(self::never())->method('markInvoicePaidAndAdjustStock');
        }

        return new StripeWebhookHandler(
            $stripePaymentService,
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
    private function makeHandlerNoRecorder(?Event $verifiedEvent, ?Inv $invoice, ?InvAmount $invoiceAmount): StripeWebhookHandler
    {
        $recorder = $this->createMock(OnlinePaymentRecorderService::class);
        $recorder->expects(self::never())->method('record');

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::never())->method('save');

        return $this->makeHandler($verifiedEvent, $invoiceAmount, $recorder, $iR);
    }

    public function testHandleReturns400WhenSignatureInvalid(): void
    {
        $handler = $this->makeHandlerNoRecorder(null, null, null);

        $response = $handler->handle($this->makeRequest('{}', 'bad-sig'));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['error' => 'invalid signature'], Json::decode((string) $response->getBody()));
    }

    public function testHandleAcknowledgesIrrelevantEventType(): void
    {
        $event = $this->fakeEvent('payment_intent.created', ['id' => 'pi_1']);
        $handler = $this->makeHandlerNoRecorder($event, null, null);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['received' => true], Json::decode((string) $response->getBody()));
    }

    public function testHandleAcknowledgesWhenInvoiceNotFound(): void
    {
        $event = $this->fakeEvent('payment_intent.succeeded', [
            'id' => 'pi_1',
            'metadata' => ['invoice_url_key' => 'missing-key'],
        ]);
        $handler = $this->makeHandlerNoRecorder($event, null, null);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleAcknowledgesWhenInvoiceAlreadyProcessed(): void
    {
        $invoice = $this->createStub(Inv::class);
        $invoiceAmount = $this->createStub(InvAmount::class);
        $invoiceAmount->method('getBalance')->willReturn(0.00);

        $event = $this->fakeEvent('payment_intent.succeeded', [
            'id' => 'pi_1',
            'metadata' => ['invoice_url_key' => 'inv-key'],
        ]);
        $handler = $this->makeHandlerNoRecorder($event, $invoice, $invoiceAmount);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleMarksInvoicePaidOnSucceededEvent(): void
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
                return $ctx->driver === 'Stripe'
                    && $ctx->d === 'stripe'
                    && $ctx->response === true
                    && $ctx->invoice_payment_method === 4
                    && $ctx->balance === 50.0
                    && $ctx->invoice_id === '7'
                    && $ctx->provider_reference === 'pi_1'
                    && $ctx->invoice_url_key === 'inv-key';
            },
        ))->willReturn($this->createStub(ResponseInterface::class));

        $iR = $this->createMock(InvRepository::class);
        $iR->method('repoUrlKeyGuestLoaded')->willReturn($invoice);
        $iR->expects(self::never())->method('save');

        $invPaymentSettlementService = $this->createMock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->expects(self::once())
            ->method('markInvoicePaidAndAdjustStock')->with($invoice, $invoiceAmount);

        $event = $this->fakeEvent('payment_intent.succeeded', [
            'id' => 'pi_1',
            'metadata' => ['invoice_url_key' => 'inv-key'],
        ]);
        $handler = $this->makeHandler($event, $invoiceAmount, $recorder, $iR, $invPaymentSettlementService);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['received' => true], Json::decode((string) $response->getBody()));
    }

    public function testHandleMarksInvoiceFailedOnPaymentFailedEvent(): void
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

        $event = $this->fakeEvent('payment_intent.payment_failed', [
            'id' => 'pi_2',
            'metadata' => ['invoice_url_key' => 'inv-key'],
        ]);
        $handler = $this->makeHandler($event, $invoiceAmount, $recorder, $iR);

        $response = $handler->handle($this->makeRequest('{}'));

        self::assertSame(200, $response->getStatusCode());
    }
}
