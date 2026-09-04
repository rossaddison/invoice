<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\Merchant\Merchant;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Infrastructure\Persistence\SquareMerchant\SquareMerchant;
use App\Invoice\Merchant\MerchantService;
use App\Invoice\Payment\PaymentService;
use App\Invoice\PaymentInformation\PaymentRecordContext;
use App\Invoice\SquareMerchant\SquareMerchantService;
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
        private readonly SquareMerchantService $squareMerchantService,
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
        // Emoji prefix so an admin browsing the payment list/detail view can
        // tell at a glance which mechanism actually confirmed this payment
        // — see PaymentRecordChannel's own docblock.
        $payment_note = $ctx->channel->emoji() . ' '
                . $this->translator->translate('transaction.reference')
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
            'online.payment.payment.successful'
        ), $ctx->invoice_number);

        $this->recordAuditRow($ctx, true, $payment_success_msg);

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
                'online.payment.payment.failed'
            ),
            $ctx->invoice_number
        );

        $this->recordAuditRow($ctx, false, $payment_failure_msg);

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

    /**
     * Square writes its own SquareMerchant row instead of the generic
     * Merchant one — see that entity's own docblock for why. Every other
     * gateway keeps using the shared Merchant table, unchanged.
     */
    private function recordAuditRow(PaymentRecordContext $ctx, bool $successful, string $message): void
    {
        if ($ctx->driver === 'Square') {
            $this->recordSquareMerchant($ctx, $successful, $message);
            return;
        }

        $this->recordGenericMerchant($ctx, $successful, $message);
    }

    private function recordGenericMerchant(PaymentRecordContext $ctx, bool $successful, string $message): void
    {
        $merchant_response_array = [
            'inv_id'                       => $ctx->invoice_id,
            'merchant_response_successful' => $successful,
            'merchant_response_date'       =>
                \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
            'merchant_response_driver'     => $ctx->driver,
            'merchant_response'            => $message,
            'merchant_response_reference'  => $ctx->reference,
            'merchant_response_provider_reference' => $successful ? $ctx->provider_reference : null,
        ];

        $merchant_response = new Merchant();
        $this->merchantService->saveMerchantViaPaymentHandler($merchant_response, $merchant_response_array);
    }

    private function recordSquareMerchant(PaymentRecordContext $ctx, bool $successful, string $message): void
    {
        $square_merchant_array = [
            'inv_id'                       => (int) $ctx->invoice_id,
            'merchant_response_successful' => $successful,
            'merchant_response_date'       =>
                \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
            'merchant_response'            => $message,
            'merchant_response_reference'  => $ctx->reference,
            'merchant_response_payment_id' => $successful ? $ctx->provider_reference : null,
            'merchant_response_order_id'   => $successful ? $ctx->secondary_provider_reference : null,
        ];

        $square_merchant = new SquareMerchant();
        $this->squareMerchantService->saveSquareMerchantViaPaymentHandler($square_merchant, $square_merchant_array);
    }
}
