<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Merchant\Merchant;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\PaymentInformation\PaymentRefundController;
use App\Invoice\PaymentInformation\PaymentRefundResult;
use App\Invoice\PaymentInformation\Service\AdyenPaymentService;
use App\Invoice\PaymentInformation\Service\AmazonPayPaymentService;
use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\PaymentInformation\Service\GoCardlessPaymentService;
use App\Invoice\PaymentInformation\Service\MolliePaymentService;
use App\Invoice\PaymentInformation\Service\PaypalPaymentService;
use App\Invoice\PaymentInformation\Service\PaystackPaymentService;
use App\Invoice\PaymentInformation\Service\RazorpayPaymentService;
use App\Invoice\PaymentInformation\Service\RobokassaPaymentService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\PaymentInformation\Service\YookassaPaymentService;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Exercises PaymentRefundController::refund() end-to-end against its public
 * contract: the not-found guards (missing payment, empty/disabled driver,
 * missing provider reference, unknown driver falling through the dispatch
 * match), and both the success/failure result handling. PaymentRepository/
 * MerchantRepository/SettingRepository/Flash are `final`, mockable here only
 * because Tests/bootstrap.php enables DG\BypassFinals. WebControllerService
 * is constructed for real against stubs of its own (non-final) interface
 * dependencies, since it's cheap to build honestly.
 */
final class PaymentRefundControllerTest extends TestCase
{
    /** @param array<string, int> $placeholderCounts translation id => number of %s placeholders expected */
    private function makeTranslator(array $placeholderCounts = []): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(
            static fn(string|Stringable $id): string => $id . str_repeat(':%s', $placeholderCounts[(string) $id] ?? 1),
        );

