<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use Adyen\Model\Checkout\CreateCheckoutSessionResponse;
use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\PaymentMethod\PaymentMethod;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Helpers\CountryHelper;
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
        $ctx = $this->loadAdyenInvoiceContext($currentRoute, $iiR);
        if ($ctx instanceof Response) {
            return $ctx;
        }

        $sessionCtx = $this->createAdyenSession($ctx, $pmR);
        if ($sessionCtx instanceof Response) {
            return $sessionCtx;
        }

        $invoice = $ctx['invoice'];
        $client = $invoice->getClient();
        $paymentMethodName = $sessionCtx['payment_method_for_this_invoice']?->getName();

        return $this->webViewRenderer->render('payment_information_adyen_pci', [
            'alert'                  => $this->alert(),
            'balance'                => $ctx['balance'],
            'client_on_invoice'      => $this->cR->repoClientquery($invoice->reqClientId()),
            'disable_form'           => $ctx['disable_form'],
            'invoice'                => $invoice,
            'inv_url_key'            => $ctx['url_key'],
            'is_overdue'             => $sessionCtx['is_overdue'],
            'json_encoded_items'     => Json::encode($ctx['items_array']),
            'partial_client_address' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/client/partial_client_address',
                ['client' => $client],
            ),
            'payment_method'      => ($paymentMethodName !== null && $paymentMethodName !== '') ? $paymentMethodName : 'None',
            'total'               => $ctx['total'],
            'companyLogo'         => $this->logoRenderer->companyLogo(),
            'adyenLogo'           => $this->logoRenderer->adyenLogo(),
            'adyenClientKey'      => $this->adyenPaymentService->getClientKey(),
            'adyenSessionId'      => $sessionCtx['session']->getId(),
            'adyenSessionData'    => $sessionCtx['session']->getSessionData(),
            'adyenEnvironment'    => $this->adyenPaymentService->isSandbox() ? 'test' : 'live',
            'adyenCountryCode'    => $sessionCtx['country_code'],
            'title'               => 'Adyen - PCI Compliant - is enabled. ',
        ]);
    }

    /**
     * Adyen Web v6 made countryCode a mandatory AdyenCheckout() config
     * field. The client's country is stored as a free-text country name
     * (see ClientForm/Client::getClientCountry()), not an ISO code, so it's
     * resolved via the same league/iso3166 lookup CountryHelper already
     * uses elsewhere (PeppolHelper's delivery-location code). Falls back to
     * 'GB' — matching this codebase's other GBP-default fallbacks (e.g.
     * AdyenPaymentService::refund()'s currency default) — when the stored
     * name doesn't resolve, rather than sending Adyen an empty/invalid value.
     */
    private function resolveCountryCode(?string $clientCountryName): string
    {
        if ($clientCountryName === null || $clientCountryName === '') {
            return 'GB';
        }
        $code = new CountryHelper()->getCountryIdentificationCodeWithLeague($clientCountryName);
        return $code !== '' ? $code : 'GB';
    }

    /**
     * First half of adyenInForm()'s guard-clause chain — split out purely
     * to keep both this method and adyenInForm() itself under SonarQube's
     * php:S1142 return-count cap (3), not for reuse elsewhere.
     *
     * @return Response|array{url_key: string, invoice: Inv, balance: float,
     *     total: float, disable_form: bool, items_array: list<string>}
     */
    private function loadAdyenInvoiceContext(CurrentRoute $currentRoute, iiR $iiR): Response|array
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

        return [
            'url_key'      => $url_key,
            'invoice'      => $invoice,
            'balance'      => $balance,
            'total'        => $total,
            'disable_form' => $disable_form,
            'items_array'  => $items_array,
        ];
    }

    /**
     * Second half of adyenInForm()'s guard-clause chain — see
     * loadAdyenInvoiceContext() docblock for why this is split out.
     *
     * @param array{url_key: string, invoice: Inv, balance: float,
     *     total: float, disable_form: bool, items_array: list<string>} $ctx
     * @return Response|array{session: CreateCheckoutSessionResponse,
     *     payment_method_for_this_invoice: ?PaymentMethod, is_overdue: bool,
     *     country_code: string}
     */
    private function createAdyenSession(array $ctx, pmR $pmR): Response|array
    {
        if (!$this->adyenPaymentService->isConfigured()) {
            $this->flashMessage('warning', 'Adyen payment gateway is not properly configured.');
            return $this->webService->getNotFoundResponse();
        }

        $countryCode = $this->resolveCountryCode($ctx['invoice']->getClient()?->getClientCountry());
        $yii_invoice_array = [
            'balance'  => $ctx['balance'],
            'currency' => !empty($this->sR->getSetting('currency_code'))
                ? strtolower($this->sR->getSetting('currency_code'))
                : 'gbp',
            'url_key' => $ctx['url_key'],
        ];
        // Explicit '_language' rather than relying on UrlGenerator's global
        // default argument — that default doesn't reliably propagate to
        // this controller since it's only ever reached via a redirect from
        // pciCompliantGatewayInForms(), not a directly-rendered link (same
        // defensive pattern already used by openBankingInForm() elsewhere
        // in this codebase).
        $returnUrl = $this->urlGenerator->generateAbsolute(
            'paymentinformation/adyenComplete',
            ['url_key' => $ctx['url_key']],
        );
        // countryCode must be set on the session itself, not only passed to
        // the front-end AdyenCheckout() config — otherwise Adyen returns
        // payment methods unfiltered by country (e.g. US-only "Pay by Bank"
        // alongside a GB session), and selecting one of those mismatched
        // methods fails at submission with a 422 "Field 'countryCode' is
        // not valid" from /sessions/{id}/payments, since the front-end
        // config value only drives Drop-in's localisation, not which
        // methods the session actually offers.
        $session = $this->adyenPaymentService->createSession($yii_invoice_array, $returnUrl, $countryCode);
        if (null === $session) {
            $this->flashMessage('warning', 'Unable to start an Adyen payment session.');
            return $this->webService->getNotFoundResponse();
        }

        $invoice = $ctx['invoice'];
        return [
            'session'                          => $session,
            'payment_method_for_this_invoice'  => $pmR->repoPaymentMethodquery((int) $invoice->getPaymentMethod()),
            'is_overdue'                        => $ctx['balance'] > 0.00
                && strtotime($invoice->getDateDue()->format('Y-m-d')) < time(),
            'country_code'                      => $countryCode,
        ];
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
