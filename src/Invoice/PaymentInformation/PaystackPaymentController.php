<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\PaystackPaymentService;
use App\Invoice\PaymentInformation\Service\PaystackWebhookHandler;
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
 * RobokassaPaymentController/YookassaPaymentController (the shared
 * controller is already at SonarQube's php:S1448 method-count ceiling).
 *
 * Like GoCardless/Robokassa/YooKassa, Paystack's own hosted checkout page
 * IS the entire "in-form" step — there's no local card-entry view to
 * render, just a 302 straight to the `authorization_url`
 * `PaystackPaymentService::createPayment()` returns.
 *
 * `paystackComplete()` is read-only, the same pattern as
 * `PaymentInformationController::stripeComplete()`: the invoice is only
 * ever actually marked paid by `PaystackWebhookHandler`, after its own
 * signature check AND authenticated re-confirmation via
 * `GET /transaction/verify/{reference}` — never by this redirect, since a
 * customer's browser returning here proves nothing about payment success
 * on its own. This action only re-reads current balance to decide which
 * message to show.
 */
final class PaystackPaymentController
{
    use FlashMessage;
    use PaymentGatewayGuardTrait;

    public function __construct(
        private readonly PaystackPaymentService $paystackPaymentService,
        private readonly PaystackWebhookHandler $paystackWebhookHandler,
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
     * Starts the payment flow and sends the customer straight to
     * Paystack's hosted checkout page — there's nothing of ours to render
     * first.
     */
    public function paystackInForm(CurrentRoute $currentRoute): Response
    {
        $resolved = $this->resolveConfiguredInvoiceWithBalanceAndEmail($currentRoute);
        if ($resolved instanceof Response) {
            return $resolved;
        }
        ['invoice' => $invoice, 'balance' => $balance, 'email' => $email] = $resolved;

        $urlKey = $invoice->getUrlKey();
        $callbackUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/paystackComplete',
            ['url_key' => $urlKey, '_language' => 'en'],
        );
        $result = $this->paystackPaymentService->createPayment(
            $balance,
            $email,
            $callbackUrl,
            ['invoiceUrlKey' => $urlKey],
        );
        if (null === $result) {
            $this->flashMessage('warning', 'Unable to start the Paystack payment — please try again shortly.');
            return $this->webService->getNotFoundResponse();
        }

        return $this->responseFactory->createResponse(302)->withHeader('Location', $result['authorizationUrl']);
    }

    /**
     * The shared load+isConfigured+balance guard chain (PaymentGatewayGuardTrait)
     * plus one extra Paystack-specific step: it requires a client email
     * address, which its refund/create-payment API needs but no other
     * gateway does.
     *
     * @return Response|array{invoice: Inv, balance: float, email: string}
     */
    private function resolveConfiguredInvoiceWithBalanceAndEmail(CurrentRoute $currentRoute): Response|array
    {
        $resolved = $this->resolveConfiguredInvoiceWithBalance($currentRoute, $this->paystackPaymentService, 'Paystack');
        if ($resolved instanceof Response) {
            return $resolved;
        }

        return $this->requireClientEmail($resolved['invoice'], $resolved['balance']);
    }

    /**
     * @return Response|array{invoice: Inv, balance: float, email: string}
     */
    private function requireClientEmail(Inv $invoice, float $balance): Response|array
    {
        $email = $invoice->getClient()?->getClientEmail() ?? '';
        if ($email === '') {
            $this->flashMessage('warning', 'Paystack requires a client email address on file — please add one before paying online.');
            return $this->webService->getNotFoundResponse();
        }

        return ['invoice' => $invoice, 'balance' => $balance, 'email' => $email];
    }

    /**
     * Customer returns here from Paystack's hosted checkout page —
     * read-only, see this class's own docblock.
     */
    public function paystackComplete(CurrentRoute $currentRoute): Response
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
                    'gateway' => 'Paystack',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['paystack'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function paystackWebhook(Request $request): Response
    {
        return $this->paystackWebhookHandler->handle($request);
    }

    private function loadInvoice(CurrentRoute $currentRoute): Response|Inv
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $invoice = null !== $urlKey ? $this->iR->repoUrlKeyGuestLoaded($urlKey) : null;
        return $invoice ?? $this->webService->getNotFoundResponse();
    }
}
