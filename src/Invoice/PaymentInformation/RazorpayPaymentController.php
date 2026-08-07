<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\RazorpayPaymentService;
use App\Invoice\PaymentInformation\Service\RazorpayWebhookHandler;
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
 * PaystackPaymentController (the shared controller is already at
 * SonarQube's php:S1448 method-count ceiling).
 *
 * Like every other gateway with a fully hosted checkout page, Razorpay's own
 * Payment Link page IS the entire "in-form" step — there's no local
 * card-entry view to render, just a 302 straight to the `short_url`
 * `RazorpayPaymentService::createPayment()` returns.
 *
 * `razorpayComplete()` is read-only, the same pattern as
 * `PaymentInformationController::stripeComplete()`: the invoice is only ever
 * actually marked paid by `RazorpayWebhookHandler`, after its own signature
 * check AND authenticated re-confirmation via
 * `GET /v1/payment_links/{id}` — never by this redirect. Razorpay's Payment
 * Link callback query string is actually self-verifying (a genuine
 * `razorpay_signature` HMAC is appended — see RazorpaySignatureService's
 * docblock), architecturally unlike Robokassa/YooKassa/Paystack's plain
 * redirects, but this action deliberately still treats it as informational
 * only, for the same defense-in-depth reason every other gateway here does:
 * a customer's browser returning here proves nothing about payment success
 * on its own. This action only re-reads current balance to decide which
 * message to show.
 */
final class RazorpayPaymentController
{
    use FlashMessage;
    use PaymentGatewayGuardTrait;

    public function __construct(
        private readonly RazorpayPaymentService $razorpayPaymentService,
        private readonly RazorpayWebhookHandler $razorpayWebhookHandler,
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
     * Starts the payment flow and sends the customer straight to Razorpay's
     * hosted Payment Link page — there's nothing of ours to render first.
     */
    public function razorpayInForm(CurrentRoute $currentRoute): Response
    {
        $resolved = $this->resolveConfiguredInvoiceWithBalance($currentRoute, $this->razorpayPaymentService, 'Razorpay');
        if ($resolved instanceof Response) {
            return $resolved;
        }
        ['invoice' => $invoice, 'balance' => $balance] = $resolved;

        $urlKey = $invoice->getUrlKey();
        $callbackUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/razorpayComplete',
            ['url_key' => $urlKey, '_language' => 'en'],
        );
        $result = $this->razorpayPaymentService->createPayment(
            $balance,
            'Invoice ' . ($invoice->getNumber() ?? $urlKey),
            $callbackUrl,
            ['invoiceUrlKey' => $urlKey],
        );
        if (null === $result) {
            $this->flashMessage('warning', 'Unable to start the Razorpay payment — please try again shortly.');
            return $this->webService->getNotFoundResponse();
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $result['paymentLinkUrl']);
    }


    /**
     * Customer returns here from Razorpay's hosted Payment Link page —
     * read-only, see this class's own docblock.
     */
    public function razorpayComplete(CurrentRoute $currentRoute): Response
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
                    'gateway' => 'Razorpay',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['razorpay'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function razorpayWebhook(Request $request): Response
    {
        return $this->razorpayWebhookHandler->handle($request);
    }

    private function loadInvoice(CurrentRoute $currentRoute): Response|Inv
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $invoice = null !== $urlKey ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        return $invoice ?? $this->webService->getNotFoundResponse();
    }
}
