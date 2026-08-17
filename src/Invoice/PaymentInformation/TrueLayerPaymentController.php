<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\TrueLayerPaymentService;
use App\Invoice\PaymentInformation\Service\TrueLayerWebhookHandler;
use App\Invoice\PaymentInformation\Trait\PaymentGatewayGuardTrait;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Kept separate from PaymentInformationController deliberately — same
 * reasoning as every other dedicated gateway controller in this app (the
 * shared controller is already at SonarQube's php:S1448 method-count
 * ceiling).
 *
 * TrueLayer's own Hosted Payments Page IS the entire "in-form" step —
 * there's no local card-entry view to render, just a 302 straight to the
 * URL TrueLayerPaymentService::createPayment() returns, same single-action
 * shape as SquarePaymentController/RazorpayPaymentController/
 * PaypalPaymentController's own InForm() actions.
 *
 * `trueLayerComplete()` is reached via a fixed, no-suffix return URL (see
 * TrueLayerPaymentService::returnUrl()'s own docblock for why), not a
 * per-invoice route parameter like every other gateway's own Complete()
 * action — TrueLayer appends only `?payment_id=...` to whatever return URL
 * it's given (confirmed directly against TrueLayer's own docs), so the
 * invoice itself is resolved from that payment's own `metadata` via
 * TrueLayerPaymentService::resolveInvoiceUrlKey(), not the URL.
 *
 * `trueLayerComplete()` also calls
 * `TrueLayerWebhookHandler::markInvoicePaidIfVerified()` directly — a
 * synchronous fallback added 2026-08-17 after confirming live that
 * TrueLayer can leave a webhook endpoint's delivery paused indefinitely
 * following an earlier run of failures, with no way for this app to force
 * a resend. This is deliberately NOT "trusting the browser redirect" in
 * the risky sense the original design avoided: the browser only supplies
 * which `payment_id` to check, and `markInvoicePaidIfVerified()` still
 * requires TrueLayer's own authenticated `GET /v3/payments/{id}` response
 * before marking anything paid — exactly the same call the webhook path
 * uses. The webhook remains the primary/first-to-arrive trigger when it
 * does work; `markInvoicePaidIfVerified()`'s own balance-zero guard makes
 * calling it a second time here a safe no-op either way.
 */
final class TrueLayerPaymentController
{
    use FlashMessage;
    use PaymentGatewayGuardTrait;

    public function __construct(
        private readonly TrueLayerPaymentService $trueLayerPaymentService,
        private readonly TrueLayerWebhookHandler $trueLayerWebhookHandler,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Translator $translator,
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
     * Starts the payment flow and sends the customer straight to
     * TrueLayer's Hosted Payments Page — there's nothing of ours to render
     * first.
     */
    public function trueLayerInForm(CurrentRoute $currentRoute): Response
    {
        $resolved = $this->resolveConfiguredInvoiceWithBalance(
            $currentRoute, $this->trueLayerPaymentService, 'TrueLayer');
        if ($resolved instanceof Response) {
            return $resolved;
        }
        ['invoice' => $invoice, 'balance' => $balance] = $resolved;

        $client = $invoice->getClient();
        $redirectUrl = $this->trueLayerPaymentService->createPayment(
            $balance,
            $this->sR->getSetting('currency_code') ?: 'GBP',
            $invoice->getUrlKey(),
            trim(($client?->getClientName() ?? '') . ' ' . ($client?->getClientSurname() ?? '')),
            $client?->getClientEmail() ?? '',
        );
        if (null === $redirectUrl) {
            $this->flashMessage('warning', 'Unable to start the TrueLayer payment — please try again shortly.');
            return $this->webService->getNotFoundResponse();
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $redirectUrl);
    }

    /**
     * Customer returns here from TrueLayer's Hosted Payments Page — see
     * this class's own docblock for why the invoice is resolved from the
     * `payment_id` query parameter TrueLayer appends, not a route
     * parameter, and for why this action also calls
     * `markInvoicePaidIfVerified()` directly rather than only being
     * read-only.
     */
    public function trueLayerComplete(Request $request): Response
    {
        $queryParams = $request->getQueryParams();
        $paymentId = (string) ($queryParams['payment_id'] ?? '');
        $urlKey = $paymentId !== '' ? $this->trueLayerPaymentService->resolveInvoiceUrlKey($paymentId) : null;
        $invoice = $urlKey !== null ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        if (null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

        // See this class's own docblock — synchronous fallback for when
        // TrueLayer's webhook delivery is delayed or paused.
        $this->trueLayerWebhookHandler->markInvoicePaidIfVerified($paymentId, $urlKey ?? '');

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
                    'gateway' => 'TrueLayer',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['truelayer'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function trueLayerWebhook(Request $request): Response
    {
        return $this->trueLayerWebhookHandler->handle($request);
    }

    private function loadInvoice(CurrentRoute $currentRoute): Response|Inv
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $invoice = null !== $urlKey ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        return $invoice ?? $this->webService->getNotFoundResponse();
    }
}
