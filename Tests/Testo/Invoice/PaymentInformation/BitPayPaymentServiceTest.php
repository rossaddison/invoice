<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\BitPayPaymentService;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Psr\Log\LoggerInterface;
use RossAddison\BitPayClient\Model\CreateInvoiceRequest;
use Testo\Assert;
use Testo\Test;

/**
 * Covers BitPayPaymentService against a mocked Guzzle handler (injected
 * straight through to `rossaddison/bitpay-client`'s own BitPayClient, whose
 * request paths/envelope/signature formula are separately covered by that
 * package's own test suite) — no real network calls.
 */
#[Test]
final class BitPayPaymentServiceTest
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

    private function makeSettingRepository(bool $sandbox = false): SettingRepository&m\MockInterface
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_bitpay_posToken')->andReturn('enc-pos-token');
        $sR->shouldReceive('decode')->with('enc-pos-token')->andReturn('pos-token-123');
        $sR->shouldReceive('getSetting')->with('gateway_bitpay_sandbox')->andReturn($sandbox ? '1' : '0');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock, bool $sandbox = false): BitPayPaymentService
    {
        return new BitPayPaymentService(
            $this->makeSettingRepository($sandbox),
            $this->makeLoggerInterfaceSpy(),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsBitpay(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('bitpay', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenPosTokenIsPresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenPosTokenIsMissing(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_bitpay_posToken')->andReturn('');
        $sR->shouldReceive('decode')->with('')->andReturn('');

        $service = new BitPayPaymentService($sR, $this->makeLoggerInterfaceSpy(), $this->makeHttpClient(new MockHandler([])));

        Assert::false($service->isConfigured());
    }

    public function isSandboxReflectsTheSandboxSetting(): void
    {
        $service = $this->makeService(new MockHandler([]), sandbox: true);

        Assert::true($service->isSandbox());
    }

    public function createPaymentSendsTheTokenAndReturnsTheInvoiceIdAndUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 'inv-123',
                    'url' => 'https://bitpay.com/invoice?id=inv-123',
                    'status' => 'new',
                    'price' => 59.40,
                    'currency' => 'GBP',
                    'orderId' => 'abc123',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(
            59.40,
            'gbp',
            'abc123',
            'https://invoice.example/return',
            'https://invoice.example/webhook',
            'client@example.com',
        );

        Assert::notNull($result);
        Assert::same('inv-123', $result['invoiceId']);
        Assert::same('https://bitpay.com/invoice?id=inv-123', $result['url']);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        /** @var array{token: string, price: float, currency: string, orderId: string, redirectURL: string, notificationURL: string, buyerEmail: string} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('pos-token-123', $body['token']);
        // strtoupper()'d before being handed to CreateInvoiceRequest.
        Assert::same('GBP', $body['currency']);
        Assert::same('abc123', $body['orderId']);
        // BitPay's own OpenAPI reference confirms these field names use
        // uppercase "URL", not camelCase "Url" — verified directly, not
        // assumed, after this test first caught the mismatch.
        Assert::same('https://invoice.example/return', $body['redirectURL']);
        Assert::same('https://invoice.example/webhook', $body['notificationURL']);
        Assert::same('client@example.com', $body['buyerEmail']);
    }

    public function createPaymentOmitsBuyerEmailWhenNotProvided(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 'inv-123',
                    'url' => 'https://bitpay.com/invoice?id=inv-123',
                    'status' => 'new',
                    'price' => 10.0,
                    'currency' => 'GBP',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $service->createPayment(10.0, 'GBP', 'abc123', 'https://invoice.example/return', 'https://invoice.example/webhook');

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::false(array_key_exists('buyerEmail', $body));
    }

    public function createPaymentReturnsNullWhenBitPayRejectsTheRequest(): void
    {
        $mock = new MockHandler([
            new Response(422, [], json_encode(['error' => ['message' => 'invalid currency']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'XXX', 'abc123', 'https://x/return', 'https://x/webhook'));
    }

    public function createPaymentReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://bitpay.com/invoices')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'GBP', 'abc123', 'https://x/return', 'https://x/webhook'));
    }

    public function verifyPaymentReportsPaidWhenStatusIsComplete(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => ['id' => 'inv-123', 'url' => 'https://bitpay.com/i', 'status' => 'complete', 'price' => 10.0, 'currency' => 'GBP'],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('inv-123');

        Assert::true($result->paid);
        Assert::same('inv-123', $result->providerReference);
        Assert::same('complete', $result->message);
    }

    public function verifyPaymentReportsNotPaidWhenStatusIsConfirmedButNotYetComplete(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => ['id' => 'inv-123', 'url' => 'https://bitpay.com/i', 'status' => 'confirmed', 'price' => 10.0, 'currency' => 'GBP'],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('inv-123')->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://bitpay.com/invoices/inv-123')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('inv-123')->paid);
    }

    public function refundAlwaysReportsUnsupported(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $result = $service->refund('inv-123', 50.0);

        Assert::false($result->refunded);
        Assert::same('inv-123', $result->providerReference);
        Assert::true(str_contains($result->message, 'merchant facade'));
    }

    public function verifyWebhookSignatureAcceptsASignatureComputedWithTheSameToken(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $body = json_encode(['id' => 'inv-123', 'status' => 'complete'], JSON_THROW_ON_ERROR);
        $canonical = json_encode(
            json_decode($body, true, 512, JSON_THROW_ON_ERROR),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        $signature = base64_encode(hash_hmac('sha256', (string) $canonical, 'pos-token-123', true));

        Assert::true($service->verifyWebhookSignature($body, $signature));
    }

    public function verifyWebhookSignatureRejectsAWrongSignature(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::false($service->verifyWebhookSignature('{"id":"inv-123"}', 'not-the-right-signature'));
    }
}
