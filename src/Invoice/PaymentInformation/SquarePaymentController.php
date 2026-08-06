<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\SquarePaymentService;
use App\Invoice\PaymentInformation\Service\SquareWebhookHandler;
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
 * reasoning as every other dedicated gateway controller in this app (the
 * shared controller is already at SonarQube's php:S1448 method-count
 * ceiling).
 *
 * Like every other gateway with a fully hosted checkout page, Square's own
 * Payment Link page IS the entire "in-form" step — there's no local
 * card-entry view to render, just a 302 straight to the `url`
 * SquarePaymentService::createPayment() returns.
 *
 * `squareComplete()` is read-only, the same pattern as
 * `PaymentInformationController::stripeComplete()`: the invoice is only
 * ever actually marked paid by `SquareWebhookHandler`, after its own
 * signature check AND authenticated re-confirmation via
 * `GET /v2/payments/{id}` — never by this redirect, since a customer's
 * browser returning here proves nothing about payment success on its own.
 * This action only re-reads current balance to decide which message to
 * show.
 */
final class SquarePaymentController
{
    use FlashMessage;

    public function __construct(
        private readonly SquarePaymentService $squarePaymentService,
        private readonly SquareWebhookHandler $squareWebhookHandler,
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
     * Starts the payment flow and sends the customer straight to Square's
     * hosted Payment Link page — there's nothing of ours to render first.
     */
    public function squareInForm(CurrentRoute $currentRoute): Response
    {
        $invoice = $this->loadInvoice($currentRoute);
        if ($invoice instanceof Response) {
            return $invoice;
        }
        if (!$this->squarePaymentService->isConfigured()) {
            $this->flashMessage('warning', 'Square payment gateway is not properly configured.');
            return $this->webService->getNotFoundResponse();
        }

        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        $balance = $invoiceAmountRecord->getBalance() ?? 0.00;
        if ($balance <= 0.00) {
            $this->flashMessage('warning', $this->translator->translate('already.paid'));
            return $this->webService->getNotFoundResponse();
        }

        $urlKey = $invoice->getUrlKey();
        $completeUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/squareComplete',
            ['url_key' => $urlKey, '_language' => 'en'],
        );
        $result = $this->squarePaymentService->createPayment(
            $balance,
            'Invoice ' . ($invoice->getNumber() ?? $urlKey),
            $completeUrl,
            ['invoiceUrlKey' => $urlKey],
        );
        if (null === $result) {
            $this->flashMessage('warning', 'Unable to start the Square payment — please try again shortly.');
            return $this->webService->getNotFoundResponse();
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $result['paymentLinkUrl']);
    }

    /**
     * Customer returns here from Square's hosted Payment Link page —
     * read-only, see this class's own docblock.
     */
    public function squareComplete(CurrentRoute $currentRoute): Response
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
                    'gateway' => 'Square',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['square'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function squareWebhook(Request $request): Response
    {
        return $this->squareWebhookHandler->handle($request);
    }

    private function loadInvoice(CurrentRoute $currentRoute): Response|Inv
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $invoice = null !== $urlKey ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        return $invoice ?? $this->webService->getNotFoundResponse();
    }
}
