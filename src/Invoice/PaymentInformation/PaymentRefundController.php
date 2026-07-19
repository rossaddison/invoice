<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\PaymentInformation\Service\AdyenPaymentService;
use App\Invoice\PaymentInformation\Service\AmazonPayPaymentService;
use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\PaymentInformation\Service\MolliePaymentService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\Setting\SettingRepository as sR;
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
        private readonly sR $sR,
        private readonly Translator $translator,
        private readonly WebControllerService $webService,
        private readonly Logger $logger,
        private readonly AdyenPaymentService $adyenPaymentService,
        private readonly AmazonPayPaymentService $amazonPayPaymentService,
        private readonly BraintreePaymentService $braintreePaymentService,
        private readonly MolliePaymentService $molliePaymentService,
        private readonly StripePaymentService $stripePaymentService,
    ) {
    }

    public function refund(CurrentRoute $currentRoute): Response
    {
        $paymentId = (int) $currentRoute->getArgument('payment_id', '0');
        $driver    = (string) $currentRoute->getArgument('gateway', '');
        $payment   = $paymentId > 0 ? $this->paymentRepository->repoPaymentquery($paymentId) : null;

        if (null === $payment || $driver === ''
                || '1' !== $this->sR->getSetting('gateway_' . strtolower($driver) . '_enabled')) {
            return $this->webService->getNotFoundResponse();
        }

        $merchant = $this->merchantRepository
            ->repoMerchantLatestSuccessfulByInvIdAndDriver($payment->reqInvId(), $driver);
        $reference = $merchant?->getProviderReference();

        if (null === $merchant || null === $reference || $reference === '') {
            $this->flashMessage('warning',
                sprintf($this->translator->translate('refund.no.provider.reference'), $driver));
            return $this->webService->getRedirectResponse('payment/index');
        }

        $result = $this->dispatchRefund($driver, $reference, $payment->getAmount() ?? 0.00);
        if (null === $result) {
            return $this->webService->getNotFoundResponse();
        }

        if ($result->refunded) {
            $this->recordRefundNote($payment, $driver, $result);
            $this->flashMessage('success',
                sprintf($this->translator->translate('refund.successful'), $driver));
        } else {
            $this->logger->error('Online payment refund failed.', [
                'driver'     => $driver,
                'payment_id' => $paymentId,
                'reference'  => $reference,
                'message'    => $result->message,
            ]);
            $this->flashMessage('danger',
                sprintf($this->translator->translate('refund.failed'), $driver));
        }

        return $this->webService->getRedirectResponse('payment/index');
    }

    private function dispatchRefund(string $driver, string $reference, float $amount): ?PaymentRefundResult
    {
        return match (strtolower($driver)) {
            'stripe'     => $this->stripePaymentService->refund($reference, $amount),
            'adyen'      => $this->adyenPaymentService->refund($reference, $amount),
            'braintree'  => $this->braintreePaymentService->refund($reference, $amount),
            'mollie'     => $this->molliePaymentService->refund($reference, $amount),
            'amazon_pay' => $this->amazonPayPaymentService->refund($reference, $amount),
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
