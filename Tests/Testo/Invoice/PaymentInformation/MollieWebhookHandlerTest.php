<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentInformation\Service\MollieWebhookHandler;
use App\Invoice\PaymentInformation\Service\OnlinePaymentRecorderService;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Psr7\HttpFactory;
use Mollie\Api\Fake\MockMollieClient;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mockery as m;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactory;

/**
 * Covers MollieWebhookHandler against Mollie's own official test-fake
 * (`Mollie\Api\Fake\MockMollieClient`/`MockResponse`) rather than
 * hand-mocked Guzzle responses — this exercises the SDK's real JSON
 * hydration/`Payment` resource parsing, not a guess at its shape.
 * `GetPaymentRequest` is the request class actually issued by
 * `$mollieClient->payments->get()`, ground-truthed by reading the SDK's own
 * source directly — see MollieWebhookHandler's docblock.
 */
#[Test]
final class MollieWebhookHandlerTest
{
    private function makeDataResponseFactory(): DataResponseFactory
    {
        $httpFactory = new HttpFactory();
        return new DataResponseFactory($httpFactory);
    }

    /**
     * @param array<string, string> $parsedBody
     */
    private function makeRequest(array $parsedBody): ServerRequestInterface
    {
        return new HttpFactory()
            ->createServerRequest('POST', '/paymentinformation/mollieWebhook')
            ->withParsedBody($parsedBody);
    }

    private function makeSettingRepository(): SettingRepository&m\MockInterface
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_mollie_testOrLiveApiKey')->andReturn('enc-key');
        $sR->shouldReceive('decode')->with('enc-key')->andReturn('test_abcdefghijklmnopqrstuvwxyz1234567890');
        $sR->shouldReceive('sandboxUrlArray')->andReturn(['mollie' => 'https://my.mollie.com/dashboard/']);

