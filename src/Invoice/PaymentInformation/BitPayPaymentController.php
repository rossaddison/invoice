<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\BitPayPaymentService;
use App\Invoice\PaymentInformation\Service\BitPayWebhookHandler;
use App\Invoice\PaymentInformation\Trait\PaymentGatewayGuardTrait;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Kept separate from PaymentInformationController deliberately — same
 * reasoning as every other dedicated gateway controller in this app (the
 * shared controller is already at SonarQube's php:S1448 method-count
 * ceiling).
 *
 * Like Paystack/GoCardless/Robokassa/YooKassa, BitPay's own hosted checkout
 * page IS the entire "in-form" step — there's no local payment-entry view
 * to render, just a 302 straight to the invoice URL
 * `BitPayPaymentService::createPayment()` returns. Unlike TrueLayer,
 * `redirectUrl`/`notificationUrl` are both generated per-invoice here and
 * passed straight through to BitPay at invoice-creation time — BitPay's
 * POS facade has no pre-registration requirement on either URL (confirmed
 * directly against BitPay's own OpenAPI reference), so there's no need for
 * a fixed Setting value the way TrueLayer's Console-registered return_uri
 * requires.
 *
 * `bitPayComplete()` is read-only, the same pattern as
 * `PaystackPaymentController::paystackComplete()`: the invoice is only ever
 * actually marked paid by `BitPayWebhookHandler`, after its own signature
 * check AND authenticated re-confirmation via `GET /invoices/{id}` — never
 * by this redirect, since a customer's browser returning here proves
 * nothing about settlement on its own. This is a deliberately better fit
 * for BitPay than TrueLayer's own synchronous fallback: a BitPay invoice
 * only reaches `complete` once its payment is confirmed on-chain, which
 * routinely takes longer than the customer's browser redirect back — so
 * `payment_message`'s existing "processing" state (rather than a forced
 * synchronous re-check the invoice usually isn't ready to pass yet) is the
 * honest thing to show here.
 */
final class BitPayPaymentController
{
    use FlashMessage;
    use PaymentGatewayGuardTrait;

    public function __construct(
        private readonly BitPayPaymentService $bitPayPaymentService,
        private readonly BitPayWebhookHandler $bitPayWebhookHandler,
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
     * Starts the payment flow and sends the customer straight to BitPay's
     * hosted invoice page — there's nothing of ours to render first.
     */
    public function bitPayInForm(CurrentRoute $currentRoute): Response
    {
        $resolved = $this->resolveConfiguredInvoiceWithBalance($currentRoute, $this->bitPayPaymentService, 'BitPay');
        if ($resolved instanceof Response) {
            return $resolved;
        }
        ['invoice' => $invoice, 'balance' => $balance] = $resolved;

        $urlKey = $invoice->getUrlKey();
        $result = $this->bitPayPaymentService->createPayment(
            $balance,
            $this->sR->getSetting('currency_code') ?: 'GBP',
            $urlKey,
            $this->urlGenerator->generateAbsolute('paymentinformation/bitPayComplete', ['url_key' => $urlKey]),
            $this->urlGenerator->generateAbsolute('paymentinformation/bitPayWebhook'),
            $invoice->getClient()?->getClientEmail() ?? '',
        );
        if (null === $result) {
            $this->flashMessage('warning', 'Unable to start the BitPay payment — please try again shortly.');
            return $this->webService->getNotFoundResponse();
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $result['url']);
    }

    /**
     * Customer returns here from BitPay's hosted invoice page —
     * read-only, see this class's own docblock.
     */
    public function bitPayComplete(CurrentRoute $currentRoute): Response
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
                    'gateway' => 'BitPay',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['bitpay'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function bitPayWebhook(Request $request): Response
    {
        return $this->bitPayWebhookHandler->handle($request);
    }

    private function loadInvoice(CurrentRoute $currentRoute): Response|Inv
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $invoice = null !== $urlKey ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        return $invoice ?? $this->webService->getNotFoundResponse();
    }
}
