<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\PaymentInformation\Service\AdyenPaymentService;
use App\Invoice\PaymentInformation\Service\AdyenWebhookHandler;
use App\Invoice\PaymentMethod\PaymentMethodRepository as pmR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Kept separate from PaymentInformationController deliberately: that
 * controller is already at SonarQube's php:S1448 method-count ceiling (20),
 * fixed there earlier the same day this gateway was added. Reached directly
 * — not via the shared paymentinformation/inform/{url_key}/{gateway} route
 * — since a browser redirect can't carry the loaded invoice/session across
 * requests anyway, so routing through the shared controller first would
 * only add a redirect hop with no reuse benefit. The one entry point that
 * *does* still go through the shared controller is
 * PaymentInformationController::pciCompliantGatewayInForms()'s 'Adyen' match
 * arm, which simply redirects here.
 */
final class AdyenPaymentController
{
    use FlashMessage;

    public function __construct(
        private AdyenPaymentService $adyenPaymentService,
        private AdyenWebhookHandler $adyenWebhookHandler,
        private Flash $flash,
        private cR $cR,
        private iR $iR,
        private iaR $iaR,
        private sR $sR,
        private Translator $translator,
        private UrlGenerator $urlGenerator,
        private UserService $userService,
        private WebViewRenderer $webViewRenderer,
        private WebControllerService $webService,
        private PaymentInformationLogoRenderer $logoRenderer,
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

    private function alert(): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash'  => $this->flash,
                'errors' => [],
            ],
        );
    }

    public function adyenInForm(CurrentRoute $currentRoute, iiR $iiR, pmR $pmR): Response
    {
        $url_key = $currentRoute->getArgument('url_key');
        $invoice = null !== $url_key ? $this->iR->repoUrlKeyGuestLoaded($url_key) : null;
        if (null === $url_key || null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }
        $invoice_id = $invoice->reqId();
        /** @var InvAmount $invoice_amount_record */
        $invoice_amount_record = $this->iaR->repoInvquery($invoice_id);

        $items = $iiR->repoInvquery($invoice_id);
        $items_array = [];
        /** @var InvItem $item */
        foreach ($items as $item) {
            $items_array[] = (string) $item->reqId() . ' ' . ($item->getName() ?? '');
        }

        $balance = $invoice_amount_record->getBalance();
        $total   = $invoice_amount_record->getTotal();
        $disable_form = false;
        if (0.00 == $balance) {
            $this->flashMessage('warning', $this->translator->translate('already.paid'));
            $disable_form = true;
        }
        if ($balance <= 0 || $total <= 0) {
            return $this->webService->getNotFoundResponse();
        }

        if (!$this->adyenPaymentService->isConfigured()) {
            $this->flashMessage('warning', 'Adyen payment gateway is not properly configured.');
            return $this->webService->getNotFoundResponse();
        }

        $client = $invoice->getClient();
        $yii_invoice_array = [
            'balance'  => $balance,
            'currency' => !empty($this->sR->getSetting('currency_code'))
                ? strtolower($this->sR->getSetting('currency_code'))
                : 'gbp',
            'url_key' => $url_key,
        ];
        // Explicit '_language' rather than relying on UrlGenerator's global
        // default argument — that default doesn't reliably propagate to
        // this controller since it's only ever reached via a redirect from
        // pciCompliantGatewayInForms(), not a directly-rendered link (same
        // defensive pattern already used by openBankingInForm() elsewhere
        // in this codebase).
        $returnUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/adyenComplete',
            ['url_key' => $url_key, '_language' => 'en'],
        );
        $session = $this->adyenPaymentService->createSession($yii_invoice_array, $returnUrl);
        if (null === $session) {
            $this->flashMessage('warning', 'Unable to start an Adyen payment session.');
            return $this->webService->getNotFoundResponse();
        }

        $payment_method_for_this_invoice = $pmR->repoPaymentMethodquery((int) $invoice->getPaymentMethod());
        $is_overdue = $balance > 0.00 && strtotime($invoice->getDateDue()->format('Y-m-d')) < time();
        $paymentMethodName = $payment_method_for_this_invoice?->getName();

        return $this->webViewRenderer->render('payment_information_adyen_pci', [
            'alert'                  => $this->alert(),
            'balance'                => $balance,
            'client_on_invoice'      => $this->cR->repoClientquery($invoice->reqClientId()),
            'disable_form'           => $disable_form,
            'invoice'                => $invoice,
            'inv_url_key'            => $url_key,
            'is_overdue'             => $is_overdue,
            'json_encoded_items'     => Json::encode($items_array),
            'partial_client_address' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/client/partial_client_address',
                ['client' => $client],
            ),
            'payment_method'      => ($paymentMethodName !== null && $paymentMethodName !== '') ? $paymentMethodName : 'None',
            'total'               => $total,
            'companyLogo'         => $this->logoRenderer->companyLogo(),
            'adyenLogo'           => $this->logoRenderer->adyenLogo(),
            'adyenClientKey'      => $this->adyenPaymentService->getClientKey(),
            'adyenSessionId'      => $session->getId(),
            'adyenSessionData'    => $session->getSessionData(),
            'adyenEnvironment'    => $this->adyenPaymentService->isSandbox() ? 'test' : 'live',
            'title'               => 'Adyen - PCI Compliant - is enabled. ',
        ]);
    }

    /**
     * Read-only: the invoice is marked paid exclusively by
     * AdyenWebhookHandler, driven by an HMAC-signed AUTHORISATION
     * notification. Unlike Stripe there's no reliable "genuinely failed"
     * signal available on the redirect return itself, so this always shows
     * "processing" rather than "paid" until the webhook confirms it —
     * never "failed" from this page alone.
     */
    public function adyenComplete(CurrentRoute $currentRoute): Response
    {
        $url_key = $currentRoute->getArgument('url_key');
        $invoice = null !== $url_key ? $this->iR->repoUrlKeyGuestLoaded($url_key) : null;
        if (null === $url_key || null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

        $invoiceNumber = $invoice->getNumber() ?? 'unknown';
        /** @var InvAmount $invoice_amount_record */
        $invoice_amount_record = $this->iaR->repoInvquery($invoice->reqId());
        $isPaid = 0.00 === $invoice_amount_record->getBalance();
        $sandboxUrlArray = $this->sR->sandboxUrlArray();

        $heading = $isPaid
            ? sprintf($this->translator->translate('online.payment.payment.successful'), $invoiceNumber)
            : sprintf($this->translator->translate('online.payment.payment.processing'), $invoiceNumber);

        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/paymentinformation/payment_message',
                [
                    'heading'     => $heading,
                    'message'     => $this->translator->translate('payment')
                        . ':' . $this->translator->translate('complete'),
                    'url'         => 'inv/urlKey',
                    'url_key'     => $url_key,
                    'gateway'     => 'Adyen',
                    'sandbox_url' => $sandboxUrlArray['adyen'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function adyenWebhook(Request $request): Response
    {
        return $this->adyenWebhookHandler->handle($request);
    }
}