        return $translator;
    }

    private function makeFlash(): Flash
    {
        // Flash is `final`; mockable here only via DG\BypassFinals (see class docblock).
        // has() must return false so FlashMessage::flashMessage() actually calls add().
        $flash = $this->createStub(Flash::class);
        $flash->method('has')->willReturn(false);

        return $flash;
    }

    private function makeWebService(): WebControllerService
    {
        $httpFactory = new HttpFactory();
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/payment/index');

        return new WebControllerService($httpFactory, $httpFactory, $urlGenerator);
    }

    private function makeRoute(int $paymentId, string $gateway): CurrentRoute
    {
        $route = $this->createStub(CurrentRoute::class);
        $route->method('getArgument')->willReturnCallback(
            static fn(string $name, ?string $default = null): ?string => match ($name) {
                'payment_id' => (string) $paymentId,
                'gateway' => $gateway,
                default => $default,
            },
        );

        return $route;
    }

    /**
     * @param array<string, string> $settings
     * @param array<string, int> $translatorPlaceholders
     */
    private function makeController(
        array $settings,
        ?Payment $payment,
        ?Merchant $merchant,
        StripePaymentService $stripe,
        ?LoggerInterface $logger = null,
        ?PaymentRepository $paymentRepository = null,
        array $translatorPlaceholders = [],
        ?GoCardlessPaymentService $goCardless = null,
    ): PaymentRefundController {
        $sR = $this->createStub(SettingRepository::class);
        $sR->method('getSetting')->willReturnCallback(
            static fn(string $key): string => $settings[$key] ?? '',
        );

        if ($paymentRepository === null) {
            $paymentRepository = $this->createStub(PaymentRepository::class);
            $paymentRepository->method('repoPaymentquery')->willReturn($payment);
        }

        $merchantRepository = $this->createStub(MerchantRepository::class);
        $merchantRepository->method('repoMerchantLatestSuccessfulByInvIdAndDriver')->willReturn($merchant);

        return new PaymentRefundController(
            $this->makeFlash(),
            $paymentRepository,
            $merchantRepository,
            $sR,
            $this->makeTranslator($translatorPlaceholders),
            $this->makeWebService(),
            $logger ?? new NullLogger(),
            $this->createStub(AdyenPaymentService::class),
            $this->createStub(AmazonPayPaymentService::class),
            $this->createStub(BraintreePaymentService::class),
            $goCardless ?? $this->createStub(GoCardlessPaymentService::class),
            $this->createStub(MolliePaymentService::class),
            $stripe,
            $this->createStub(RobokassaPaymentService::class),
            $this->createStub(YookassaPaymentService::class),
            $this->createStub(PaystackPaymentService::class),
            $this->createStub(RazorpayPaymentService::class),
            $this->createStub(PaypalPaymentService::class),
        );
    }

    public function testRefundReturnsNotFoundWhenPaymentIdMissing(): void
    {
        $paymentRepository = $this->createMock(PaymentRepository::class);
        $paymentRepository->expects(self::never())->method('repoPaymentquery');

        $controller = $this->makeController(
            settings: [],
            payment: null,
            merchant: null,
            stripe: $this->createStub(StripePaymentService::class),
            paymentRepository: $paymentRepository,
        );

        $response = $controller->refund($this->makeRoute(0, 'stripe'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRefundReturnsNotFoundWhenGatewayEmpty(): void
    {
        $payment = $this->createStub(Payment::class);

        $controller = $this->makeController(
            settings: [],
            payment: $payment,
            merchant: null,
            stripe: $this->createStub(StripePaymentService::class),
        );

        $response = $controller->refund($this->makeRoute(1, ''));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRefundReturnsNotFoundWhenGatewayNotEnabled(): void
    {
        $payment = $this->createStub(Payment::class);

        $controller = $this->makeController(
            settings: ['gateway_stripe_enabled' => '0'],
            payment: $payment,
            merchant: null,
            stripe: $this->createStub(StripePaymentService::class),
        );

        $response = $controller->refund($this->makeRoute(1, 'stripe'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRefundRedirectsWithWarningWhenNoProviderReference(): void
    {
        $payment = $this->createStub(Payment::class);
        $payment->method('reqInvId')->willReturn(9);

        $controller = $this->makeController(
            settings: ['gateway_stripe_enabled' => '1'],
            payment: $payment,
            merchant: null,
            stripe: $this->createStub(StripePaymentService::class),
        );

        $response = $controller->refund($this->makeRoute(1, 'stripe'));

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/payment/index', $response->getHeaderLine('Location'));
    }

    public function testRefundReturnsNotFoundWhenDriverUnknown(): void
    {
        $payment = $this->createStub(Payment::class);
        $payment->method('reqInvId')->willReturn(9);

        $merchant = $this->createStub(Merchant::class);
        $merchant->method('getProviderReference')->willReturn('ref-123');

        $controller = $this->makeController(
            settings: ['gateway_unknown_enabled' => '1'],
            payment: $payment,
            merchant: $merchant,
            stripe: $this->createStub(StripePaymentService::class),
        );

        $response = $controller->refund($this->makeRoute(1, 'unknown'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRefundDispatchesToGoCardlessWhenDriverIsGocardless(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('reqInvId')->willReturn(9);
        $payment->method('reqId')->willReturn(1);
        $payment->method('getAmount')->willReturn(130.0);
        $payment->method('getNote')->willReturn('');
        $payment->expects(self::once())->method('setNote')->with(self::stringContains('RF123'));

        $merchant = $this->createStub(Merchant::class);
        $merchant->method('getProviderReference')->willReturn('PM123');

        $paymentRepository = $this->createMock(PaymentRepository::class);
        $paymentRepository->method('repoPaymentquery')->willReturn($payment);
        $paymentRepository->expects(self::once())->method('save')->with($payment);

        $goCardless = $this->createMock(GoCardlessPaymentService::class);
        $goCardless->expects(self::once())
            ->method('refund')
            ->with('PM123', 130.0)
            ->willReturn(new PaymentRefundResult(refunded: true, providerReference: 'RF123', message: 'refunded'));

        $controller = $this->makeController(
            settings: ['gateway_gocardless_enabled' => '1'],
            payment: $payment,
            merchant: $merchant,
            stripe: $this->createStub(StripePaymentService::class),
            paymentRepository: $paymentRepository,
            translatorPlaceholders: ['refund.recorded' => 2],
            goCardless: $goCardless,
        );

        $response = $controller->refund($this->makeRoute(1, 'gocardless'));

        self::assertSame(302, $response->getStatusCode());
    }

    public function testRefundSuccessRecordsNoteAndRedirects(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('reqInvId')->willReturn(9);
        $payment->method('reqId')->willReturn(1);
        $payment->method('getAmount')->willReturn(25.0);
        $payment->method('getNote')->willReturn('');
        $payment->expects(self::once())->method('setNote')->with(self::stringContains('re_123'));

        $merchant = $this->createStub(Merchant::class);
        $merchant->method('getProviderReference')->willReturn('pi_123');

        $paymentRepository = $this->createMock(PaymentRepository::class);
        $paymentRepository->method('repoPaymentquery')->willReturn($payment);
        $paymentRepository->expects(self::once())->method('save')->with($payment);

        $stripe = $this->createMock(StripePaymentService::class);
        $stripe->expects(self::once())
            ->method('refund')
            ->with('pi_123', 25.0)
            ->willReturn(new PaymentRefundResult(refunded: true, providerReference: 're_123', message: 'succeeded'));

        $controller = $this->makeController(
            settings: ['gateway_stripe_enabled' => '1'],
            payment: $payment,
            merchant: $merchant,
            stripe: $stripe,
            paymentRepository: $paymentRepository,
            translatorPlaceholders: ['refund.recorded' => 2],
        );

        $response = $controller->refund($this->makeRoute(1, 'stripe'));

        self::assertSame(302, $response->getStatusCode());
    }

    public function testRefundFailureLogsErrorWithoutSavingNote(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('reqInvId')->willReturn(9);
        $payment->method('reqId')->willReturn(1);
        $payment->method('getAmount')->willReturn(25.0);
        $payment->expects(self::never())->method('setNote');

        $merchant = $this->createStub(Merchant::class);
        $merchant->method('getProviderReference')->willReturn('pi_123');

        $paymentRepository = $this->createMock(PaymentRepository::class);
        $paymentRepository->method('repoPaymentquery')->willReturn($payment);
        $paymentRepository->expects(self::never())->method('save');

        $stripe = $this->createStub(StripePaymentService::class);
        $stripe->method('refund')->willReturn(
            new PaymentRefundResult(refunded: false, providerReference: 'pi_123', message: 'card_declined'),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('Online payment refund failed.', self::callback(
            static fn(array $context): bool => $context['driver'] === 'stripe' && $context['message'] === 'card_declined',
        ));

        $controller = $this->makeController(
            settings: ['gateway_stripe_enabled' => '1'],
            payment: $payment,
            merchant: $merchant,
            stripe: $stripe,
            logger: $logger,
            paymentRepository: $paymentRepository,
        );

        $response = $controller->refund($this->makeRoute(1, 'stripe'));

        self::assertSame(302, $response->getStatusCode());
    }
}
