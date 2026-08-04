<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\YookassaPaymentService;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers YookassaPaymentService against a mocked Guzzle handler — no real
 * network calls. Base URL, Basic Auth header, endpoint paths, and
 * status/response shapes are all ground-truthed against YooKassa's official
 * (archived) `yoomoney/yookassa-sdk-php` SDK source — see the class's own
 * docblock.
 */
#[Test]
final class YookassaPaymentServiceTest
{
    private function makeSettingRepository(): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_yookassa_shopId')->andReturn('demo-shop-id');
        $sR->shouldReceive('getSetting')->with('gateway_yookassa_secretKey')->andReturn('enc-secret-key');
        $sR->shouldReceive('decode')->with('enc-secret-key')->andReturn('live_secretkey123');
        $sR->shouldReceive('getSetting')->with('currency_code')->andReturn('RUB');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock): YookassaPaymentService
    {
        return new YookassaPaymentService(
            $this->makeSettingRepository(),
            m::spy(LoggerInterface::class),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsYookassa(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('yookassa', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenShopIdAndSecretKeyArePresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenShopIdIsMissing(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_yookassa_shopId')->andReturn('');
        $sR->shouldReceive('getSetting')->with('gateway_yookassa_secretKey')->andReturn('enc-secret-key');
        $sR->shouldReceive('decode')->with('enc-secret-key')->andReturn('live_secretkey123');

        $service = new YookassaPaymentService($sR, m::spy(LoggerInterface::class), $this->makeHttpClient(new MockHandler([])));

        Assert::false($service->isConfigured());
    }

    public function createPaymentSendsBasicAuthAndIdempotenceKeyAndReturnsTheConfirmationUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'payment-uuid-123',
                'status' => 'pending',
                'confirmation' => ['type' => 'redirect', 'confirmation_url' => 'https://yookassa.ru/checkout/payments/v2/contract?orderId=payment-uuid-123'],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(99.5, 'Invoice #123', 'https://invoice.example/return', ['invoiceUrlKey' => 'abc123']);

        Assert::notNull($result);
        Assert::same('payment-uuid-123', $result['paymentId']);
        Assert::same('https://yookassa.ru/checkout/payments/v2/contract?orderId=payment-uuid-123', $result['confirmationUrl']);

        $sentRequest = $mock->getLastRequest();
        Assert::same('Basic ' . base64_encode('demo-shop-id:live_secretkey123'), $sentRequest->getHeaderLine('Authorization'));
        Assert::notSame('', $sentRequest->getHeaderLine('Idempotence-Key'));

        /** @var array{amount: array{value: string, currency: string}, confirmation: array{return_url: string}, metadata: array{invoiceUrlKey: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('99.50', $body['amount']['value']);
        Assert::same('RUB', $body['amount']['currency']);
        Assert::same('https://invoice.example/return', $body['confirmation']['return_url']);
        Assert::same('abc123', $body['metadata']['invoiceUrlKey']);
    }

    public function createPaymentReturnsNullWhenResponseIsMissingIdOrConfirmationUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'pending'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/return'));
    }

    public function createPaymentReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api.yookassa.ru/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/return'));
    }

    public function verifyPaymentReportsPaidWhenStatusIsSucceeded(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'payment-uuid-123', 'status' => 'succeeded'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('payment-uuid-123');

        Assert::true($result->paid);
        Assert::same('payment-uuid-123', $result->providerReference);
    }

    public function verifyPaymentReportsNotPaidWhenStatusIsPending(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'payment-uuid-123', 'status' => 'pending'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('payment-uuid-123');

        Assert::false($result->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://api.yookassa.ru/')),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('payment-uuid-123');

        Assert::false($result->paid);
    }

    public function refundReturnsRefundedTrueWhenStatusIsSucceeded(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'refund-uuid-1', 'status' => 'succeeded'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('payment-uuid-123', 50.0);

        Assert::true($result->refunded);
        Assert::same('refund-uuid-1', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        /** @var array{payment_id: string, amount: array{value: string, currency: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('payment-uuid-123', $body['payment_id']);
        Assert::same('50.00', $body['amount']['value']);
    }

    public function refundReturnsRefundedFalseWhenTheApiRejectsTheRequest(): void
    {
        // Exact top-level {type, id, code, description, parameter} shape per
        // the SDK's own BadApiRequestException — the real parsing logic for
        // a 400 response, not a guess (see YookassaPaymentService's docblock).
        $mock = new MockHandler([
            new Response(400, [], json_encode([
                'type' => 'error',
                'id' => 'error-uuid-123',
                'code' => 'invalid_request',
                'description' => 'Payment has already been refunded',
                'parameter' => 'payment_id',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('payment-uuid-123', 50.0);

        Assert::false($result->refunded);
        Assert::same('Payment has already been refunded', $result->message);
    }

    public function refundReturnsRefundedFalseWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api.yookassa.ru/')),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('payment-uuid-123', 50.0);

        Assert::false($result->refunded);
    }
}
