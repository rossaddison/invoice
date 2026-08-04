<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\RobokassaPaymentService;
use App\Invoice\PaymentInformation\Service\RobokassaSignatureService;
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
 * Covers RobokassaPaymentService against a mocked Guzzle handler — no real
 * network calls. Response shapes and the verifyPayment()/verifyResultUrlCallback()
 * formulas all match Robokassa's official OpenAPI specification
 * (https://docs.robokassa.ru/openapi/robokassa.yaml) — see the class's own
 * docblock.
 */
#[Test]
final class RobokassaPaymentServiceTest
{
    private function makeSettingRepository(): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_robokassa_login')->andReturn('demo-login');
        $sR->shouldReceive('getSetting')->with('gateway_robokassa_password1')->andReturn('enc-password1');
        $sR->shouldReceive('decode')->with('enc-password1')->andReturn('password1');
        $sR->shouldReceive('getSetting')->with('gateway_robokassa_password2')->andReturn('enc-password2');
        $sR->shouldReceive('decode')->with('enc-password2')->andReturn('password2');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock): RobokassaPaymentService
    {
        return new RobokassaPaymentService(
            $this->makeSettingRepository(),
            new RobokassaSignatureService(),
            m::spy(LoggerInterface::class),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsRobokassa(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('robokassa', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenLoginAndBothPasswordsArePresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenAPasswordIsMissing(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_robokassa_login')->andReturn('demo-login');
        $sR->shouldReceive('getSetting')->with('gateway_robokassa_password1')->andReturn('');
        $sR->shouldReceive('decode')->with('')->andReturn('');
        $sR->shouldReceive('getSetting')->with('gateway_robokassa_password2')->andReturn('enc-password2');
        $sR->shouldReceive('decode')->with('enc-password2')->andReturn('password2');

        $service = new RobokassaPaymentService(
            $sR,
            new RobokassaSignatureService(),
            m::spy(LoggerInterface::class),
            $this->makeHttpClient(new MockHandler([])),
        );

        Assert::false($service->isConfigured());
    }

    public function createPaymentUrlReturnsTheUrlFromASuccessfulResponse(): void
    {
        // Exact InvoiceCreateSuccess shape per the OpenAPI spec: id, invId,
        // url, isSuccess: true.
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => '01234567-89ab-4cde-8f01-23456789abcd',
                'invId' => 123,
                'url' => 'https://auth.robokassa.ru/merchant/Invoice/AbCdEf',
                'isSuccess' => true,
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $url = $service->createPaymentUrl(123, 99.50, 'Invoice #123');

        Assert::same('https://auth.robokassa.ru/merchant/Invoice/AbCdEf', $url);
    }

    public function createPaymentUrlSignsTheJwtWithMerchantLoginAndPassword1(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['isSuccess' => true, 'id' => 'x', 'invId' => 1, 'url' => 'https://example.test/pay'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $service->createPaymentUrl(1, 10.0, 'test');

        $sentRequest = $mock->getLastRequest();
        /** @var string $jwt */
        $jwt = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $signature = explode('.', $jwt)[2];

        [, $expectedSignature] = (new RobokassaSignatureService())->signJwt(
            ['alg' => 'MD5', 'typ' => 'JWT'],
            ['MerchantLogin' => 'demo-login', 'InvoiceType' => 'OneTime', 'Culture' => 'en', 'InvId' => 1, 'OutSum' => 10.0, 'Description' => 'test'],
            'demo-login',
            'password1',
        );

        Assert::same($expectedSignature, $signature);
    }

    public function createPaymentUrlReturnsNullWhenTheApiReturnsAnApplicationError(): void
    {
        // Robokassa's Invoice API answers HTTP 200 even for application
        // errors, distinguished only by isSuccess — per InvoiceError schema.
        $mock = new MockHandler([
            new Response(200, [], json_encode(['isSuccess' => false, 'message' => 'Некорректно составлен запрос'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPaymentUrl(1, 10.0, 'test'));
    }

    public function createPaymentUrlReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://services.robokassa.ru/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPaymentUrl(1, 10.0, 'test'));
    }

    public function createPaymentUrlReturnsNullWhenTheResponseIsMalformedJson(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'not json'),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPaymentUrl(1, 10.0, 'test'));
    }

    public function verifyPaymentReportsPaidWhenStateCodeIs100(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '<OperationStateResponse><Result><Code>0</Code></Result><State><Code>100</Code></State></OperationStateResponse>'),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('789');

        Assert::true($result->paid);
        Assert::same('789', $result->providerReference);
    }

    public function verifyPaymentReportsNotPaidWhenStateCodeIsHold(): void
    {
        // 20 = HOLD per the spec's x-robokassa-state-descriptions — not a
        // final "paid" state.
        $mock = new MockHandler([
            new Response(200, [], '<OperationStateResponse><Result><Code>0</Code></Result><State><Code>20</Code></State></OperationStateResponse>'),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('789');

        Assert::false($result->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheOperationIsNotFound(): void
    {
        // Per the spec's operationNotFound example: Result.Code 3, no State
        // element at all.
        $mock = new MockHandler([
            new Response(200, [], '<OperationStateResponse><Result><Code>3</Code><Description>Not found</Description></Result></OperationStateResponse>'),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('789');

        Assert::false($result->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheResponseIsUnparsableXml(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'not xml'),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('789');

        Assert::false($result->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://auth.robokassa.ru/')),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('789');

        Assert::false($result->paid);
    }

    public function verifyResultUrlCallbackDelegatesToTheSignatureService(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $valid = hash('md5', '100.00:456:password2');

        Assert::true($service->verifyResultUrlCallback('100.00', '456', $valid));
        Assert::false($service->verifyResultUrlCallback('100.00', '456', 'wrong-signature'));
    }

    public function refundAlwaysReportsUnrefundedWithAMerchantDashboardMessage(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $result = $service->refund('ref-123', 50.0);

        Assert::false($result->refunded);
        Assert::same('ref-123', $result->providerReference);
        Assert::true(str_contains($result->message, 'merchant dashboard'));
    }
}
