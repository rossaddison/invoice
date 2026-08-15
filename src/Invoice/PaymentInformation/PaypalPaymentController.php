<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\PaypalPaymentService;
use App\Invoice\PaymentInformation\Service\PaypalWebhookHandler;
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
 * reasoning as every other dedicated gateway controller in this app (the
 * shared controller is already at SonarQube's php:S1448 method-count
 * ceiling).
 *
 * Unlike every other gateway in this app, `paypalComplete()` is **not**
 * purely read-only: PayPal requires a server-to-server "capture" call
 * (`PaypalPaymentService::captureOrder()`) after the customer approves on
 * PayPal's hosted page — PayPal does not move any money on its own once
 * approved, unlike a classic hosted-checkout redirect. This is a required
 * side effect of finalizing the transaction, not a "trust the client
 * redirect" shortcut, since the capture response comes from PayPal's own
 * API using this app's own credentials. Marking the invoice paid in this
 * app's own database is still left entirely to `PaypalWebhookHandler`,
 * matching every other gateway's belt-and-braces convention — this action
 * only triggers the capture and then re-reads current balance to decide
 * which message to show, same as every other Complete() action.
 *
 * A customer who cancels on PayPal's page is redirected back here too
 * (PayPal's `cancel_url`, reused as the same route as `return_url` to avoid
 * a special-cased extra action) — no `PayerID` query param is present in
 * that case, so `paypalComplete()` skips the capture attempt entirely and
 * just shows the ordinary unpaid/processing message.
 */
final class PaypalPaymentController
{
    use FlashMessage;
    use PaymentGatewayGuardTrait;

    public function __construct(
        private readonly PaypalPaymentService $paypalPaymentService,
        private readonly PaypalWebhookHandler $paypalWebhookHandler,
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
     * Starts the payment flow and sends the customer straight to PayPal's
     * hosted "approve" page — there's nothing of ours to render first.
     */
    public function paypalInForm(CurrentRoute $currentRoute): Response
    {
        $resolved = $this->resolveConfiguredInvoiceWithBalance($currentRoute, $this->paypalPaymentService, 'PayPal');
        if ($resolved instanceof Response) {
            return $resolved;
        }
        ['invoice' => $invoice, 'balance' => $balance] = $resolved;

        $urlKey = $invoice->getUrlKey();
        $completeUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/paypalComplete',
            ['url_key' => $urlKey],
        );
        $result = $this->paypalPaymentService->createPayment(
            $balance,
            'Invoice ' . ($invoice->getNumber() ?? $urlKey),
            $completeUrl,
            $completeUrl,
            ['invoiceUrlKey' => $urlKey],
        );
        if (null === $result) {
            $this->flashMessage('warning', 'Unable to start the PayPal payment — please try again shortly.');
            return $this->webService->getNotFoundResponse();
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $result['approveUrl']);
    }

    /**
     * Customer returns here from PayPal's hosted "approve" page (or arrives
     * here via PayPal's `cancel_url` if they cancelled instead) — see this
     * class's own docblock for why this action captures the order but never
     * itself marks the invoice paid.
     */
    public function paypalComplete(CurrentRoute $currentRoute, Request $request): Response
    {
        $invoice = $this->loadInvoice($currentRoute);
        if ($invoice instanceof Response) {
            return $invoice;
        }

        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        $balance = $invoiceAmountRecord->getBalance() ?? 0.00;

        $queryParams = $request->getQueryParams();
        $orderId = (string) ($queryParams['token'] ?? '');
        $payerId = (string) ($queryParams['PayerID'] ?? '');
        if ($balance > 0.00 && $orderId !== '' && $payerId !== '') {
            $capture = $this->paypalPaymentService->captureOrder($orderId);
            if (null === $capture) {
                $this->flashMessage('warning', 'Unable to confirm the PayPal payment — please check back shortly.');
            }
        }

        $urlKey = $invoice->getUrlKey();
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
                    'gateway' => 'Paypal',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['paypal'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function paypalWebhook(Request $request): Response
    {
        return $this->paypalWebhookHandler->handle($request);
    }

    private function loadInvoice(CurrentRoute $currentRoute): Response|Inv
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $invoice = null !== $urlKey ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        return $invoice ?? $this->webService->getNotFoundResponse();
    }
}
