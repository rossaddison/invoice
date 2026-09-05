<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentInformation\Service\BitPayPaymentService;
use App\Invoice\PaymentInformation\Service\BitPayWebhookHandler;
use App\Invoice\PaymentInformation\Service\OnlinePaymentRecorderService;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery as m;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactory;

/**
 * Covers BitPayWebhookHandler end to end: real `x-signature` computation/
 * verification (via a real BitPayPaymentService, matching the same token
 * used to sign) and a mocked Guzzle handler for its `GET /invoices/{id}`
 * re-confirmation call — the same belt-and-braces "never trust the webhook
 * body alone" pattern every other gateway's webhook test in this app
 * exercises (see CheckoutComWebhookHandlerTest).
 */
#[Test]
final class BitPayWebhookHandlerTest
{
    private const string POS_TOKEN = 'pos-token-123';

    private function makeDataResponseFactory(): DataResponseFactory
    {
        return new DataResponseFactory(new HttpFactory());
    }

    private function makeRequest(string $rawBody, string $signature): ServerRequestInterface
    {
        $httpFactory = new HttpFactory();
        $body = $httpFactory->createStream($rawBody);

        return $httpFactory
            ->createServerRequest('POST', '/paymentinformation/bitPayWebhook')
            ->withBody($body)
            ->withHeader('x-signature', $signature);
    }

    private function sign(string $rawBody): string
    {
        $canonical = json_encode(
            json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        return base64_encode(hash_hmac('sha256', (string) $canonical, self::POS_TOKEN, true));
    }

    private function makeBitPaySettingRepository(): SettingRepository&m\MockInterface
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_bitpay_posToken')->andReturn('enc-pos-token');
        $sR->shouldReceive('decode')->with('enc-pos-token')->andReturn(self::POS_TOKEN);
        $sR->shouldReceive('getSetting')->with('gateway_bitpay_sandbox')->andReturn('0');
        $sR->shouldReceive('sandboxUrlArray')->andReturn(['bitpay' => 'https://test.bitpay.com/dashboard']);

        return $sR;
    }

    /**
     * @return LoggerInterface&m\MockInterface
     */
    private function makeLoggerSpy(): LoggerInterface
    {
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::spy(LoggerInterface::class);
        return $logger;
    }

    private function makePaymentService(MockHandler $mock): BitPayPaymentService
    {
        $httpClient = new HttpClient(['handler' => HandlerStack::create($mock)]);

        return new BitPayPaymentService(
            $this->makeBitPaySettingRepository(),
            $this->makeLoggerSpy(),
            $httpClient,
        );
    }

    public function handleReturnsBadRequestWhenSignatureIsInvalid(): void
    {
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var InvPaymentSettlementService&m\MockInterface $invPaymentSettlementService */
        $invPaymentSettlementService = m::mock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->shouldNotReceive('markInvoicePaidAndAdjustStock');
        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new BitPayWebhookHandler(
            $this->makePaymentService(new MockHandler([])),
            $this->makeBitPaySettingRepository(),
            $iR,
            $iaR,
            $invPaymentSettlementService,
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
        );

        $rawBody = json_encode(['data' => ['id' => 'inv-123', 'orderId' => 'abc123', 'status' => 'complete']], JSON_THROW_ON_ERROR);
        $response = $handler->handle($this->makeRequest($rawBody, 'not-the-real-signature'));

        Assert::same(400, $response->getStatusCode());
    }

