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
 * `BitPayPaymentService::createPayment()` returns.
 *
 * `notificationUrl` is fixed (no `{url_key}`, generated straight from the
 * route) since `BitPayWebhookHandler` resolves the invoice from the
 * webhook body's own `orderId` field regardless. `redirectUrl`, by
 * contrast, is ALSO built from a fixed route now — confirmed live
 * 2026-09-05 that BitPay's redirect-URL allow-list (Dashboard > Settings >
 * URL Redirect Allow List) rejects a per-invoice path segment
 * ("invalid redirectURL: url not whitelisted") and doesn't support
 * wildcards either (a `/*`-suffixed entry silently behaved identically to
 * an empty allow-list, i.e. the generic "Account not setup completely
 * yet." reappeared rather than a more specific rejection) — the exact
 * fixed-URL constraint this class's own earlier docblock incorrectly
 * assumed didn't apply here, based only on the OpenAPI schema not
 * mentioning one. `$urlKey` is instead appended as a `?url_key=` query
 * string onto that fixed URL — the working assumption is that allow-list
 * matching, like OAuth redirect_uri matching generally, is on the URL up
 * to the query string; not yet independently re-confirmed against a real
 * BitPay redirect (the exact rejection message above was only seen for
 * path-segment and wildcard variants, not a query-string one). If this
 * assumption turns out wrong too, the fallback is what TrueLayer already
 * does: a fully bare fixed URL with the invoice resolved purely from the
 * webhook, `bitPayComplete()` reduced to a generic (non-invoice-specific)
 * "thanks, check your invoice" page. `bitPayComplete()` reads `url_key`
 * from `$request->getQueryParams()`, not a route argument.
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
 *
 * `bitPayInForm()`'s failure page includes BitPay's own error reason
 * (`BitPayPaymentService::lastErrorMessage()`), not just a generic "please
 * try again" — found live 2026-09-05 that a real sandbox merchant account
 * with an unfinished setup step failed invoice creation with a bare
 * not-found page and zero visible explanation, the exact reason
 * (`"Account not setup completely yet."`) only ever reaching
 * `runtime/logs/app.log`. It renders via
 * `PaymentGatewayGuardTrait::renderGuardFailure()` — the same rendering
 * path that trait's own guards use, rather than a second near-duplicate
 * implementation; see that trait's own docblock for why a real rendered
 * page, not `flashMessage()` + `getNotFoundResponse()`, is needed at all.
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
            $this->urlGenerator->generateAbsolute('paymentinformation/bitPayComplete')
                . '?url_key=' . rawurlencode($urlKey),
            $this->urlGenerator->generateAbsolute('paymentinformation/bitPayWebhook'),
            $invoice->getClient()?->getClientEmail() ?? '',
        );
        if (null === $result) {
            $reason = $this->bitPayPaymentService->lastErrorMessage();
            // Reuses PaymentGatewayGuardTrait's own renderGuardFailure() —
            // the exact same "render a real page instead of a bare
            // getNotFoundResponse()" fix that trait's own guards needed
            // (see that trait's own docblock) applies identically here,
            // so this deliberately doesn't duplicate that rendering logic
            // a second time.
            return $this->renderGuardFailure(
                $invoice,
                $this->bitPayPaymentService,
                'BitPay',
                $reason !== '' ? "BitPay said: \"{$reason}\"" : 'Please try again shortly.',
            );
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $result['url']);
    }

    /**
     * Customer returns here from BitPay's hosted invoice page —
     * read-only, see this class's own docblock.
     */
    public function bitPayComplete(Request $request): Response
    {
        $urlKey = (string) ($request->getQueryParams()['url_key'] ?? '');
        $invoice = $urlKey !== '' ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        if (null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

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
