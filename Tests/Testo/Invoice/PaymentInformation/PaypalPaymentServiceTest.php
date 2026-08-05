<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\PaypalPaymentService;
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
 * Covers PaypalPaymentService against a mocked Guzzle handler — no real
 * network calls. Base URL, OAuth2 token flow, endpoint paths, and response
 * shapes are all ground-truthed against the official
 * `paypal/paypal-server-sdk` SDK source and PayPal's own current developer
 * docs — see the class's own docblock.
 *
 * Every public method fetches a fresh OAuth2 access token first (no
 * caching — see the class's own docblock), so every mock queue below
 * starts with a token response before the actual operation's response.
 */
#[Test]
final class PaypalPaymentServiceTest
{
    private function tokenResponse(): Response
    {
        return new Response(200, [], json_encode([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 32000,
        ], JSON_THROW_ON_ERROR));
    }

    private function makeSettingRepository(): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_paypal_clientId')->andReturn('test-client-id');
        $sR->shouldReceive('getSetting')->with('gateway_paypal_clientSecret')->andReturn('enc-client-secret');
        $sR->shouldReceive('decode')->with('enc-client-secret')->andReturn('plain-client-secret');
        $sR->shouldReceive('getSetting')->with('gateway_paypal_sandbox')->andReturn('1');
        $sR->shouldReceive('getSetting')->with('currency_code')->andReturn('USD');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock): PaypalPaymentService
    {
        return new PaypalPaymentService(
            $this->makeSettingRepository(),
            m::spy(LoggerInterface::class),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsPaypal(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('paypal', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenClientIdAndClientSecretArePresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenClientIdIsMissing(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_paypal_clientId')->andReturn('');
        $sR->shouldReceive('getSetting')->with('gateway_paypal_clientSecret')->andReturn('enc-client-secret');
        $sR->shouldReceive('decode')->with('enc-client-secret')->andReturn('plain-client-secret');

        $service = new PaypalPaymentService($sR, m::spy(LoggerInterface::class), $this->makeHttpClient(new MockHandler([])));

        Assert::false($service->isConfigured());
    }

    public function createPaymentSendsBearerTokenAndDecimalAmountAndReturnsTheApproveUrl(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(200, [], json_encode([
                'id' => 'ORDER123',
                'links' => [
                    ['rel' => 'self', 'href' => 'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER123'],
                    ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER123'],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(
            99.5,
            'Invoice #123',
            'https://invoice.example/complete',
            'https://invoice.example/complete',
            ['invoiceUrlKey' => 'abc123'],
        );

        Assert::notNull($result);
        Assert::same('ORDER123', $result['orderId']);
        Assert::same('https://www.sandbox.paypal.com/checkoutnow?token=ORDER123', $result['approveUrl']);

        $sentRequest = $mock->getLastRequest();
        Assert::same('Bearer test-access-token', $sentRequest->getHeaderLine('Authorization'));

        /** @var array{intent: string, purchase_units: list<array{amount: array{currency_code: string, value: string}, invoice_id: string, description: string}>, application_context: array{return_url: string, cancel_url: string, user_action: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('CAPTURE', $body['intent']);
        Assert::same('USD', $body['purchase_units'][0]['amount']['currency_code']);
        Assert::same('99.50', $body['purchase_units'][0]['amount']['value']);
        Assert::same('abc123', $body['purchase_units'][0]['invoice_id']);
        Assert::same('Invoice #123', $body['purchase_units'][0]['description']);
        Assert::same('https://invoice.example/complete', $body['application_context']['return_url']);
        Assert::same('PAY_NOW', $body['application_context']['user_action']);
    }

    public function createPaymentReturnsNullWhenTheAccessTokenRequestFails(): void
    {
        $mock = new MockHandler([new Response(401, [], json_encode(['error' => 'invalid_client'], JSON_THROW_ON_ERROR))]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/complete', 'https://invoice.example/complete'));
    }

    public function createPaymentReturnsNullWhenResponseIsMissingIdOrApproveLink(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(200, [], json_encode(['id' => 'ORDER123', 'links' => []], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/complete', 'https://invoice.example/complete'));
    }

    public function createPaymentReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api-m.sandbox.paypal.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->createPayment(10.0, 'test', 'https://invoice.example/complete', 'https://invoice.example/complete'));
    }

    public function captureOrderReturnsTheCaptureIdAndStatus(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(200, [], json_encode([
                'purchase_units' => [[
                    'payments' => ['captures' => [['id' => 'CAP123', 'status' => 'COMPLETED']]],
                ]],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->captureOrder('ORDER123');

        Assert::notNull($result);
        Assert::same('CAP123', $result['captureId']);
        Assert::same('COMPLETED', $result['status']);
    }

    public function captureOrderReturnsNullWhenResponseIsMissingACapture(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(422, [], json_encode(['name' => 'UNPROCESSABLE_ENTITY'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::null($service->captureOrder('ORDER123'));
    }

    public function verifyPaymentReportsPaidWhenStatusIsCompleted(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(200, [], json_encode(['id' => 'CAP123', 'status' => 'COMPLETED'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('CAP123');

        Assert::true($result->paid);
        Assert::same('CAP123', $result->providerReference);
    }

    public function verifyPaymentReportsNotPaidWhenStatusIsPending(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(200, [], json_encode(['id' => 'CAP123', 'status' => 'PENDING'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('CAP123')->paid);
    }

    public function verifyPaymentReportsNotPaidWhenTheAccessTokenRequestFails(): void
    {
        $mock = new MockHandler([new Response(401, [], json_encode(['error' => 'invalid_client'], JSON_THROW_ON_ERROR))]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('CAP123')->paid);
    }

    public function refundReturnsRefundedTrueWhenStatusIsCompletedAndSendsTheDecimalAmount(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(200, [], json_encode(['id' => 'REFUND1', 'status' => 'COMPLETED'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('CAP123', 50.0);

        Assert::true($result->refunded);
        Assert::same('REFUND1', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        /** @var array{amount: array{currency_code: string, value: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('USD', $body['amount']['currency_code']);
        Assert::same('50.00', $body['amount']['value']);
    }

    public function refundReturnsRefundedFalseWhenPaypalRejectsTheRequest(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(422, [], json_encode([
                'name' => 'UNPROCESSABLE_ENTITY',
                'message' => 'Capture has already been fully refunded',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('CAP123', 50.0);

        Assert::false($result->refunded);
        Assert::same('Capture has already been fully refunded', $result->message);
    }

    public function refundReturnsRefundedFalseWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://api-m.sandbox.paypal.com/')),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->refund('CAP123', 50.0)->refunded);
    }

    public function verifyWebhookSignatureReturnsTrueWhenVerificationStatusIsSuccess(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(200, [], json_encode(['verification_status' => 'SUCCESS'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyWebhookSignature(
            '{"event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            [
                'transmissionId' => 'tx-1',
                'transmissionTime' => '2026-08-06T00:00:00Z',
                'certUrl' => 'https://api.paypal.com/cert.pem',
                'authAlgo' => 'SHA256withRSA',
                'transmissionSig' => 'sig-value',
            ],
            'webhook-id-123',
        );

        Assert::true($result);

        $sentRequest = $mock->getLastRequest();
        /** @var array{transmission_id: string, transmission_time: string, cert_url: string, auth_algo: string, transmission_sig: string, webhook_id: string, webhook_event: array{event_type: string}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('tx-1', $body['transmission_id']);
        Assert::same('webhook-id-123', $body['webhook_id']);
        Assert::same('PAYMENT.CAPTURE.COMPLETED', $body['webhook_event']['event_type']);
    }

    public function verifyWebhookSignatureReturnsFalseWhenVerificationStatusIsFailure(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse(),
            new Response(200, [], json_encode(['verification_status' => 'FAILURE'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyWebhookSignature('{}', [], 'webhook-id-123'));
    }

    public function verifyWebhookSignatureReturnsFalseWhenTheAccessTokenRequestFails(): void
    {
        $mock = new MockHandler([new Response(401, [], json_encode(['error' => 'invalid_client'], JSON_THROW_ON_ERROR))]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyWebhookSignature('{}', [], 'webhook-id-123'));
    }
}