    public function handleReturnsBadRequestForAMalformedBody(): void
    {
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var InvPaymentSettlementService&m\MockInterface $invPaymentSettlementService */
        $invPaymentSettlementService = m::mock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->shouldNotReceive('markInvoicePaidAndAdjustStock');
        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new BitPayWebhookHandler(
            $this->makePaymentService(new MockHandler([])),
            $this->makeBitPaySettingRepository(),
            $iR,
            $iaR,
            $invPaymentSettlementService,
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
        );

        $rawBody = '{not valid json';
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign('{}')));

        Assert::same(400, $response->getStatusCode());
    }

    public function handleMarksInvoicePaidWhenCompleteAndVerified(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                'data' => ['id' => 'inv-123', 'url' => 'https://bitpay.com/i', 'status' => 'complete', 'price' => 10.0, 'currency' => 'GBP'],
            ], JSON_THROW_ON_ERROR)),
        ]);

        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('reqId')->andReturn(42);
        $invoice->shouldReceive('getNumber')->andReturn('INV125');

        /** @var InvAmount&m\MockInterface $invoiceAmountRecord */
        $invoiceAmountRecord = m::mock(InvAmount::class);
        $invoiceAmountRecord->shouldReceive('getBalance')->andReturn(10.00);
        $invoiceAmountRecord->shouldReceive('reqInvId')->andReturn(42);

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('abc123')->andReturn($invoice);

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->once()->with(42)->andReturn($invoiceAmountRecord);

        /** @var InvPaymentSettlementService&m\MockInterface $invPaymentSettlementService */
        $invPaymentSettlementService = m::mock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->shouldReceive('markInvoicePaidAndAdjustStock')
            ->once()->with($invoice, $invoiceAmountRecord);

        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldReceive('record')->once();

        $handler = new BitPayWebhookHandler(
            $this->makePaymentService($mock),
            $this->makeBitPaySettingRepository(),
            $iR,
            $iaR,
            $invPaymentSettlementService,
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
        );

        $rawBody = json_encode(['data' => ['id' => 'inv-123', 'orderId' => 'abc123', 'status' => 'paid']], JSON_THROW_ON_ERROR);
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleDoesNotReRecordWhenAlreadyPaid(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                'data' => ['id' => 'inv-123', 'url' => 'https://bitpay.com/i', 'status' => 'complete', 'price' => 10.0, 'currency' => 'GBP'],
            ], JSON_THROW_ON_ERROR)),
        ]);

        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('reqId')->andReturn(42);

        /** @var InvAmount&m\MockInterface $invoiceAmountRecord */
        $invoiceAmountRecord = m::mock(InvAmount::class);
        // Already recorded — BitPay may redeliver the same notification
        // on every status transition it retries.
        $invoiceAmountRecord->shouldReceive('getBalance')->andReturn(0.00);

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('abc123')->andReturn($invoice);

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->once()->with(42)->andReturn($invoiceAmountRecord);

        /** @var InvPaymentSettlementService&m\MockInterface $invPaymentSettlementService */
        $invPaymentSettlementService = m::mock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->shouldNotReceive('markInvoicePaidAndAdjustStock');

        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new BitPayWebhookHandler(
            $this->makePaymentService($mock),
            $this->makeBitPaySettingRepository(),
            $iR,
            $iaR,
            $invPaymentSettlementService,
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
        );

        $rawBody = json_encode(['data' => ['id' => 'inv-123', 'orderId' => 'abc123', 'status' => 'complete']], JSON_THROW_ON_ERROR);
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleAcknowledgesWithOkWhenInvoiceUrlKeyIsNotFound(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                'data' => ['id' => 'inv-123', 'url' => 'https://bitpay.com/i', 'status' => 'complete', 'price' => 10.0, 'currency' => 'GBP'],
            ], JSON_THROW_ON_ERROR)),
        ]);

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('unknown-key')->andReturn(null);

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var InvPaymentSettlementService&m\MockInterface $invPaymentSettlementService */
        $invPaymentSettlementService = m::mock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->shouldNotReceive('markInvoicePaidAndAdjustStock');
        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new BitPayWebhookHandler(
            $this->makePaymentService($mock),
            $this->makeBitPaySettingRepository(),
            $iR,
            $iaR,
            $invPaymentSettlementService,
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
        );

        $rawBody = json_encode(['data' => ['id' => 'inv-123', 'orderId' => 'unknown-key', 'status' => 'complete']], JSON_THROW_ON_ERROR);
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleDoesNothingWhenReCheckDoesNotConfirmComplete(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                'data' => ['id' => 'inv-123', 'url' => 'https://bitpay.com/i', 'status' => 'confirmed', 'price' => 10.0, 'currency' => 'GBP'],
            ], JSON_THROW_ON_ERROR)),
        ]);

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldNotReceive('repoUrlKeyGuestLoaded');

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var InvPaymentSettlementService&m\MockInterface $invPaymentSettlementService */
        $invPaymentSettlementService = m::mock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->shouldNotReceive('markInvoicePaidAndAdjustStock');
        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new BitPayWebhookHandler(
            $this->makePaymentService($mock),
            $this->makeBitPaySettingRepository(),
            $iR,
            $iaR,
            $invPaymentSettlementService,
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
        );

        // BitPay's own IPN fires on every status transition, not just
        // 'complete' — the webhook body's own status is never trusted
        // regardless of what it says (see this class's own docblock).
        $rawBody = json_encode(['data' => ['id' => 'inv-123', 'orderId' => 'abc123', 'status' => 'confirmed']], JSON_THROW_ON_ERROR);
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }
}