        return $sR;
    }

    public function handleReturnsBadRequestWhenIdIsMissing(): void
    {
        $iR = m::mock(InvRepository::class);
        $iaR = m::mock(InvAmountRepository::class);
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $sR = m::mock(SettingRepository::class);

        $handler = new MollieWebhookHandler($iR, $iaR, $sR, $recorder, $this->makeDataResponseFactory(), m::spy(LoggerInterface::class));

        $response = $handler->handle($this->makeRequest([]));

        Assert::same(400, $response->getStatusCode());
    }

    public function handleAcknowledgesWithOkWhenGatewayNotConfigured(): void
    {
        $iR = m::mock(InvRepository::class);
        $iaR = m::mock(InvAmountRepository::class);
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_mollie_testOrLiveApiKey')->andReturn('');

        $handler = new MollieWebhookHandler($iR, $iaR, $sR, $recorder, $this->makeDataResponseFactory(), m::spy(LoggerInterface::class));

        $response = $handler->handle($this->makeRequest(['id' => 'tr_123']));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleMarksInvoicePaidWhenPaymentIsPaidAndInvoiceHasABalance(): void
    {
        $mollieClient = new MockMollieClient([
            GetPaymentRequest::class => MockResponse::ok([
                'id' => 'tr_123',
                'status' => 'paid',
                'amount' => ['currency' => 'GBP', 'value' => '99.50'],
                'metadata' => ['invoice_url_key' => 'abc123'],
                'createdAt' => '2026-08-05T12:00:00+00:00',
                'paidAt' => '2026-08-05T12:01:00+00:00',
            ]),
        ]);

        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('reqId')->andReturn(42);
        $invoice->shouldReceive('getNumber')->andReturn('INV-0001');
        $invoice->shouldReceive('setStatusId')->once()->with(4);
        $invoice->shouldReceive('setPaymentMethod')->once()->with(4);

        $invoiceAmountRecord = m::mock(InvAmount::class);
        $invoiceAmountRecord->shouldReceive('getBalance')->andReturn(99.50);
        $invoiceAmountRecord->shouldReceive('reqInvId')->andReturn(42);
        $invoiceAmountRecord->shouldReceive('getTotal')->andReturn(99.50);
        $invoiceAmountRecord->shouldReceive('setBalance')->once()->with(0);
        $invoiceAmountRecord->shouldReceive('setPaid')->once()->with(99.50);

        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('abc123')->andReturn($invoice);
        $iR->shouldReceive('save')->once()->with($invoice);

        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->once()->with(42)->andReturn($invoiceAmountRecord);
        $iaR->shouldReceive('save')->once()->with($invoiceAmountRecord);

        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldReceive('record')->once();

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
            $mollieClient,
        );

        $response = $handler->handle($this->makeRequest(['id' => 'tr_123']));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleDoesNotReRecordWhenTheInvoiceIsAlreadyPaid(): void
    {
        $mollieClient = new MockMollieClient([
            GetPaymentRequest::class => MockResponse::ok([
                'id' => 'tr_123',
                'status' => 'paid',
                'amount' => ['currency' => 'GBP', 'value' => '99.50'],
                'metadata' => ['invoice_url_key' => 'abc123'],
                'createdAt' => '2026-08-05T12:00:00+00:00',
                'paidAt' => '2026-08-05T12:01:00+00:00',
            ]),
        ]);

        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('reqId')->andReturn(42);
        $invoice->shouldNotReceive('setStatusId');
        $invoice->shouldNotReceive('setPaymentMethod');

        $invoiceAmountRecord = m::mock(InvAmount::class);
        // Already recorded — e.g. mollieComplete()'s own redirect-time
        // check got there first.
        $invoiceAmountRecord->shouldReceive('getBalance')->andReturn(0.00);
        $invoiceAmountRecord->shouldNotReceive('setBalance');
        $invoiceAmountRecord->shouldNotReceive('setPaid');

        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('abc123')->andReturn($invoice);
        $iR->shouldNotReceive('save');

        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->once()->with(42)->andReturn($invoiceAmountRecord);
        $iaR->shouldNotReceive('save');

        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
            $mollieClient,
        );

        $response = $handler->handle($this->makeRequest(['id' => 'tr_123']));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleDoesNothingWhenPaymentIsNotYetPaid(): void
    {
        $mollieClient = new MockMollieClient([
            GetPaymentRequest::class => MockResponse::ok([
                'id' => 'tr_123',
                'status' => 'open',
                'amount' => ['currency' => 'GBP', 'value' => '99.50'],
                'metadata' => ['invoice_url_key' => 'abc123'],
                'createdAt' => '2026-08-05T12:00:00+00:00',
            ]),
        ]);

        $iR = m::mock(InvRepository::class);
        $iR->shouldNotReceive('repoUrlKeyGuestLoaded');

        $iaR = m::mock(InvAmountRepository::class);
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
            $mollieClient,
        );

        $response = $handler->handle($this->makeRequest(['id' => 'tr_123']));

        Assert::same(200, $response->getStatusCode());
    }

    public function handleAcknowledgesWithOkWhenTheInvoiceUrlKeyIsNotFound(): void
    {
        $mollieClient = new MockMollieClient([
            GetPaymentRequest::class => MockResponse::ok([
                'id' => 'tr_123',
                'status' => 'paid',
                'amount' => ['currency' => 'GBP', 'value' => '99.50'],
                'metadata' => ['invoice_url_key' => 'unknown-key'],
                'createdAt' => '2026-08-05T12:00:00+00:00',
                'paidAt' => '2026-08-05T12:01:00+00:00',
            ]),
        ]);

        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('unknown-key')->andReturn(null);

        $iaR = m::mock(InvAmountRepository::class);
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $recorder,
            $this->makeDataResponseFactory(),
            m::spy(LoggerInterface::class),
            $mollieClient,
        );

        $response = $handler->handle($this->makeRequest(['id' => 'tr_123']));

        Assert::same(200, $response->getStatusCode());
    }
}
