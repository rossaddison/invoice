<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentInformation\Service\CheckoutComPaymentService;
use App\Invoice\PaymentInformation\Service\CheckoutComSignatureService;
use App\Invoice\PaymentInformation\Service\CheckoutComWebhookHandler;
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
 * Covers CheckoutComWebhookHandler end to end: real `Cko-Signature`
 * computation/verification (CheckoutComSignatureService), and a real
 * CheckoutComPaymentService wired to a mocked Guzzle handler for its
 * `GET /payments/{id}` re-confirmation call — the same belt-and-braces
 * "never trust the webhook body alone" pattern every other gateway's
 * webhook test in this app exercises.
 */
#[Test]
final class CheckoutComWebhookHandlerTest
{
    private const string VALID_SECRET_KEY = 'sk_sbox_abcdefghijklmnopqrstuvwxyza';
    private const string WEBHOOK_SIGNING_KEY = 'whsec_test_signing_key';

    private function makeDataResponseFactory(): DataResponseFactory
    {
        return new DataResponseFactory(new HttpFactory());
    }

    private function makeRequest(string $rawBody, string $signature): ServerRequestInterface
    {
        $httpFactory = new HttpFactory();
        $body = $httpFactory->createStream($rawBody);

        return $httpFactory
            ->createServerRequest('POST', '/paymentinformation/checkoutComWebhook')
            ->withBody($body)
            ->withHeader('Cko-Signature', $signature);
    }

