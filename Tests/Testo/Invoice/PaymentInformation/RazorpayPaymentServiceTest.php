<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\RazorpayPaymentService;
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
 * Covers RazorpayPaymentService against a mocked Guzzle handler — no real
 * network calls. Base URL, HTTP Basic auth, endpoint paths, and response
 * shapes are all ground-truthed against the official `razorpay/razorpay-php`
 * SDK source and Razorpay's own current API docs — see the class's own
 * docblock.
 */
#[Test]
final class RazorpayPaymentServiceTest
{
    private function makeSettingRepository(): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_razorpay_keyId')->andReturn('rzp_test_abc123');
        $sR->shouldReceive('getSetting')->with('gateway_razorpay_keySecret')->andReturn('enc-key-secret');
        $sR->shouldReceive('decode')->with('enc-key-secret')->andReturn('plain_key_secret');
        $sR->shouldReceive('getSetting')->with('currency_code')->andReturn('INR');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock): RazorpayPaymentService
    {
        return new RazorpayPaymentService(
            $this->makeSettingRepository(),
            m::spy(LoggerInterface::class),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsRazorpay(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('razorpay', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenKeyIdAndKeySecretArePresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenKeyIdIsMissing(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_razorpay_keyId')->andReturn('');
        $sR->shouldReceive('getSetting')->with('gateway_razorpay_keySecret')->andReturn('enc-key-secret');
        $sR->shouldReceive('decode')->with('enc-key-secret')->andReturn('plain_key_secret');

        $service = new RazorpayPaymentService($sR, m::spy(LoggerInterface::class), $this->makeHttpClient(new MockHandler([])));

        Assert::false($service->isConfigured());
    }

    public function createPaymentSendsBasicAuthAndSubunitAmountAndPromotesInvoiceUrlKeyToReferenceId(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'plink_abc123',
                'short_url' => 'https://rzp.io/i/abc123',
                'status' => 'created',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(99.5, 'Invoice #123', 'https://invoice.example/return', ['invoiceUrlKey' => 'abc123']);

        Assert::notNull($result);
        Assert::same('plink_abc123', $result['paymentLinkId']);
        Assert::same('https://rzp.io/i/abc123', $result['paymentLinkUrl']);

        $sentRequest = $mock->getLastRequest();
        Assert::same('Basic ' . base64_encode('rzp_test_abc123:plain_key_secret'), $sentRequest->getHeaderLine('Authorization'));

        /** @var array{amount: int, currency: string, description: string, reference_id: string, callback_url: string, callback_method: string, notes: array{invoiceUrlKey: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same(9950, $body['amount']);
        Assert::same('INR', $body['currency']);
        Assert::same('Invoice #123', $body['description']);
        Assert::same('abc123', $body['reference_id']);
        Assert::same('https://invoice.example/return', $body['callback_url']);
        Assert::same('get', $body['callback_method']);
        Assert::same('abc123', $body['notes']['invoiceUrlKey']);
    }

    public function createPaymentReturnsNullWhenResponseIsMissingIdOrShortUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'created'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/return'));
    }

    public function createPaymentReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api.razorpay.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/return'));
    }

    public function verifyPaymentReportsPaidWhenStatusIsPaid(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'plink_abc123', 'status' => 'paid'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('plink_abc123');

        Assert::true($result->paid);
        Assert::same('plink_abc123', $result->providerReference);
    }

    public function verifyPaymentReportsNotPaidWhenStatusIsCreated(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'plink_abc123', 'status' => 'created'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('plink_abc123')->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://api.razorpay.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('plink_abc123')->paid);
    }

    public function refundReturnsRefundedTrueWhenStatusIsProcessedAndUsesPaymentIdInTheRequestBody(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'rfnd_1', 'status' => 'processed'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('pay_xyz789', 50.0);

        Assert::true($result->refunded);
        Assert::same('rfnd_1', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        /** @var array{payment_id: string, amount: int} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('pay_xyz789', $body['payment_id']);
        Assert::same(5000, $body['amount']);
    }

    public function refundReturnsRefundedFalseWhenRazorpayRejectsTheRequest(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['error' => ['description' => 'The payment has already been fully refunded']], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('pay_xyz789', 50.0);

        Assert::false($result->refunded);
        Assert::same('The payment has already been fully refunded', $result->message);
    }

    public function refundReturnsRefundedFalseWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api.razorpay.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->refund('pay_xyz789', 50.0)->refunded);
    }
}
