<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\PaymentInformation\Service\AdyenPaymentService;
use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\PaymentInformation\Service\CheckoutComPaymentService;
use App\Invoice\PaymentInformation\Service\GoCardlessPaymentService;
use App\Invoice\PaymentInformation\Service\MercadoPagoPaymentService;
use App\Invoice\PaymentInformation\Service\MolliePaymentService;
use App\Invoice\PaymentInformation\Service\PaypalPaymentService;
use App\Invoice\PaymentInformation\Service\PaystackPaymentService;
use App\Invoice\PaymentInformation\Service\RazorpayPaymentService;
use App\Invoice\PaymentInformation\Service\RobokassaPaymentService;
use App\Invoice\PaymentInformation\Service\SquarePaymentService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\PaymentInformation\Service\TrueLayerPaymentService;
use App\Invoice\PaymentInformation\Service\YookassaPaymentService;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\SquareMerchant\SquareMerchantRepository;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * Issues a full refund of a recorded Payment against whichever online
 * gateway the guest actually paid with, using the provider transaction
 * reference captured on the Merchant audit record at payment time (see
 * OnlinePaymentRecorderService / Merchant::provider_reference). Kept out of
 * PaymentInformationController, which is already at SonarQube's per-class
 * method count ceiling (php:S1448) — see pciCompliantGatewayInForms().
 */
final class PaymentRefundController
{
    use FlashMessage;

    public function __construct(
        private readonly Flash $flash,
        private readonly PaymentRepository $paymentRepository,
        private readonly MerchantRepository $merchantRepository,
        private readonly SquareMerchantRepository $squareMerchantRepository,
        private readonly sR $sR,
        private readonly Translator $translator,
        private readonly WebControllerService $webService,
        private readonly Logger $logger,
        private readonly AdyenPaymentService $adyenPaymentService,
        private readonly BraintreePaymentService $braintreePaymentService,
        private readonly GoCardlessPaymentService $goCardlessPaymentService,
        private readonly MolliePaymentService $molliePaymentService,
        private readonly StripePaymentService $stripePaymentService,
        private readonly RobokassaPaymentService $robokassaPaymentService,
        private readonly YookassaPaymentService $yookassaPaymentService,
        private readonly PaystackPaymentService $paystackPaymentService,
        private readonly RazorpayPaymentService $razorpayPaymentService,
        private readonly MercadoPagoPaymentService $mercadoPagoPaymentService,
        private readonly PaypalPaymentService $paypalPaymentService,
        private readonly SquarePaymentService $squarePaymentService,
        private readonly CheckoutComPaymentService $checkoutComPaymentService,
        private readonly TrueLayerPaymentService $trueLayerPaymentService,
    ) {
    }

    public function refund(CurrentRoute $currentRoute): Response
    {
        $context = $this->resolveContext($currentRoute);
        if ($context instanceof Response) {
            return $context;
        }

        $result = $this->dispatchRefund($context->driver, $context->reference, $context->payment->getAmount() ?? 0.00);
        if (null === $result) {
            return $this->webService->getNotFoundResponse();
        }

        $this->handleRefundResult($context, $result);

        return $this->webService->getRedirectResponse('payment/index');
    }

    private function resolveContext(CurrentRoute $currentRoute): Response|RefundContext
    {
        $paymentId = (int) $currentRoute->getArgument('payment_id', '0');
        $driver    = (string) $currentRoute->getArgument('gateway', '');
        $payment   = $paymentId > 0 ? $this->paymentRepository->repoPaymentquery($paymentId) : null;

        if (null === $payment || $driver === ''
                || '1' !== $this->sR->getSetting('gateway_' . strtolower($driver) . '_enabled')) {
            return $this->webService->getNotFoundResponse();
        }

        $reference = $this->resolveProviderReference($payment->reqInvId(), $driver);

        if (null === $reference || $reference === '') {
            $this->flashMessage('warning',
                sprintf($this->translator->translate('refund.no.provider.reference'), $driver));
            return $this->webService->getRedirectResponse('payment/index');
        }

        return new RefundContext($payment, $driver, $reference);
    }

    /**
     * Square has its own per-provider Merchant entity (SquareMerchant),
     * refund-capable by its payment_id column, not the generic Merchant
     * table's provider_reference — see SquareMerchant's own docblock.
     * Every other gateway still resolves through the shared Merchant
     * table, unchanged.
     */
    private function resolveProviderReference(int $invId, string $driver): ?string
    {
        if (strtolower($driver) === 'square') {
            return $this->squareMerchantRepository
                ->repoSquareMerchantLatestSuccessfulByInvId($invId)
                ?->getPaymentId();
        }

        return $this->merchantRepository
            ->repoMerchantLatestSuccessfulByInvIdAndDriver($invId, $driver)
            ?->getProviderReference();
    }

    private function handleRefundResult(RefundContext $context, PaymentRefundResult $result): void
    {
        if ($result->refunded) {
            $this->recordRefundNote($context->payment, $context->driver, $result);
            $this->flashMessage('success',
                sprintf($this->translator->translate('refund.successful'), $context->driver));
            return;
        }

        $this->logger->error('Online payment refund failed.', [
            'driver'     => $context->driver,
            'payment_id' => $context->payment->reqId(),
            'reference'  => $context->reference,
            'message'    => $result->message,
        ]);
        $this->flashMessage('danger',
            sprintf($this->translator->translate('refund.failed'), $context->driver));
    }

    private function dispatchRefund(string $driver, string $reference, float $amount): ?PaymentRefundResult
    {
        return match (strtolower($driver)) {
            'stripe'     => $this->stripePaymentService->refund($reference, $amount),
            'adyen'      => $this->adyenPaymentService->refund($reference, $amount),
            'braintree'  => $this->braintreePaymentService->refund($reference, $amount),
            'mollie'     => $this->molliePaymentService->refund($reference, $amount),
            'gocardless' => $this->goCardlessPaymentService->refund($reference, $amount),
            'robokassa'  => $this->robokassaPaymentService->refund($reference, $amount),
            'yookassa'   => $this->yookassaPaymentService->refund($reference, $amount),
            'paystack'   => $this->paystackPaymentService->refund($reference, $amount),
            'razorpay'   => $this->razorpayPaymentService->refund($reference, $amount),
            'mercado_pago' => $this->mercadoPagoPaymentService->refund($reference, $amount),
            'paypal'     => $this->paypalPaymentService->refund($reference, $amount),
            'square'     => $this->squarePaymentService->refund($reference, $amount),
            'checkout_com' => $this->checkoutComPaymentService->refund($reference, $amount),
            'truelayer'  => $this->trueLayerPaymentService->refund($reference, $amount),
            default      => null,
        };
    }

    private function recordRefundNote(
        Payment $payment,
        string $driver,
        PaymentRefundResult $result,
    ): void {
        $note = $payment->getNote() . "\n"
            . sprintf($this->translator->translate('refund.recorded'), $driver, $result->providerReference);
        $payment->setNote($note);
        $this->paymentRepository->save($payment);
    }
}
