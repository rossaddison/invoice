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
use Psr\Log\LoggerInterface as Logger;
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
 * `notificationUrl` and `redirectUrl` are BOTH fully fixed and bare — no
 * `{url_key}` path segment, no `?url_key=` query string, nothing at all
 * beyond the route itself. `BitPayWebhookHandler` resolves the invoice
 * from the webhook body's own `orderId` field regardless, so
 * `notificationUrl` never needed anything appended. `redirectUrl` went
 * through two wrong assumptions before landing here, both confirmed wrong
 * live against a real production account (yii3i.online), not guessed:
 * first, that BitPay's POS facade needed no pre-registered redirect URL
 * at all (based only on the OpenAPI schema not mentioning one — BitPay's
 * Dashboard has a "URL Redirect Allow List" the schema never mentions,
 * confirmed live 2026-09-05 when a per-invoice path segment was rejected,
 * "invalid redirectURL: url not whitelisted", and a trailing `/*`
 * wildcard was rejected too, silently reverting to the same generic
 * "Account not setup completely yet." shown for an empty allow-list);
 * second, that appending `?url_key=...` as a query string onto an
 * otherwise-registered fixed URL would still match (by analogy with OAuth
 * redirect_uri matching, which usually ignores the query string) — also
 * confirmed wrong live the same day, identical rejection message, even
 * though the exact bare URL was verified registered correctly. BitPay's
 * allow-list requires a byte-exact match with nothing appended at all, by
 * anyone. Since there is therefore no way to identify which invoice a
 * given visit to `bitPayComplete()` is for, that action is deliberately
 * NOT invoice-specific — a generic page, matching what TrueLayer would
 * have needed too if its own `?payment_id=...` query param (which
 * TrueLayer's own docs confirm it always appends, unlike BitPay) hadn't
 * made a more specific page possible there.
 *
 * `bitPayComplete()` is read-only regardless of any of the above: the
 * invoice is only ever actually marked paid by `BitPayWebhookHandler`,
 * after its own signature check AND authenticated re-confirmation via
 * `GET /invoices/{id}` — never by this redirect, since a customer's
 * browser returning here proves nothing about settlement on its own, and
 * now can't even identify which invoice to re-check regardless. A BitPay
 * invoice only reaches `complete` once its payment is confirmed on-chain,
 * which routinely outlasts the browser redirect back anyway, so a generic
 * "your payment is processing, check your invoice" message is the honest
 * thing to show here even setting the allow-list constraint aside.
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
        private readonly Logger $logger,
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
            $this->urlGenerator->generateAbsolute('paymentinformation/bitPayComplete'),
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
     * Customer returns here from BitPay's hosted invoice page — always
     * generic, see this class's own docblock for why no specific invoice
     * can be identified here at all. Logs whatever query parameters
     * actually arrive (BitPay's own, not one this app appends — see the
     * class docblock) purely for future diagnosis, in case BitPay's
     * redirect does carry something usable that a live visit can reveal
     * without needing to guess again.
     */
    public function bitPayComplete(Request $request): Response
    {
        $this->logger->info('BitPay bitPayComplete reached.', ['query' => $request->getQueryParams()]);

        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/paymentinformation/bitpay_generic_complete',
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
