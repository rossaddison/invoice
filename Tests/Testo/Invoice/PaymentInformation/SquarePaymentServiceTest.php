<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\SquarePaymentService;
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
 * Covers SquarePaymentService against a mocked Guzzle handler — no real
 * network calls. Base URL, Bearer + Square-Version auth headers, endpoint
 * paths, and response shapes are all ground-truthed against the official
 * `square/square-php-sdk` SDK source — see the class's own docblock.
 */
#[Test]
final class SquarePaymentServiceTest
{
    /**
     * @return LoggerInterface&m\MockInterface
     */
    private function makeLoggerInterfaceSpy(): LoggerInterface
    {
        /** @var LoggerInterface&m\MockInterface $mock */
        $mock = m::spy(LoggerInterface::class);
        return $mock;
    }
    private function makeSettingRepository(): SettingRepository&m\MockInterface
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_square_accessToken')->andReturn('enc-access-token');
        $sR->shouldReceive('decode')->with('enc-access-token')->andReturn('plain-access-token');
        $sR->shouldReceive('getSetting')->with('gateway_square_locationId')->andReturn('L123LOCATION');
        $sR->shouldReceive('getSetting')->with('gateway_square_sandbox')->andReturn('1');
        $sR->shouldReceive('getSetting')->with('currency_code')->andReturn('USD');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock): SquarePaymentService
    {
        return new SquarePaymentService(
            $this->makeSettingRepository(),
            $this->makeLoggerInterfaceSpy(),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsSquare(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('square', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenAccessTokenAndLocationIdArePresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenLocationIdIsMissing(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_square_accessToken')->andReturn('enc-access-token');
        $sR->shouldReceive('decode')->with('enc-access-token')->andReturn('plain-access-token');
        $sR->shouldReceive('getSetting')->with('gateway_square_locationId')->andReturn('');

        $service = new SquarePaymentService($sR, $this->makeLoggerInterfaceSpy(), $this->makeHttpClient(new MockHandler([])));

        Assert::false($service->isConfigured());
    }

    public function createPaymentSendsBearerAndSquareVersionHeadersAndPromotesInvoiceUrlKeyToOrderReferenceId(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'payment_link' => [
                    'id' => 'plink_abc123',
                    'url' => 'https://square.link/u/abc123',
                    'order_id' => 'order_xyz789',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(99.5, 'Invoice #123', 'https://invoice.example/complete', ['invoiceUrlKey' => 'abc123']);

        Assert::notNull($result);
        Assert::same('plink_abc123', $result['paymentLinkId']);
        Assert::same('https://square.link/u/abc123', $result['paymentLinkUrl']);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        Assert::same('Bearer plain-access-token', $sentRequest->getHeaderLine('Authorization'));
        Assert::notSame('', $sentRequest->getHeaderLine('Square-Version'));

        /** @var array{description: string, order: array{location_id: string, reference_id: string, line_items: list<array{name: string, quantity: string, base_price_money: array{amount: int, currency: string}}>}, checkout_options: array{redirect_url: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('L123LOCATION', $body['order']['location_id']);
        Assert::same('abc123', $body['order']['reference_id']);
        Assert::same(9950, $body['order']['line_items'][0]['base_price_money']['amount']);
        Assert::same('USD', $body['order']['line_items'][0]['base_price_money']['currency']);
        Assert::same('1', $body['order']['line_items'][0]['quantity']);
        Assert::same('https://invoice.example/complete', $body['checkout_options']['redirect_url']);
    }

    public function createPaymentReturnsNullWhenResponseIsMissingIdOrUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['payment_link' => ['id' => 'plink_abc123']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/complete'));
    }

    public function createPaymentReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://connect.squareupsandbox.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/complete'));
    }

    public function verifyPaymentReportsPaidWhenStatusIsCompleted(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['payment' => ['id' => 'pay_1', 'status' => 'COMPLETED']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('pay_1');

        Assert::true($result->paid);
        Assert::same('pay_1', $result->providerReference);
    }

    public function verifyPaymentReportsNotPaidWhenStatusIsApproved(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['payment' => ['id' => 'pay_1', 'status' => 'APPROVED']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('pay_1')->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://connect.squareupsandbox.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('pay_1')->paid);
    }

    public function getOrderReferenceIdReturnsTheReferenceIdWhenPresent(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['order' => ['reference_id' => 'abc123']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::same('abc123', $service->getOrderReferenceId('order_xyz789'));
    }

    public function getOrderReferenceIdReturnsNullWhenAbsent(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['order' => []], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->getOrderReferenceId('order_xyz789'));
    }

    public function getOrderReferenceIdReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://connect.squareupsandbox.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->getOrderReferenceId('order_xyz789'));
    }

    public function refundReturnsRefundedTrueWhenStatusIsCompleted(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['refund' => ['id' => 'refund_1', 'status' => 'COMPLETED']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('pay_1', 50.0);

        Assert::true($result->refunded);
        Assert::same('refund_1', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        /** @var array{payment_id: string, amount_money: array{amount: int, currency: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('pay_1', $body['payment_id']);
        Assert::same(5000, $body['amount_money']['amount']);
    }

    public function refundReturnsRefundedFalseWhenSquareRejectsTheRequest(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['errors' => [['detail' => 'Refund amount exceeds the payment amount.']]], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('pay_1', 999.0);

        Assert::false($result->refunded);
        Assert::same('Refund amount exceeds the payment amount.', $result->message);
    }

    public function refundReturnsRefundedFalseWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://connect.squareupsandbox.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->refund('pay_1', 50.0)->refunded);
    }
}
