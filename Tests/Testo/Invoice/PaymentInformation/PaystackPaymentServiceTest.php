<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\PaystackPaymentService;
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
 * Covers PaystackPaymentService against a mocked Guzzle handler — no real
 * network calls. Base URL, Bearer-token auth header, endpoint paths, and
 * the `{status, message, data}` response envelope are all ground-truthed
 * against Paystack's `yabacon/paystack-php` SDK source — see the class's
 * own docblock. The /refund shape specifically is NOT independently
 * confirmed against primary docs (flagged there too).
 */
#[Test]
final class PaystackPaymentServiceTest
{
    private function makeSettingRepository(): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_paystack_secretKey')->andReturn('enc-secret-key');
        $sR->shouldReceive('decode')->with('enc-secret-key')->andReturn('sk_test_live123');
        $sR->shouldReceive('getSetting')->with('currency_code')->andReturn('NGN');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock): PaystackPaymentService
    {
        return new PaystackPaymentService(
            $this->makeSettingRepository(),
            m::spy(LoggerInterface::class),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsPaystack(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('paystack', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenSecretKeyIsPresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenSecretKeyIsMissing(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_paystack_secretKey')->andReturn('');
        $sR->shouldReceive('decode')->with('')->andReturn('');

        $service = new PaystackPaymentService($sR, m::spy(LoggerInterface::class), $this->makeHttpClient(new MockHandler([])));

        Assert::false($service->isConfigured());
    }

    public function createPaymentReturnsNullWhenEmailIsMissing(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::null($service->createPayment(99.5, '', 'https://invoice.example/return'));
    }

    public function createPaymentSendsBearerAuthAndSubunitAmountAndReturnsTheAuthorizationUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/abc123',
                    'access_code' => 'access-code-123',
                    'reference' => 'server-side-ignored-reference',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(99.5, 'client@example.com', 'https://invoice.example/return', ['invoiceUrlKey' => 'abc123']);

        Assert::notNull($result);
        Assert::same('https://checkout.paystack.com/abc123', $result['authorizationUrl']);
        Assert::true(str_starts_with($result['reference'], 'inv-'));

        $sentRequest = $mock->getLastRequest();
        Assert::same('Bearer sk_test_live123', $sentRequest->getHeaderLine('Authorization'));

        /** @var array{amount: int, email: string, currency: string, reference: string, callback_url: string, metadata: array{invoiceUrlKey: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same(9950, $body['amount']);
        Assert::same('client@example.com', $body['email']);
        Assert::same('NGN', $body['currency']);
        Assert::same('https://invoice.example/return', $body['callback_url']);
        Assert::same('abc123', $body['metadata']['invoiceUrlKey']);
        Assert::same($result['reference'], $body['reference']);
    }

    public function createPaymentReturnsNullWhenPaystackRejectsTheRequest(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => false, 'message' => 'Invalid key'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'client@example.com', 'https://invoice.example/return'));
    }

    public function createPaymentReturnsNullWhenResponseIsMissingAuthorizationUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => true, 'data' => []], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'client@example.com', 'https://invoice.example/return'));
    }

    public function createPaymentReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api.paystack.co/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'client@example.com', 'https://invoice.example/return'));
    }

    public function verifyPaymentReportsPaidWhenStatusIsSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => true, 'data' => ['status' => 'success']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('inv-123');

        Assert::true($result->paid);
        Assert::same('inv-123', $result->providerReference);
    }

    public function verifyPaymentReportsNotPaidWhenStatusIsAbandoned(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => true, 'data' => ['status' => 'abandoned']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('inv-123')->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://api.paystack.co/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('inv-123')->paid);
    }

    public function refundReturnsRefundedTrueWhenStatusIsProcessed(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => true,
                'data' => ['status' => 'processed'],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('inv-123', 50.0);

        Assert::true($result->refunded);
        Assert::same('inv-123', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        /** @var array{transaction: string, amount: int} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('inv-123', $body['transaction']);
        Assert::same(5000, $body['amount']);
    }

    public function refundReturnsRefundedFalseWhenPaystackRejectsTheRequest(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['status' => false, 'message' => 'Transaction not found'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('inv-123', 50.0);

        Assert::false($result->refunded);
        Assert::same('Transaction not found', $result->message);
    }

    public function refundReturnsRefundedFalseWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api.paystack.co/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->refund('inv-123', 50.0)->refunded);
    }
}
