<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\GoCardlessPaymentService;
use App\Invoice\PaymentInformation\Service\GoCardlessWebhookHandler;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use DateTimeImmutable;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Security\Random;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Kept separate from PaymentInformationController deliberately — same
 * reasoning as AdyenPaymentController (see that class's docblock): the
 * shared controller is already at SonarQube's php:S1448 method-count
 * ceiling, and a redirect can't carry loaded context across requests anyway.
 *
 * Unlike every other gateway here, GoCardless's own hosted pages ARE the
 * entire "in-form" step — there's no local page to render at all, just a
 * 302 straight to GoCardless. Setup happens once per invoice (no mandate
 * reuse across invoices yet — a client's recurring invoices each still
 * complete their own redirect flow; a real "already has a mandate, skip
 * straight to scheduling" path is a follow-up, not built here).
 */
final class GoCardlessPaymentController
{
    use FlashMessage;

    private const string SESSION_TOKEN_KEY = 'gocardless_session_token';

    /**
     * GoCardless enforces its own minimum charge-date lead time per payment
     * scheme (e.g. BACS) and rejects an earlier date outright — this is a
     * conservative default clamp so the common case doesn't round-trip a
     * validation error first. date_due is used as-is whenever it's already
     * further out than this.
     */
    private const int MINIMUM_LEAD_DAYS = 5;

    public function __construct(
        private GoCardlessPaymentService $goCardlessPaymentService,
        private GoCardlessWebhookHandler $goCardlessWebhookHandler,
        private iR $iR,
        private iaR $iaR,
        private sR $sR,
        private ResponseFactoryInterface $responseFactory,
        private SessionInterface $session,
        private Translator $translator,
        private UrlGenerator $urlGenerator,
        private UserService $userService,
        private WebViewRenderer $webViewRenderer,
        private WebControllerService $webService,
        private Flash $flash,
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
     * Starts the redirect flow and sends the customer straight to
     * GoCardless's hosted mandate-setup pages — there's nothing of ours to
     * render first.
     */
    public function goCardlessInForm(CurrentRoute $currentRoute): Response
    {
        $ctx = $this->loadInvoiceContext($currentRoute);
        if ($ctx instanceof Response) {
            return $ctx;
        }
        if (!$this->goCardlessPaymentService->isConfigured()) {
            $this->flashMessage('warning', 'GoCardless payment gateway is not properly configured.');
            return $this->webService->getNotFoundResponse();
        }

        [$urlKey, $invoice] = $ctx;
        $sessionToken = Random::string(32);
        $this->session->set(self::SESSION_TOKEN_KEY, $sessionToken);
        $this->session->set(self::SESSION_TOKEN_KEY . '_url_key', $urlKey);

        $successRedirectUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/goCardlessComplete',
            ['url_key' => $urlKey, '_language' => 'en'],
        );
        $flow = $this->goCardlessPaymentService->createRedirectFlow(
            'Invoice ' . ($invoice->getNumber() ?? $urlKey),
            $sessionToken,
            $successRedirectUrl,
        );

        return $this->responseFactory->createResponse(302)->withHeader('Location', $flow['redirect_url']);
    }

    /**
     * Customer returns here from GoCardless's hosted pages with
     * redirect_flow_id in the query string. Completes the mandate, then
     * immediately schedules the actual Direct Debit collection against it —
     * GoCardless payments aren't synchronous, so the invoice is only
     * actually marked paid later, by GoCardlessWebhookHandler.
     */
    public function goCardlessComplete(CurrentRoute $currentRoute, Request $request): Response
    {
        $ctx = $this->loadInvoiceContext($currentRoute);
        if ($ctx instanceof Response) {
            return $ctx;
        }
        [$urlKey, $invoice] = $ctx;

        $redirectFlowId = (string) ($request->getQueryParams()['redirect_flow_id'] ?? '');
        /** @var string|null $sessionToken */
        $sessionToken = $this->session->get(self::SESSION_TOKEN_KEY);
        /** @var string|null $sessionUrlKey */
        $sessionUrlKey = $this->session->get(self::SESSION_TOKEN_KEY . '_url_key');
        if ($redirectFlowId === '' || null === $sessionToken || $sessionUrlKey !== $urlKey) {
            $this->flashMessage('warning', 'GoCardless session expired — please try again.');
            return $this->webService->getNotFoundResponse();
        }

        $mandate = $this->goCardlessPaymentService->completeRedirectFlow($redirectFlowId, $sessionToken);
        $this->scheduleCollection($invoice, $urlKey, $mandate['mandate_id']);

        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/paymentinformation/payment_message',
                [
                    'heading' => sprintf(
                        $this->translator->translate('online.payment.payment.processing'),
                        $invoice->getNumber() ?? 'unknown',
                    ),
                    'message' => $this->translator->translate('payment')
                        . ':' . $this->translator->translate('complete'),
                    'url' => 'inv/urlKey',
                    'url_key' => $urlKey,
                    'gateway' => 'GoCardless',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['gocardless'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function goCardlessWebhook(Request $request): Response
    {
        return $this->goCardlessWebhookHandler->handle($request);
    }

    private function scheduleCollection(Inv $invoice, string $urlKey, string $mandateId): void
    {
        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        $chargeDate = $this->resolveChargeDate($invoice->getDateDue());
        $currency = !empty($this->sR->getSetting('currency_code'))
            ? $this->sR->getSetting('currency_code')
            : 'GBP';

        $payment = $this->goCardlessPaymentService->scheduleDirectDebitPayment(
            $mandateId,
            $invoiceAmountRecord->getBalance() ?? 0.00,
            $currency,
            $chargeDate,
            ['invoice_url_key' => $urlKey],
        );

        $invoice->setDirectDebitDate(new DateTimeImmutable($payment['charge_date']));
        $invoice->setPaymentMethod(6);
        $this->iR->save($invoice);
    }

    private function resolveChargeDate(DateTimeImmutable $dateDue): DateTimeImmutable
    {
        $earliest = new DateTimeImmutable()->modify('+' . self::MINIMUM_LEAD_DAYS . ' days');
        return $dateDue > $earliest ? $dateDue : $earliest;
    }

    /**
     * @return Response|array{0: string, 1: Inv}
     */
    private function loadInvoiceContext(CurrentRoute $currentRoute): Response|array
    {
        $url_key = $currentRoute->getArgument('url_key');
        $invoice = null !== $url_key ? $this->iR->repoUrlKeyGuestLoaded($url_key) : null;
        if (null === $url_key || null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        if (($invoiceAmountRecord->getBalance() ?? 0.00) <= 0.00) {
            $this->flashMessage('warning', $this->translator->translate('already.paid'));
            return $this->webService->getNotFoundResponse();
        }

        return [$url_key, $invoice];
    }
}
