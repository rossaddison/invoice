<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\PaymentInformation\Service\GoCardlessMandateCollectionService;
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
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class GoCardlessPaymentController
{
    use FlashMessage;

    private const string BILLING_REQUEST_ID_KEY = 'gocardless_billing_request_id';
    private const string BILLING_REQUEST_FLOW_ID_KEY = 'gocardless_billing_request_flow_id';
    private const string BILLING_REQUEST_URL_KEY = 'gocardless_billing_request_url_key';

    public function __construct(
        private GoCardlessPaymentService $goCardlessPaymentService,
        private GoCardlessWebhookHandler $goCardlessWebhookHandler,
        private GoCardlessMandateCollectionService $mandateCollectionService,
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

    public function goCardlessInForm(CurrentRoute $currentRoute): Response
    {
        $ctx = $this->loadInvoiceContext($currentRoute);

        if ($ctx instanceof Response) {
            return $ctx;
        }

        if (!$this->goCardlessPaymentService->isConfigured()) {
            $this->flashMessage(
                'warning',
                'GoCardless payment gateway is not properly configured.',
            );

            return $this->webService->getNotFoundResponse();
        }

        [$urlKey] = $ctx;

        $successRedirectUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/goCardlessComplete',
            ['url_key' => $urlKey],
        );

        $flow = $this->goCardlessPaymentService->createBillingRequestFlow(
            $urlKey,
            $successRedirectUrl,
        );

        $this->session->set(
            self::BILLING_REQUEST_ID_KEY,
            $flow['billing_request_id'],
        );
        $this->session->set(
            self::BILLING_REQUEST_FLOW_ID_KEY,
            $flow['billing_request_flow_id'],
        );
        $this->session->set(self::BILLING_REQUEST_URL_KEY, $urlKey);

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $flow['redirect_url']);
    }

    public function goCardlessComplete(CurrentRoute $currentRoute, Request $request): Response
    {
        $ctx = $this->loadInvoiceContext($currentRoute);

        if ($ctx instanceof Response) {
            return $ctx;
        }

        [$urlKey, $invoice] = $ctx;

        if ($invoice->getDirectDebitDate() === null) {
            $queryParams = $request->getQueryParams();

            $billingRequestId = (string) ($queryParams['billing_request_id'] ?? '');
            $billingRequestFlowId = (string) ($queryParams['billing_request_flow_id'] ?? '');

            /** @var string|null $sessionBillingRequestId */
            $sessionBillingRequestId = $this->session->get(self::BILLING_REQUEST_ID_KEY);

            /** @var string|null $sessionBillingRequestFlowId */
            $sessionBillingRequestFlowId = $this->session->get(self::BILLING_REQUEST_FLOW_ID_KEY);

            /** @var string|null $sessionUrlKey */
            $sessionUrlKey = $this->session->get(self::BILLING_REQUEST_URL_KEY);

            if (
                $billingRequestId === ''
                || $billingRequestFlowId === ''
                || $sessionBillingRequestId === null
                || $sessionBillingRequestFlowId === null
                || $sessionUrlKey === null
                || !hash_equals($sessionBillingRequestId, $billingRequestId)
                || !hash_equals($sessionBillingRequestFlowId, $billingRequestFlowId)
                || !hash_equals($sessionUrlKey, $urlKey)
            ) {
                $this->flashMessage(
                    'warning',
                    'GoCardless session expired — please try again.',
                );

                return $this->webService->getNotFoundResponse();
            }

            $processedInvoice = $this->mandateCollectionService
                ->scheduleFromBillingRequest($billingRequestId);

            if ($processedInvoice === null) {
                $this->flashMessage(
                    'warning',
                    'GoCardless authorisation has not yet been completed. Please try again shortly.',
                );

                return $this->webService->getNotFoundResponse();
            }

            $invoice = $processedInvoice;

            $this->clearBillingRequestSession();
        } else {
            $this->clearBillingRequestSession();
        }

        $directDebitDate = $invoice->getDirectDebitDate();

        $message = $directDebitDate instanceof DateTimeImmutable
            ? sprintf(
                $this->translator->translate(
                    'gocardless.direct.debit.collection.scheduled.for',
                ),
                $directDebitDate->format('jS F Y'),
            )
            : $this->translator->translate('payment')
                . ':'
                . $this->translator->translate('complete');

        $viewData = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/paymentinformation/payment_message',
                [
                    'heading' => sprintf(
                        $this->translator->translate('online.payment.payment.processing'),
                        $invoice->getNumber() ?? 'unknown',
                    ),
                    'message' => $message,
                    'url' => 'inv/urlKey',
                    'url_key' => $urlKey,
                    'gateway' => 'GoCardless',
                    'sandbox_url' => $this->sR->sandboxUrlArray()['gocardless'],
                ],
            ),
        ];

        return $this->webViewRenderer->render(
            'payment_completion_page',
            $viewData,
        );
    }

    public function goCardlessWebhook(Request $request): Response
    {
        return $this->goCardlessWebhookHandler->handle($request);
    }

    private function clearBillingRequestSession(): void
    {
        $this->session->remove(self::BILLING_REQUEST_ID_KEY);
        $this->session->remove(self::BILLING_REQUEST_FLOW_ID_KEY);
        $this->session->remove(self::BILLING_REQUEST_URL_KEY);
    }

    /**
     * @return Response|array{0: string, 1: Inv}
     */
    private function loadInvoiceContext(CurrentRoute $currentRoute): Response|array
    {
        $urlKey = $currentRoute->getArgument('url_key');

        $invoice = $urlKey !== null
            ? $this->iR->repoUrlKeyGuestLoaded($urlKey)
            : null;

        if ($urlKey === null || $invoice === null) {
            return $this->webService->getNotFoundResponse();
        }

        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());

        if (($invoiceAmountRecord->getBalance() ?? 0.00) <= 0.00) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('already.paid'),
            );

            return $this->webService->getNotFoundResponse();
        }

        return [$urlKey, $invoice];
    }
}
