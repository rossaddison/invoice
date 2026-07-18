<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\Merchant\Merchant;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Merchant\MerchantService;
use App\Invoice\Payment\PaymentService;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\Traits\FlashMessage;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Writes the payment/merchant audit record shared by every online gateway's
 * completion flow (Braintree, Mollie, Stripe). Extracted out of
 * PaymentInformationController to keep it under SonarQube's per-class method
 * count (php:S1448).
 */
final class OnlinePaymentRecorderService
{
    use FlashMessage;

    public function __construct(
        private readonly DataResponseFactoryInterface $factory,
        private readonly Flash $flash,
        private readonly MerchantService $merchantService,
        private readonly PaymentService $paymentService,
        private readonly Translator $translator,
        private readonly WebViewRenderer $webViewRenderer,
    ) {
    }

    /** @psalm-suppress UnusedReturnValue */
    public function record(PaymentRecordContext $ctx): Response
    {
        if ($ctx->response) {
            return $this->recordSuccess($ctx);
        }

        return $this->recordFailure($ctx);
    }

    private function recordSuccess(PaymentRecordContext $ctx): Response
    {
        $payment_note = $this->translator->translate('transaction.reference')
                . ': ' . $ctx->reference . "\n";
        $payment_note .= $this->translator->translate('payment.provider')
                . ': ' . ucwords(str_replace('_', ' ', $ctx->d));

        $payment_array = [
            'inv_id'            => $ctx->invoice_id,
            'payment_date'      =>
                \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
            'amount'            => $ctx->balance,
            'payment_method_id' => $ctx->invoice_payment_method,
            'note'              => $payment_note,
        ];

        $payment = new Payment();
        $this->paymentService->addPaymentViaPaymentHandler($payment, $payment_array);

        $payment_success_msg = sprintf($this->translator->translate(
                    'online.payment.payment.successful'), $ctx->invoice_number);

        $successful_merchant_response_array = [
            'inv_id'                       => $ctx->invoice_id,
            'merchant_response_successful' => true,
            'merchant_response_date'       =>
                \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
            'merchant_response_driver'     => $ctx->driver,
            'merchant_response'            => $payment_success_msg,
            'merchant_response_reference'  => $ctx->reference,
        ];

        $merchant_response = new Merchant();
        $this->merchantService
            ->saveMerchantViaPaymentHandler(
                    $merchant_response, $successful_merchant_response_array);

        $this->flashMessage('success', $payment_success_msg);

        return $this->factory->createResponse(
            $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/payment_message',
                [
                    'heading'     => '',
                    'message'     => $payment_success_msg,
                    'url'         => 'inv/urlKey',
                    'url_key'     => $ctx->invoice_url_key,
                    'gateway'     => $ctx->driver,
                    'sandbox_url' => $ctx->sandbox_url_array[$ctx->d],
                ],
            ),
        );
    }

    private function recordFailure(PaymentRecordContext $ctx): Response
    {
        $payment_failure_msg = sprintf(
            $this->translator->translate(
                'online.payment.payment.failed'), $ctx->invoice_number);

        $unsuccessful_merchant_response_array = [
            'inv_id'                       => $ctx->invoice_id,
            'merchant_response_successful' => false,
            'merchant_response_date'       =>
                \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
            'merchant_response_driver'     => $ctx->driver,
            'merchant_response'            => $payment_failure_msg,
            'merchant_response_reference'  => $ctx->reference,
        ];

        $merchant_response = new Merchant();
        $this->merchantService
            ->saveMerchantViaPaymentHandler(
                $merchant_response,
                $unsuccessful_merchant_response_array,
            );

        $this->flashMessage('warning', $payment_failure_msg);

        return $this->factory->createResponse(
            $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/payment_message',
                [
                    'heading'     => '',
                    'message'     => $payment_failure_msg,
                    'url'         => 'inv/urlKey',
                    'url_key'     => $ctx->invoice_url_key,
                    'gateway'     => $ctx->driver,
                    'sandbox_url' => $ctx->sandbox_url_array[$ctx->d],
                ],
            ),
        );
    }
}
