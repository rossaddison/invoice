<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\MercadoPagoPaymentService;
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
 * Covers MercadoPagoPaymentService against a mocked Guzzle handler — no
 * real network calls. Base URL, Bearer auth, endpoint paths, the
 * plain-decimal-float amount convention, and response shapes are all
 * ground-truthed against the official `mercadopago/sdk-php` SDK source —
 * see the class's own docblock.
 */
#[Test]
final class MercadoPagoPaymentServiceTest
{
    private function makeSettingRepository(bool $sandbox = false): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_mercado_pago_accessToken')->andReturn('enc-access-token');
        $sR->shouldReceive('decode')->with('enc-access-token')->andReturn('APP_USR-plain-access-token');
        $sR->shouldReceive('getSetting')->with('gateway_mercado_pago_sandbox')->andReturn($sandbox ? '1' : '0');
        $sR->shouldReceive('getSetting')->with('currency_code')->andReturn('BRL');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock, bool $sandbox = false): MercadoPagoPaymentService
    {
        return new MercadoPagoPaymentService(
            $this->makeSettingRepository($sandbox),
            m::spy(LoggerInterface::class),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsMercadoPago(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('mercado_pago', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenAccessTokenIsPresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenAccessTokenIsMissing(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_mercado_pago_accessToken')->andReturn('');
        $sR->shouldReceive('decode')->with('')->andReturn('');

        $service = new MercadoPagoPaymentService($sR, m::spy(LoggerInterface::class), $this->makeHttpClient(new MockHandler([])));

        Assert::false($service->isConfigured());
    }

    public function createPaymentSendsBearerAuthAndPlainFloatAmountAndPromotesInvoiceUrlKeyToExternalReference(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'pref_abc123',
                'init_point' => 'https://www.mercadopago.com/checkout/v1/redirect?pref_id=pref_abc123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/v1/redirect?pref_id=pref_abc123',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(
            99.5,
            'Invoice #123',
            'https://invoice.example/return',
            'https://invoice.example/webhook',
            ['invoiceUrlKey' => 'abc123'],
        );

        Assert::notNull($result);
        Assert::same('pref_abc123', $result['preferenceId']);
        Assert::same('https://www.mercadopago.com/checkout/v1/redirect?pref_id=pref_abc123', $result['checkoutUrl']);

        $sentRequest = $mock->getLastRequest();
        Assert::same('Bearer APP_USR-plain-access-token', $sentRequest->getHeaderLine('Authorization'));

        /** @var array{items: list<array{title: string, quantity: int, unit_price: float, currency_id: string}>, external_reference: string, back_urls: array{success: string, pending: string, failure: string}, notification_url: string, auto_return: string} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        // Plain decimal float, NOT (int) round($amount * 100) — Mercado
        // Pago doesn't use integer subunits, unlike Stripe/Razorpay/Square.
        Assert::same(99.5, $body['items'][0]['unit_price']);
        Assert::same(1, $body['items'][0]['quantity']);
        Assert::same('Invoice #123', $body['items'][0]['title']);
        Assert::same('BRL', $body['items'][0]['currency_id']);
        Assert::same('abc123', $body['external_reference']);
        Assert::same('https://invoice.example/return', $body['back_urls']['success']);
        Assert::same('https://invoice.example/return', $body['back_urls']['pending']);
        Assert::same('https://invoice.example/return', $body['back_urls']['failure']);
        Assert::same('https://invoice.example/webhook', $body['notification_url']);
        Assert::same('approved', $body['auto_return']);
    }

    public function createPaymentReturnsTheSandboxInitPointWhenSandboxIsEnabled(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'pref_abc123',
                'init_point' => 'https://www.mercadopago.com/live',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com/test',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock, sandbox: true);

        $result = $service->createPayment(10.0, 'test', 'https://invoice.example/return', 'https://invoice.example/webhook');

        Assert::notNull($result);
        Assert::same('https://sandbox.mercadopago.com/test', $result['checkoutUrl']);
    }

    public function createPaymentReturnsNullWhenResponseIsMissingIdOrCheckoutUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'pref_abc123'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/return', 'https://invoice.example/webhook'));
    }

    public function createPaymentReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api.mercadopago.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/return', 'https://invoice.example/webhook'));
    }

    public function verifyPaymentReportsPaidWhenStatusIsApproved(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'approved', 'external_reference' => 'abc123'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('pay_999');

        Assert::true($result->paid);
        Assert::same('pay_999', $result->providerReference);
    }

    public function verifyPaymentReportsNotPaidWhenStatusIsPending(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'pending'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('pay_999')->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://api.mercadopago.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('pay_999')->paid);
    }

    public function fetchPaymentDetailsReturnsBothStatusAndExternalReference(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'approved', 'external_reference' => 'abc123'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $details = $service->fetchPaymentDetails('pay_999');

        Assert::notNull($details);
        Assert::same('approved', $details['status']);
        Assert::same('abc123', $details['externalReference']);
    }

    public function fetchPaymentDetailsReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('GET', 'https://api.mercadopago.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->fetchPaymentDetails('pay_999'));
    }

    public function refundReturnsRefundedTrueWhenAnIdComesBackAndUsesPlainFloatAmountInTheRequestBody(): void
    {
        $mock = new MockHandler([
            new Response(201, [], json_encode(['id' => 111222, 'status' => 'approved'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('pay_999', 50.0);

        Assert::true($result->refunded);
        Assert::same('111222', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        Assert::same('/v1/payments/pay_999/refunds', $sentRequest->getUri()->getPath());
        /** @var array{amount: int|float} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        // json_encode(50.0) emits "50" (no trailing .0), so a whole-number
        // amount decodes back as an int, not a float — real API behavior,
        // not a bug; cast before comparing rather than asserting a type
        // JSON itself doesn't reliably preserve.
        Assert::same(50.0, (float) $body['amount']);
    }

    public function refundReturnsRefundedFalseWhenMercadoPagoRejectsTheRequest(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['error' => 'bad_request', 'message' => 'Refund amount exceeds payment amount'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('pay_999', 50.0);

        Assert::false($result->refunded);
        Assert::same('Refund amount exceeds payment amount', $result->message);
    }

    public function refundReturnsRefundedFalseWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api.mercadopago.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->refund('pay_999', 50.0)->refunded);
    }
}
