<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvPaymentSettlementService;
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
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_mollie_testOrLiveApiKey')->andReturn('enc-key');
        $sR->shouldReceive('decode')->with('enc-key')->andReturn('test_abcdefghijklmnopqrstuvwxyz1234567890');
        $sR->shouldReceive('sandboxUrlArray')->andReturn(['mollie' => 'https://my.mollie.com/dashboard/']);

        return $sR;
    }

    /**
     * @return InvPaymentSettlementService&m\MockInterface
     */
    private function makeUncalledInvPaymentSettlementService(): InvPaymentSettlementService
    {
        /** @var InvPaymentSettlementService&m\MockInterface $service */
        $service = m::mock(InvPaymentSettlementService::class);
        $service->shouldNotReceive('markInvoicePaidAndAdjustStock');
        return $service;
    }

    /**
     * @return LoggerInterface&m\MockInterface
     */
    private function makeLoggerSpy(): LoggerInterface
    {
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::spy(LoggerInterface::class);
        return $logger;
    }

    public function handleReturnsBadRequestWhenIdIsMissing(): void
    {
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $sR,
            $this->makeUncalledInvPaymentSettlementService(),
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
        );

        $response = $handler->handle($this->makeRequest([]));

        Assert::same(400, $response->getStatusCode());
    }

    public function handleAcknowledgesWithOkWhenGatewayNotConfigured(): void
    {
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_mollie_testOrLiveApiKey')->andReturn('');

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $sR,
            $this->makeUncalledInvPaymentSettlementService(),
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
        );

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

        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('reqId')->andReturn(42);
        $invoice->shouldReceive('getNumber')->andReturn('INV-0001');
        // setStatusId/setPaymentMethod/save and the InvAmount balance/paid
        // settlement are InvPaymentSettlementService's own responsibility
        // now (covered by InvPaymentSettlementServiceTest) — this test only
        // needs to confirm the handler hands off the right objects.

        /** @var InvAmount&m\MockInterface $invoiceAmountRecord */
        $invoiceAmountRecord = m::mock(InvAmount::class);
        $invoiceAmountRecord->shouldReceive('getBalance')->andReturn(99.50);
        $invoiceAmountRecord->shouldReceive('reqInvId')->andReturn(42);

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('abc123')->andReturn($invoice);

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->once()->with(42)->andReturn($invoiceAmountRecord);

        /** @var InvPaymentSettlementService&m\MockInterface $invPaymentSettlementService */
        $invPaymentSettlementService = m::mock(InvPaymentSettlementService::class);
        $invPaymentSettlementService->shouldReceive('markInvoicePaidAndAdjustStock')
            ->once()->with($invoice, $invoiceAmountRecord);

        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldReceive('record')->once();

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $invPaymentSettlementService,
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
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

        /** @var Inv&m\MockInterface $invoice */
        $invoice = m::mock(Inv::class);
        $invoice->shouldReceive('reqId')->andReturn(42);

        /** @var InvAmount&m\MockInterface $invoiceAmountRecord */
        $invoiceAmountRecord = m::mock(InvAmount::class);
        // Already recorded — e.g. mollieComplete()'s own redirect-time
        // check got there first.
        $invoiceAmountRecord->shouldReceive('getBalance')->andReturn(0.00);

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('abc123')->andReturn($invoice);
        $iR->shouldNotReceive('save');

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->once()->with(42)->andReturn($invoiceAmountRecord);
        $iaR->shouldNotReceive('save');

        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $this->makeUncalledInvPaymentSettlementService(),
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
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

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldNotReceive('repoUrlKeyGuestLoaded');

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $this->makeUncalledInvPaymentSettlementService(),
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
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

        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->once()->with('unknown-key')->andReturn(null);

        /** @var InvAmountRepository&m\MockInterface $iaR */
        $iaR = m::mock(InvAmountRepository::class);
        /** @var OnlinePaymentRecorderService&m\MockInterface $recorder */
        $recorder = m::mock(OnlinePaymentRecorderService::class);
        $recorder->shouldNotReceive('record');

        $handler = new MollieWebhookHandler(
            $iR,
            $iaR,
            $this->makeSettingRepository(),
            $this->makeUncalledInvPaymentSettlementService(),
            $recorder,
            $this->makeDataResponseFactory(),
            $this->makeLoggerSpy(),
            $mollieClient,
        );

        $response = $handler->handle($this->makeRequest(['id' => 'tr_123']));

        Assert::same(200, $response->getStatusCode());
    }
}
