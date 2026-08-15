<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\MercadoPagoPaymentService;
use App\Invoice\PaymentInformation\Service\MercadoPagoWebhookHandler;
use App\Invoice\PaymentInformation\Trait\PaymentGatewayGuardTrait;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Kept separate from PaymentInformationController deliberately — same
 * reasoning as AdyenPaymentController/GoCardlessPaymentController/
 * RobokassaPaymentController/YookassaPaymentController/
 * PaystackPaymentController/RazorpayPaymentController (the shared
 * controller is already at SonarQube's php:S1448 method-count ceiling).
 *
 * Like every other gateway with a fully hosted checkout page, Mercado
 * Pago's own Checkout Pro page IS the entire "in-form" step — there's no
 * local card-entry view to render, just a 302 straight to the `init_point`/
 * `sandbox_init_point` URL `MercadoPagoPaymentService::createPayment()`
 * returns.
 *
 * `mercadoPagoComplete()` is read-only, the same pattern as
 * `RazorpayPaymentController::razorpayComplete()`: the invoice is only ever
 * actually marked paid by `MercadoPagoWebhookHandler`, after its own
 * signature check AND authenticated re-confirmation via
 * `GET /v1/payments/{id}` — never by this redirect. A customer's browser
 * returning here proves nothing about payment success on its own — this
 * action only re-reads current balance to decide which message to show.
 */
final class MercadoPagoPaymentController
{
    use FlashMessage;
    use PaymentGatewayGuardTrait;

    public function __construct(
        private readonly MercadoPagoPaymentService $mercadoPagoPaymentService,
        private readonly MercadoPagoWebhookHandler $mercadoPagoWebhookHandler,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Translator $translator,
        private readonly UrlGenerator $urlGenerator,
        private readonly UserService $userService,
        private WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly Flash $flash,
    ) {
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && !$this->userService->hasPermission(Permissions::EDIT_INV)) {
            $this->webViewRenderer = $webViewRenderer
                ->withControllerName('invoice/paymentinformation')
                ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && $this->userService->hasPermission(Permissions::EDIT_INV)) {
            $this->webViewRenderer = $webViewRenderer
                ->withControllerName('invoice/paymentinformation')
                ->withLayout('@views/layout/invoice.php');
        }
    }

    /**
     * Starts the payment flow and sends the customer straight to Mercado
     * Pago's hosted Checkout Pro page — there's nothing of ours to render
     * first.
     */
    public function mercadoPagoInForm(CurrentRoute $currentRoute): Response
    {
        $resolved = $this->resolveConfiguredInvoiceWithBalance($currentRoute, $this->mercadoPagoPaymentService, 'Mercado Pago');
        if ($resolved instanceof Response) {
            return $resolved;
        }
        ['invoice' => $invoice, 'balance' => $balance] = $resolved;

        $urlKey = $invoice->getUrlKey();
        $callbackUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/mercadoPagoComplete',
            ['url_key' => $urlKey],
        );
        $notificationUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/mercadoPagoWebhook',
        );
        $result = $this->mercadoPagoPaymentService->createPayment(
            $balance,
            'Invoice ' . ($invoice->getNumber() ?? $urlKey),
            $callbackUrl,
            $notificationUrl,
            ['invoiceUrlKey' => $urlKey],
        );
        if (null === $result) {
            $this->flashMessage('warning', 'Unable to start the Mercado Pago payment — please try again shortly.');
            return $this->webService->getNotFoundResponse();
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $result['checkoutUrl']);
    }

    /**
     * Customer returns here from Mercado Pago's hosted Checkout Pro page —
     * read-only, see this class's own docblock.
     */
    public function mercadoPagoComplete(CurrentRoute $currentRoute): Response
    {
        $invoice = $this->loadInvoice($currentRoute);
        if ($invoice instanceof Response) {
            return $invoice;
        }

        $urlKey = $invoice->getUrlKey();
        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        $isPaid = 0.00 === ($invoiceAmountRecord->getBalance() ?? 0.00);
        $invoiceNumber = $invoice->getNumber() ?? 'unknown';

        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/paymentinformation/payment_message',
                [
                    'heading' => $isPaid
                        ? sprintf($this->translator->translate('online.payment.payment.successful'), $invoiceNumber)
                        : sprintf($this->translator->translate('online.payment.payment.processing'), $invoiceNumber),
                    'message' => $this->translator->translate('payment')
                        . ':' . $this->translator->translate('complete'),
                    'url' => 'inv/urlKey',
                    'url_key' => $urlKey,
                    'gateway' => 'Mercado Pago',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['mercado_pago'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function mercadoPagoWebhook(Request $request): Response
    {
        return $this->mercadoPagoWebhookHandler->handle($request);
    }

    private function loadInvoice(CurrentRoute $currentRoute): Response|Inv
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $invoice = null !== $urlKey ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        return $invoice ?? $this->webService->getNotFoundResponse();
    }
}
