<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentInformation\PaymentVerificationResult;
use App\Invoice\PaymentInformation\Service\OnlinePaymentRecorderService;
use App\Invoice\PaymentInformation\Service\RobokassaPaymentService;
use App\Invoice\PaymentInformation\Service\RobokassaWebhookHandler;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * The Robokassa Result URL is public. With no password2 configured its signature could be computed by
 * anyone, and the handler then recorded the invoice's whole balance as paid against whatever url_key was
 * posted. A callback must now carry a signature that verifies against a configured password AND be
 * confirmed paid by Robokassa itself (OpStateExt) before anything is recorded.
 */
#[Test]
final class RobokassaWebhookHandlerTest
{
    private function handler(
        RobokassaPaymentService $service,
        OnlinePaymentRecorderService $recorder,
        InvPaymentSettlementService $settlement,
        InvRepository $iR,
        InvAmountRepository $iaR,
    ): RobokassaWebhookHandler {
        $factory = TypedMock::of(DataResponseFactoryInterface::class);
        $factory->shouldReceive('createResponse')->andReturnUsing(
            static fn (): \Psr\Http\Message\ResponseInterface => new HttpFactory()->createResponse(200),
        );
        $sR = TypedMock::of(SettingRepository::class);
        $sR->shouldReceive('sandboxUrlArray')->andReturn([]);

        return new RobokassaWebhookHandler($service, $iR, $iaR, $sR, $settlement, $recorder, $factory, new NullLogger());
    }

    private function request(): ServerRequest
    {
        return new ServerRequest('POST', '/paymentinformation/robokassaWebhook')->withParsedBody([
            'OutSum' => '1',
            'InvId' => '1',
            'SignatureValue' => 'whatever',
            'Shp_invoiceUrlKey' => 'the-key',
        ]);
    }

    public function aCallbackWithAnInvalidSignatureIsRefusedAndRecordsNothing(): void
    {
        $service = TypedMock::of(RobokassaPaymentService::class);
        $service->shouldReceive('verifyResultUrlCallback')->andReturn(false);
        $service->shouldReceive('verifyPayment')->never();
        $recorder = TypedMock::of(OnlinePaymentRecorderService::class);
        $recorder->shouldReceive('record')->never();
        $settlement = TypedMock::of(InvPaymentSettlementService::class);
        $settlement->shouldReceive('markInvoicePaidAndAdjustStock')->never();

        $response = $this->handler(
            $service,
            $recorder,
            $settlement,
            TypedMock::of(InvRepository::class),
            TypedMock::of(InvAmountRepository::class),
        )->handle($this->request());

        Assert::same(400, $response->getStatusCode());
    }

    public function aSignedCallbackThatRobokassaDoesNotConfirmPaidIsRefusedAndRecordsNothing(): void
    {
        $service = TypedMock::of(RobokassaPaymentService::class);
        $service->shouldReceive('verifyResultUrlCallback')->andReturn(true);
        $service->shouldReceive('verifyPayment')->once()->with('1')->andReturn(
            new PaymentVerificationResult(false, '1', 'State code: 5'),
        );
        $recorder = TypedMock::of(OnlinePaymentRecorderService::class);
        $recorder->shouldReceive('record')->never();
        $settlement = TypedMock::of(InvPaymentSettlementService::class);
        $settlement->shouldReceive('markInvoicePaidAndAdjustStock')->never();

        $response = $this->handler(
            $service,
            $recorder,
            $settlement,
            TypedMock::of(InvRepository::class),
            TypedMock::of(InvAmountRepository::class),
        )->handle($this->request());

        Assert::same(400, $response->getStatusCode());
    }

    public function aSignedCallbackConfirmedPaidIsRecorded(): void
    {
        $service = TypedMock::of(RobokassaPaymentService::class);
        $service->shouldReceive('verifyResultUrlCallback')->andReturn(true);
        $service->shouldReceive('verifyPayment')->once()->with('1')->andReturn(
            new PaymentVerificationResult(true, '1', 'State code: 100'),
        );

        $inv = new Inv();
        $inv->setId(7);
        $invAmount = new InvAmount();
        $invAmount->setInvId(7);
        $invAmount->setBalance(50.0);
        $iR = TypedMock::of(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->with('the-key')->andReturn($inv);
        $iaR = TypedMock::of(InvAmountRepository::class);
        $iaR->shouldReceive('repoInvquery')->with(7)->andReturn($invAmount);
        $recorder = TypedMock::of(OnlinePaymentRecorderService::class);
        $recorder->shouldReceive('record')->once();
        $settlement = TypedMock::of(InvPaymentSettlementService::class);
        $settlement->shouldReceive('markInvoicePaidAndAdjustStock')->once();

        $response = $this->handler($service, $recorder, $settlement, $iR, $iaR)->handle($this->request());

        Assert::same(200, $response->getStatusCode());
    }
}
