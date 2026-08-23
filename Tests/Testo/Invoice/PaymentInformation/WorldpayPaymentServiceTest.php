<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\WorldpayPaymentService;
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
 * Covers WorldpayPaymentService against a mocked Guzzle handler — no
 * real network calls. Request/response shapes are ground-truthed
 * against the real, downloaded Worldpay Payments API OpenAPI spec
 * (`worldpay-payments-full.yaml`, confirmed during research) — see the
 * class's own docblock. `refund()`'s state-dependent HATEOAS discovery
 * (GET first to find whether a refund action currently exists) is the
 * behaviour most worth covering here, since it's genuinely different
 * from every other gateway's fixed-URL refund() in this codebase.
 */
#[Test]
final class WorldpayPaymentServiceTest
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

    private function makeSettingRepository(bool $sandbox = true): SettingRepository&m\MockInterface
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_worldpay_username')->andReturn('enc-username');
        $sR->shouldReceive('decode')->with('enc-username')->andReturn('wp_user');
        $sR->shouldReceive('getSetting')->with('gateway_worldpay_password')->andReturn('enc-password');
        $sR->shouldReceive('decode')->with('enc-password')->andReturn('wp_pass');
        $sR->shouldReceive('getSetting')->with('gateway_worldpay_entity')->andReturn('default');
        $sandboxSetting = $sandbox ? '1' : '0';
        $sR->shouldReceive('getSetting')->with('gateway_worldpay_sandbox')->andReturn($sandboxSetting);
        $sR->shouldReceive('getSetting')->with('gateway_worldpay_tradingName')->andReturn('Invoice Trading Name');

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeService(MockHandler $mock, bool $sandbox = true): WorldpayPaymentService
    {
        return new WorldpayPaymentService(
            $this->makeSettingRepository($sandbox),
            $this->makeLoggerInterfaceSpy(),
            $this->makeHttpClient($mock),
        );
    }

    public function getDriverKeyReturnsWorldpay(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::same('worldpay', $service->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenAllThreeCredentialsArePresent(): void
    {
        $service = $this->makeService(new MockHandler([]));

        Assert::true($service->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenEntityIsMissing(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_worldpay_username')->andReturn('enc-username');
        $sR->shouldReceive('decode')->with('enc-username')->andReturn('wp_user');
        $sR->shouldReceive('getSetting')->with('gateway_worldpay_password')->andReturn('enc-password');
        $sR->shouldReceive('decode')->with('enc-password')->andReturn('wp_pass');
        $sR->shouldReceive('getSetting')->with('gateway_worldpay_entity')->andReturn('');

        $service = new WorldpayPaymentService($sR, $this->makeLoggerInterfaceSpy(), $this->makeHttpClient(new MockHandler([])));

        Assert::false($service->isConfigured());
    }

    public function isSandboxReflectsTheSandboxSetting(): void
    {
        Assert::true($this->makeService(new MockHandler([]), true)->isSandbox());
        Assert::false($this->makeService(new MockHandler([]), false)->isSandbox());
    }

    public function createPaymentSendsBasicAuthAndTheConfirmedRequestShape(): void
    {
        $mock = new MockHandler([
            new Response(201, [], json_encode([
                'outcome' => 'authorized',
                'paymentId' => 'payI-123',
                'transactionReference' => 'inv-1-abc',
                '_links' => ['self' => ['href' => 'https://try.access.worldpay.com/api/payments/opaque']],
                '_actions' => [],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(
            transactionReference: 'inv-1-abc',
            sessionHref: 'https://try.access.worldpay.com/sessions/xyz',
            cardHolderName: 'Sherlock Holmes',
            billingAddress: ['address1' => '221B Baker Street', 'city' => 'London', 'postalCode' => 'NW1 6XE', 'countryCode' => 'GB'],
            amount: 42.50,
            currency: 'gbp',
            narrativeLine1: 'Invoice Trading Name',
            threeDsReturnUrl: 'https://invoice.example/worldpayComplete',
        );

        Assert::notNull($result);
        Assert::same('authorized', $result['outcome']);
        Assert::same('payI-123', $result['paymentId']);
        Assert::same('https://try.access.worldpay.com/api/payments/opaque', $result['selfHref']);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        Assert::same('Basic ' . base64_encode('wp_user:wp_pass'), $sentRequest->getHeaderLine('Authorization'));
        Assert::same('2024-06-01', $sentRequest->getHeaderLine('WP-Api-Version'));
        Assert::same('https://try.access.worldpay.com/api/payments', (string) $sentRequest->getUri());

        /** @var array{transactionReference: string, merchant: array{entity: string}, instruction: array{method: string, settlement: array{auto: bool}, paymentInstrument: array{type: string, sessionHref: string}, value: array{currency: string, amount: int}}} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('default', $body['merchant']['entity']);
        Assert::same('card', $body['instruction']['method']);
        Assert::true($body['instruction']['settlement']['auto']);
        // Bare "checkout", NOT "card/checkout" — confirmed from the real
        // card-payment-checkout example in the downloaded spec, distinct
        // from Verified Tokens' own (different, unrelated) discriminator.
        Assert::same('checkout', $body['instruction']['paymentInstrument']['type']);
        Assert::same('https://try.access.worldpay.com/sessions/xyz', $body['instruction']['paymentInstrument']['sessionHref']);
        Assert::same('GBP', $body['instruction']['value']['currency']);
        Assert::same(4250, $body['instruction']['value']['amount']);
    }

    public function createPaymentReturnsNullWhenTheHttpCallFails(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://try.access.worldpay.com/api/payments')),
        ]);
        $service = $this->makeService($mock);

        $result = $service->createPayment(
            transactionReference: 'inv-1-abc',
            sessionHref: 'https://try.access.worldpay.com/sessions/xyz',
            cardHolderName: 'Sherlock Holmes',
            billingAddress: ['address1' => '221B Baker Street', 'city' => 'London', 'postalCode' => 'NW1 6XE', 'countryCode' => 'GB'],
            amount: 10.0,
            currency: 'GBP',
            narrativeLine1: 'Invoice',
            threeDsReturnUrl: 'https://invoice.example/worldpayComplete',
        );

        Assert::null($result);
    }

    public function verifyPaymentReportsPaidForAnAuthorizedOutcome(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['outcome' => 'authorized'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->verifyPayment('https://try.access.worldpay.com/api/payments/opaque');

        Assert::true($result->paid);
        Assert::same('https://try.access.worldpay.com/api/payments/opaque', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        Assert::same('https://try.access.worldpay.com/api/payments/opaque', (string) $sentRequest->getUri());
    }

    public function verifyPaymentReportsNotPaidForARefusedOutcome(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['outcome' => 'refused'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('https://try.access.worldpay.com/api/payments/opaque')->paid);
    }

    public function verifyPaymentReturnsFalseImmediatelyForAnEmptyProviderReference(): void
    {
        $mock = new MockHandler([]);
        $service = $this->makeService($mock);

        Assert::false($service->verifyPayment('')->paid);
        Assert::null($mock->getLastRequest());
    }

    public function refundFetchesCurrentStateThenPostsToTheDiscoveredRefundAction(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'outcome' => 'sentForSettlement',
                '_actions' => ['refundPayment' => ['href' => 'https://try.access.worldpay.com/api/payments/opaque/refunds']],
            ], JSON_THROW_ON_ERROR)),
            new Response(202, [], json_encode(['outcome' => 'sentForRefund'], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('https://try.access.worldpay.com/api/payments/opaque', 20.0);

        Assert::true($result->refunded);

        $refundRequest = $mock->getLastRequest();
        Assert::notNull($refundRequest);
        Assert::same('https://try.access.worldpay.com/api/payments/opaque/refunds', (string) $refundRequest->getUri());
        /** @var array{value: array{amount: int}} $body */
        $body = json_decode((string) $refundRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same(2000, $body['value']['amount']);
    }

    public function refundFailsWithoutPostingWhenNoRefundActionIsPresentYet(): void
    {
        // Confirmed throughout research: a freshly-authorized-but-not-yet-
        // settled payment has no refund action at all, only
        // cancel/settle — this must not guess a refund URL.
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'outcome' => 'authorized',
                '_actions' => ['cancelPayment' => ['href' => 'https://try.access.worldpay.com/api/payments/opaque/cancellations']],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = $this->makeService($mock);

        $result = $service->refund('https://try.access.worldpay.com/api/payments/opaque', 20.0);

        Assert::false($result->refunded);
        // Only the initial GET happened — no refund POST was attempted
        // (the MockHandler queue had just one response; a second, unqueued
        // request would throw OutOfBoundsException rather than silently
        // succeed, so reaching this line at all already proves it).
        $lastRequest = $mock->getLastRequest();
        Assert::notNull($lastRequest);
        Assert::same('GET', $lastRequest->getMethod());
    }

    public function refundReturnsFalseImmediatelyForAnEmptyProviderReference(): void
    {
        $mock = new MockHandler([]);
        $service = $this->makeService($mock);

        Assert::false($service->refund('', 20.0)->refunded);
        Assert::null($mock->getLastRequest());
    }
}