    private function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, self::WEBHOOK_SIGNING_KEY);
    }

    private function makeSettingRepository(): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_secretKey')->andReturn('enc-secret');
        $sR->shouldReceive('decode')->with('enc-secret')->andReturn(self::VALID_SECRET_KEY);
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_publicKey')->andReturn('');
        $sR->shouldReceive('decode')->with('')->andReturn('');
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_sandbox')->andReturn('1');
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_webhookSecret')->andReturn('enc-webhook-secret');
        $sR->shouldReceive('decode')->with('enc-webhook-secret')->andReturn(self::WEBHOOK_SIGNING_KEY);
        $sR->shouldReceive('sandboxUrlArray')->andReturn(['checkout_com' => 'https://hub.checkout.com/']);

        return $sR;
    }

    private function makePaymentService(MockHandler $mock): CheckoutComPaymentService
    {
        $httpClient = new HttpClient(['handler' => HandlerStack::create($mock)]);

        return new CheckoutComPaymentService(
            $this->makeSettingRepository(),
            m::spy(LoggerInterface::class),
            $httpClient,
        );
    }

    public function handleReturnsBadRequestWhenSignatureIsInvalid(): void
    {
        $iR = m::mock(InvRepository::class);
        $iaR = m::mock(InvAmountRepository::class);
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');
        $sR = $this->makeSettingRepository();

        $handler = new CheckoutComWebhookHandler(
            $this->makePaymentService(new MockHandler([])),
            new CheckoutComSignatureService(),
            $sR,
            $iR,
            $iaR,
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
        );

        $rawBody = json_encode(['type' => 'payment_captured', 'data' => ['id' => 'pay_123']], JSON_THROW_ON_ERROR);
        $response = $handler->handle($this->makeRequest($rawBody, 'not-the-real-signature'));

        Assert::same(400, $response->getStatusCode());
    }

    public function handleAcknowledgesWithOkForANonCaptureEventType(): void
    {
        $iR = m::mock(InvRepository::class);
        $iR->shouldNotReceive('repoUrlKeyGuestLoaded');
        $iaR = m::mock(InvAmountRepository::class);
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');
        $sR = $this->makeSettingRepository();

        $handler = new CheckoutComWebhookHandler(
            $this->makePaymentService(new MockHandler([])),
            new CheckoutComSignatureService(),
            $sR,
            $iR,
            $iaR,
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
        );

        $rawBody = json_encode(
            ['type' => 'payment_approved', 'data' => ['id' => 'pay_123', 'reference' => 'abc123']],
            JSON_THROW_ON_ERROR,
        );
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleMarksInvoicePaidWhenCapturedAndVerified(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 'pay_123', 'status' => 'Captured'], JSON_THROW_ON_ERROR)),
        ]);

        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('reqId')->andReturn(42);
        $invoice->shouldReceive('getNumber')->andReturn('INV125');
        $invoice->shouldReceive('setStatusId')->once()->with(4);
        $invoice->shouldReceive('setPaymentMethod')->once()->with(4);

        $invoiceAmountRecord = m::mock(InvAmount::class);
        $invoiceAmountRecord->shouldReceive('getBalance')->andReturn(10.00);
        $invoiceAmountRecord->shouldReceive('reqInvId')->andReturn(42);
        $invoiceAmountRecord->shouldReceive('getTotal')->andReturn(10.00);
        $invoiceAmountRecord->shouldReceive('setBalance')->once()->with(0);
        $invoiceAmountRecord->shouldReceive('setPaid')->once()->with(10.00);

        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('abc123')->andReturn($invoice);
        $iR->shouldReceive('save')->once()->with($invoice);

        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->once()->with(42)->andReturn($invoiceAmountRecord);
        $iaR->shouldReceive('save')->once()->with($invoiceAmountRecord);

        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldReceive('record')->once();

        $sR = $this->makeSettingRepository();

        $handler = new CheckoutComWebhookHandler(
            $this->makePaymentService($mock),
            new CheckoutComSignatureService(),
            $sR,
            $iR,
            $iaR,
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
        );

        $rawBody = json_encode(
            ['type' => 'payment_captured', 'data' => ['id' => 'pay_123', 'reference' => 'abc123']],
            JSON_THROW_ON_ERROR,
        );
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleDoesNotReRecordWhenAlreadyPaid(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 'pay_123', 'status' => 'Captured'], JSON_THROW_ON_ERROR)),
        ]);

        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('reqId')->andReturn(42);
        $invoice->shouldNotReceive('setStatusId');
        $invoice->shouldNotReceive('setPaymentMethod');

        $invoiceAmountRecord = m::mock(InvAmount::class);
        // Already recorded — checkoutComComplete()'s own redirect never
        // writes payment state itself (read-only, see the controller's
        // docblock), but a redelivered notification should still no-op.
        $invoiceAmountRecord->shouldReceive('getBalance')->andReturn(0.00);
        $invoiceAmountRecord->shouldNotReceive('setBalance');
        $invoiceAmountRecord->shouldNotReceive('setPaid');

        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('abc123')->andReturn($invoice);
        $iR->shouldNotReceive('save');

        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->once()->with(42)->andReturn($invoiceAmountRecord);
        $iaR->shouldNotReceive('save');

        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $sR = $this->makeSettingRepository();

        $handler = new CheckoutComWebhookHandler(
            $this->makePaymentService($mock),
            new CheckoutComSignatureService(),
            $sR,
            $iR,
            $iaR,
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
        );

        $rawBody = json_encode(
            ['type' => 'payment_captured', 'data' => ['id' => 'pay_123', 'reference' => 'abc123']],
            JSON_THROW_ON_ERROR,
        );
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleAcknowledgesWithOkWhenInvoiceUrlKeyIsNotFound(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 'pay_123', 'status' => 'Captured'], JSON_THROW_ON_ERROR)),
        ]);

        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('unknown-key')->andReturn(null);

        $iaR = m::mock(InvAmountRepository::class);
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $sR = $this->makeSettingRepository();

        $handler = new CheckoutComWebhookHandler(
            $this->makePaymentService($mock),
            new CheckoutComSignatureService(),
            $sR,
            $iR,
            $iaR,
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
        );

        $rawBody = json_encode(
            ['type' => 'payment_captured', 'data' => ['id' => 'pay_123', 'reference' => 'unknown-key']],
            JSON_THROW_ON_ERROR,
        );
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleDoesNothingWhenReCheckDoesNotConfirmCaptured(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 'pay_123', 'status' => 'Authorized'], JSON_THROW_ON_ERROR)),
        ]);

        $iR = m::mock(InvRepository::class);
        $iR->shouldNotReceive('repoUrlKeyGuestLoaded');

        $iaR = m::mock(InvAmountRepository::class);
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $sR = $this->makeSettingRepository();

        $handler = new CheckoutComWebhookHandler(
            $this->makePaymentService($mock),
            new CheckoutComSignatureService(),
            $sR,
            $iR,
            $iaR,
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
        );

        $rawBody = json_encode(
            ['type' => 'payment_captured', 'data' => ['id' => 'pay_123', 'reference' => 'abc123']],
            JSON_THROW_ON_ERROR,
        );
        $response = $handler->handle($this->makeRequest($rawBody, $this->sign($rawBody)));

        Assert::same(200, $response->getStatusCode());
    }
}
