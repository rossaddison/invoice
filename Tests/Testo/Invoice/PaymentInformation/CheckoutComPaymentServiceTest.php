<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\CheckoutComPaymentService;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CheckoutComPaymentService against a mocked Guzzle handler — no
 * real network calls. Every endpoint/field/auth detail is ground-truthed
 * against the official `checkout/checkout-sdk-php` package's own
 * executable source — see the class's own docblock. A real Guzzle client
 * backed by a MockHandler is injected via the service's own test-only
 * constructor parameter, wrapped internally in the SDK's
 * HttpClientBuilderInterface — this exercises the SDK's real request
 * construction/response parsing, not a guess at its shape.
 *
 * A valid-format test secret key is required here specifically because
 * the SDK's own builder validates the key against its real regex
 * (`/^sk_(sbox_)?[a-z2-7]{26}[a-z2-7*#$=]$/`) before ever making a
 * request — an arbitrary string would throw CheckoutArgumentException
 * before the mocked HTTP layer is ever reached.
 */
#[Test]
final class CheckoutComPaymentServiceTest
{
    private const string VALID_SECRET_KEY = 'sk_sbox_abcdefghijklmnopqrstuvwxyza';

    private function makeSettingRepository(bool $sandbox = true): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_secretKey')->andReturn('enc-secret');
        $sR->shouldReceive('decode')->with('enc-secret')->andReturn(self::VALID_SECRET_KEY);
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_publicKey')->andReturn('');
        $sR->shouldReceive('decode')->with('')->andReturn('');
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_processingChannelId')->andReturn('');
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_sandbox')->andReturn($sandbox ? '1' : '0');
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_environmentSubdomain')->andReturn('');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock, bool $sandbox = true): CheckoutComPaymentService
    {
        return new CheckoutComPaymentService(
            $this->makeSettingRepository($sandbox),
            m::spy(LoggerInterface::class),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsCheckoutCom(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('checkout_com', $service->getDriverKey());
    }

    public function isConfiguredIsFalseWithoutASecretKey(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_secretKey')->andReturn('');
        $sR->shouldReceive('decode')->with('')->andReturn('');

        $service = new CheckoutComPaymentService($sR, m::spy(LoggerInterface::class));

        Assert::false($service->isConfigured());
    }

    public function isConfiguredIsTrueWithASecretKey(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function createPaymentLinkReturnsTheRedirectUrlOnSuccess(): void
    {
        $mock = new MockHandler([
            new Response(201, [], json_encode([
                '_links' => [
                    'self' => ['href' => 'https://api.sandbox.checkout.com/payment-links/pl_abc123'],
                    'redirect' => ['href' => 'https://pay.sandbox.checkout.com/link/pl_abc123'],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $service = $this->makeService($mock);

        $result = $service->createPaymentLink(
            10.00,
            'gbp',
            'invoice-url-key-123',
            'Invoice INV125',
            'https://yii3i.online/paymentinformation/checkoutComComplete/invoice-url-key-123',
        );

        Assert::same('https://pay.sandbox.checkout.com/link/pl_abc123', $result);
    }

    public function createPaymentLinkReturnsNullWhenResponseIsMissingTheRedirectUrl(): void
    {
        $mock = new MockHandler([
            new Response(201, [], json_encode(['_links' => []], JSON_THROW_ON_ERROR)),
        ]);

        $service = $this->makeService($mock);

        $result = $service->createPaymentLink(10.00, 'gbp', 'key', 'Invoice INV1', 'https://example.test/complete');

        Assert::null($result);
    }

    public function createPaymentLinkReturnsNullOnApiError(): void
    {
        $mock = new MockHandler([
            new Response(422, [], json_encode([
                'request_id' => 'req_123',
                'error_type' => 'request_invalid',
                'error_codes' => ['amount_required'],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $service = $this->makeService($mock);

        $result = $service->createPaymentLink(10.00, 'gbp', 'key', 'Invoice INV1', 'https://example.test/complete');

        Assert::null($result);
    }

    /**
     * Confirmed live against a real sandbox account: Payment Link
     * creation fails with a 422 `processing_channel_id_required` API
     * error unless this is set — see checkoutComGatewayFields()'s own
     * docblock. Asserts it actually reaches the outgoing request body
     * when configured, via Guzzle's own history middleware rather than
     * just checking the (mocked) response.
     */
    public function createPaymentLinkIncludesProcessingChannelIdWhenConfigured(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_secretKey')->andReturn('enc-secret');
        $sR->shouldReceive('decode')->with('enc-secret')->andReturn(self::VALID_SECRET_KEY);
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_publicKey')->andReturn('');
        $sR->shouldReceive('decode')->with('')->andReturn('');
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_processingChannelId')->andReturn('enc-channel');
        $sR->shouldReceive('decode')->with('enc-channel')->andReturn('pc_abc123');
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_sandbox')->andReturn('1');
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_environmentSubdomain')->andReturn('');

        $history = [];
        $mock = new MockHandler([
            new Response(201, [], json_encode([
                '_links' => ['redirect' => ['href' => 'https://pay.sandbox.checkout.com/link/pl_abc123']],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));
        $httpClient = new HttpClient(['handler' => $handlerStack]);

        $service = new CheckoutComPaymentService($sR, m::spy(LoggerInterface::class), $httpClient);

        $service->createPaymentLink(10.00, 'gbp', 'key', 'Invoice INV1', 'https://example.test/complete');

        Assert::same(1, count($history));
        /** @var array{request: \Psr\Http\Message\RequestInterface} $entry */
        $entry = $history[0];
        /** @var array{processing_channel_id?: string} $sentBody */
        $sentBody = json_decode((string) $entry['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('pc_abc123', $sentBody['processing_channel_id'] ?? null);
    }

    public function verifyPaymentReturnsPaidTrueWhenStatusIsCaptured(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'pay_123', 'status' => 'Captured'], JSON_THROW_ON_ERROR)),
        ]);

        $service = $this->makeService($mock);

        $result = $service->verifyPayment('pay_123');

        Assert::true($result->paid);
        Assert::same('pay_123', $result->providerReference);
        Assert::same('Captured', $result->message);
    }

    public function verifyPaymentReturnsPaidFalseWhenStatusIsNotCaptured(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'pay_123', 'status' => 'Authorized'], JSON_THROW_ON_ERROR)),
        ]);

        $service = $this->makeService($mock);

        $result = $service->verifyPayment('pay_123');

        Assert::false($result->paid);
        Assert::same('Authorized', $result->message);
    }

    public function verifyPaymentReturnsPaidFalseOnApiError(): void
    {
        $mock = new MockHandler([
            new Response(404, [], json_encode(['error_type' => 'payment_not_found'], JSON_THROW_ON_ERROR)),
        ]);

        $service = $this->makeService($mock);

        $result = $service->verifyPayment('unknown_id');

        Assert::false($result->paid);
    }

    public function refundReturnsRefundedTrueOnSuccess(): void
    {
        $mock = new MockHandler([
            new Response(202, [], json_encode([
                'action_id' => 'act_refund123',
                'reference' => null,
            ], JSON_THROW_ON_ERROR)),
        ]);

        $service = $this->makeService($mock);

        $result = $service->refund('pay_123', 10.00);

        Assert::true($result->refunded);
        Assert::same('act_refund123', $result->providerReference);
    }

    public function refundReturnsRefundedFalseOnApiError(): void
    {
        $mock = new MockHandler([
            new Response(422, [], json_encode([
                'error_type' => 'request_invalid',
                'error_codes' => ['amount_exceeds_balance'],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $service = $this->makeService($mock);

        $result = $service->refund('pay_123', 10.00);

        Assert::false($result->refunded);
        Assert::same('pay_123', $result->providerReference);
    }

    public function webhookSigningKeyReturnsTheDecodedWebhookSecret(): void
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_checkout_com_webhookSecret')->andReturn('enc-webhook-secret');
        $sR->shouldReceive('decode')->with('enc-webhook-secret')->andReturn('plain-webhook-signing-key');

        $service = new CheckoutComPaymentService($sR, m::spy(LoggerInterface::class));

        Assert::same('plain-webhook-signing-key', $service->webhookSigningKey());
    }
}
