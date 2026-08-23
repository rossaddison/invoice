<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\WorldpayMerchant\WorldpayMerchant;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\PaymentInformation\Service\WorldpayPaymentService;
use App\Invoice\PaymentInformation\Service\WorldpayWebhookHandler;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Traits\FlashMessage;
use App\Invoice\WorldpayMerchant\WorldpayMerchantRepository;
use App\Invoice\WorldpayMerchant\WorldpayMerchantService;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Kept separate from PaymentInformationController deliberately — same
 * reasoning as AdyenPaymentController: that controller is already at
 * SonarQube's php:S1448 method-count ceiling.
 *
 * Unlike every redirect/Drop-in-driven gateway already in this app,
 * Worldpay's Checkout SDK + mandatory 3DS flow needs genuine AJAX
 * round-trips between this app's own frontend and backend mid-payment
 * (create payment -> maybe supply device data -> maybe complete a
 * challenge), not just a single session-creation call followed by the
 * gateway's own servers taking over. See payment-worldpay.ts for the
 * client-side half of this exchange.
 *
 * Persistence timing differs from every other gateway too: a
 * provisional WorldpayMerchant row is written here, synchronously,
 * immediately after a successful createPayment() call — before this
 * app can possibly know the final outcome — because Worldpay's
 * `_links.self.href` can't be reconstructed later from anything a
 * webhook payload carries. WorldpayWebhookHandler is what flips that
 * row to confirmed; see its own docblock and WorldpayMerchant's.
 */
final class WorldpayPaymentController
{
    use FlashMessage;

    public function __construct(
        private WorldpayPaymentService $worldpayPaymentService,
        private WorldpayWebhookHandler $worldpayWebhookHandler,
        private WorldpayMerchantService $worldpayMerchantService,
        private WorldpayMerchantRepository $worldpayMerchantRepository,
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
        private DataResponseFactoryInterface $factory,
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
            ['flash' => $this->flash, 'errors' => []],
        );
    }

    public function worldpayInForm(CurrentRoute $currentRoute, iiR $iiR): Response
    {
        $ctx = $this->loadWorldpayInvoiceContext($currentRoute, $iiR);
        if ($ctx instanceof Response) {
            return $ctx;
        }

        if (!$this->worldpayPaymentService->isConfigured()) {
            $this->flashMessage('warning', 'Worldpay payment gateway is not properly configured.');
            return $this->webService->getNotFoundResponse();
        }

        $invoice = $ctx['invoice'];
        $client = $invoice->getClient();

        return $this->webViewRenderer->render('payment_information_worldpay_pci', [
            'alert' => $this->alert(),
            'balance' => $ctx['balance'],
            'client_on_invoice' => $this->cR->repoClientquery($invoice->reqClientId()),
            'disable_form' => $ctx['disable_form'],
            'invoice' => $invoice,
            'inv_url_key' => $ctx['url_key'],
            'is_overdue' => $ctx['balance'] > 0.00
                && strtotime($invoice->getDateDue()->format('Y-m-d')) < time(),
            'json_encoded_items' => Json::encode($ctx['items_array']),
            'partial_client_address' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/client/partial_client_address',
                ['client' => $client],
            ),
            'total' => $ctx['total'],
            'companyLogo' => $this->logoRenderer->companyLogo(),
            'worldpayLogo' => $this->logoRenderer->worldpayLogo(),
            'worldpayCheckoutId' => $this->sR->getSetting('gateway_worldpay_entity') ?: '',
            'worldpayEnvironment' => $this->worldpayPaymentService->isSandbox() ? 'test' : 'live',
            'worldpayCurrency' => strtoupper($this->sR->getSetting('currency_code') ?: 'GBP'),
            'worldpayAmount' => $ctx['balance'],
            'worldpayCreatePaymentUrl' => $this->urlGenerator->generateAbsolute(
                'paymentinformation/worldpayCreatePayment',
                ['url_key' => $ctx['url_key']],
            ),
            'worldpaySupply3dsDeviceDataUrl' => $this->urlGenerator->generateAbsolute(
                'paymentinformation/worldpaySupply3dsDeviceData',
                ['url_key' => $ctx['url_key']],
            ),
            'worldpayCompleteUrl' => $this->urlGenerator->generateAbsolute(
                'paymentinformation/worldpayComplete',
                ['url_key' => $ctx['url_key']],
            ),
            'title' => 'Worldpay - PCI Compliant - is enabled. ',
        ]);
    }

    /**
     * First half of worldpayInForm()'s guard-clause chain — split out
     * purely to keep both this method and worldpayInForm() itself
     * under SonarQube's php:S1142 return-count cap (3), matching
     * AdyenPaymentController::loadAdyenInvoiceContext() exactly.
     *
     * @return Response|array{url_key: string, invoice: Inv, balance: float,
     *     total: float, disable_form: bool, items_array: list<string>}
     */
    private function loadWorldpayInvoiceContext(CurrentRoute $currentRoute, iiR $iiR): Response|array
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
        $total = $invoice_amount_record->getTotal();
        $disable_form = false;
        if (0.00 == $balance) {
            $this->flashMessage('warning', $this->translator->translate('already.paid'));
            $disable_form = true;
        }
        if ($balance <= 0 || $total <= 0) {
            return $this->webService->getNotFoundResponse();
        }

        return [
            'url_key' => $url_key,
            'invoice' => $invoice,
            'balance' => $balance,
            'total' => $total,
            'disable_form' => $disable_form,
            'items_array' => $items_array,
        ];
    }

    /**
     * AJAX endpoint: the browser has a Checkout SDK sessionHref and
     * POSTs it here as JSON. Calls Worldpay, persists the provisional
     * WorldpayMerchant row immediately (see this class's own
     * docblock), and returns a small JSON envelope the frontend reacts
     * to — either "go to the completion page" or "here's the 3DS
     * device-data step to run next".
     */
    public function worldpayCreatePayment(Request $request, CurrentRoute $currentRoute): Response
    {
        $ctx = $this->resolveWorldpayContextForAjax($currentRoute);
        if ($ctx instanceof Response) {
            return $ctx;
        }

        $outcome = $this->attemptCreatePayment($ctx, $request);
        if ($outcome instanceof Response) {
            return $outcome;
        }

        $this->recordProvisional($ctx['invoice'], $outcome['transactionReference'], $outcome['result']);

        return $this->factory->createResponse(Json::encode([
            'outcome' => $outcome['result']['outcome'],
            'deviceDataCollection' => $outcome['result']['deviceDataCollection'],
        ]));
    }

    /**
     * Second half of worldpayCreatePayment()'s guard-clause chain — see
     * loadWorldpayInvoiceContext() docblock for the general reasoning.
     *
     * @param array{url_key: string, invoice: Inv, balance: float} $ctx
     * @return Response|array{transactionReference: string, result: array{
     *     outcome: string, paymentId: string, transactionReference: string,
     *     selfHref: ?string, deviceDataCollection: ?array{bin: string, jwt: string, url: string},
     *     supply3dsDeviceDataHref: ?string, raw: array<array-key, mixed>,
     * }}
     */
    private function attemptCreatePayment(array $ctx, Request $request): Response|array
    {
        /**
         * @var array{
         *     sessionHref?: string, cardHolderName?: string,
         *     billingAddress?: array{
         *         address1?: string, address2?: string, address3?: string,
         *         city?: string, postalCode?: string, state?: string, countryCode?: string,
         *     },
         * } $payload
         */
        $payload = (array) Json::decode((string) $request->getBody());
        $sessionHref = $payload['sessionHref'] ?? '';
        if ($sessionHref === '') {
            return $this->factory->createResponse(Json::encode(['outcome' => 'error', 'message' => 'Missing session.']));
        }
        $billing = $payload['billingAddress'] ?? [];

        $transactionReference = 'inv-' . $ctx['invoice']->reqId() . '-' . bin2hex(random_bytes(8));
        $result = $this->worldpayPaymentService->createPayment(
            transactionReference: $transactionReference,
            sessionHref: $sessionHref,
            cardHolderName: $payload['cardHolderName'] ?? '',
            billingAddress: [
                'address1' => $billing['address1'] ?? '',
                'address2' => $billing['address2'] ?? '',
                'address3' => $billing['address3'] ?? '',
                'city' => $billing['city'] ?? '',
                'postalCode' => $billing['postalCode'] ?? '',
                'state' => $billing['state'] ?? '',
                'countryCode' => $billing['countryCode'] ?? '',
            ],
            amount: $ctx['balance'],
            currency: $this->sR->getSetting('currency_code') ?: 'GBP',
            narrativeLine1: $this->worldpayPaymentService->tradingName() ?: 'Invoice',
            threeDsReturnUrl: $this->urlGenerator->generateAbsolute(
                'paymentinformation/worldpayComplete',
                ['url_key' => $ctx['url_key']],
            ),
        );
        if (null === $result) {
            return $this->factory->createResponse(Json::encode(['outcome' => 'error', 'message' => 'Unable to reach Worldpay.']));
        }

        return ['transactionReference' => $transactionReference, 'result' => $result];
    }

    /**
     * AJAX endpoint: the browser captured a Device Data Collection
     * `postMessage` result and POSTs it here. Calls Worldpay's
     * supply3dsDeviceData action, updates the provisional row's
     * pending_action_href for whatever comes next, and returns the
     * outcome (possibly a 3dsChallenged payload) to the frontend.
     */
    public function worldpaySupply3dsDeviceData(Request $request, CurrentRoute $currentRoute): Response
    {
        $ctx = $this->resolveWorldpayContextForAjax($currentRoute);
        if ($ctx instanceof Response) {
            return $ctx;
        }

        $outcome = $this->attemptSupply3dsDeviceData($ctx, $request);
        if ($outcome instanceof Response) {
            return $outcome;
        }

        $this->updatePendingAction($outcome['worldpayMerchant'], $outcome['data']);

        return $this->factory->createResponse(Json::encode([
            'outcome' => $outcome['data']['outcome'] ?? '',
            'challenge' => $outcome['data']['challenge'] ?? null,
        ]));
    }

    /**
     * Second half of worldpaySupply3dsDeviceData()'s guard-clause chain
     * — see loadWorldpayInvoiceContext() docblock for the general
     * reasoning.
     *
     * @param array{url_key: string, invoice: Inv, balance: float} $ctx
     * @return Response|array{worldpayMerchant: WorldpayMerchant, data: array{
     *     outcome?: string, challenge?: array{reference?: string, url?: string, jwt?: string, payload?: string},
     *     _actions?: array{complete3dsChallenge?: array{href?: string}},
     * }}
     */
    private function attemptSupply3dsDeviceData(array $ctx, Request $request): Response|array
    {
        $worldpayMerchant = $this->worldpayMerchantRepository
            ->repoWorldpayMerchantLatestByInvId($ctx['invoice']->reqId());
        $actionHref = $worldpayMerchant?->getPendingActionHref() ?? '';
        if (null === $worldpayMerchant || '' === $actionHref) {
            return $this->factory->createResponse(Json::encode(['outcome' => 'error', 'message' => 'No pending 3DS action.']));
        }

        /** @var array{collectionReference?: string} $payload */
        $payload = (array) Json::decode((string) $request->getBody());
        /** @var array{outcome?: string, challenge?: array{reference?: string, url?: string, jwt?: string, payload?: string}, _actions?: array{complete3dsChallenge?: array{href?: string}}}|null $data */
        $data = $this->worldpayPaymentService->supply3dsDeviceData($actionHref, $payload['collectionReference'] ?? '');
        if (null === $data) {
            return $this->factory->createResponse(Json::encode(['outcome' => 'error', 'message' => 'Unable to reach Worldpay.']));
        }

        return ['worldpayMerchant' => $worldpayMerchant, 'data' => $data];
    }

    /**
     * Read-only for every outcome except one: if a 3DS challenge is
     * still pending against this invoice (Cardinal's ACS redirects/
     * posts the browser back here after the customer completes the
     * challenge — see Challenge.returnUrl on the original request),
     * this calls complete3dsChallenge() server-side before rendering,
     * matching the plan's design. It never itself marks the invoice
     * paid either way — that write happens exclusively in
     * WorldpayWebhookHandler, same reasoning as
     * AdyenPaymentController::adyenComplete().
     */
    public function worldpayComplete(CurrentRoute $currentRoute): Response
    {
        $url_key = $currentRoute->getArgument('url_key');
        $invoice = null !== $url_key ? $this->iR->repoUrlKeyGuestLoaded($url_key) : null;
        if (null === $url_key || null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

        $this->resumeChallengeIfPending($invoice);

        return $this->renderCompletionPage($invoice, $url_key);
    }

    private function resumeChallengeIfPending(Inv $invoice): void
    {
        $worldpayMerchant = $this->worldpayMerchantRepository
            ->repoWorldpayMerchantLatestByInvId($invoice->reqId());
        $actionHref = $worldpayMerchant?->getPendingActionHref() ?? '';
        if (null === $worldpayMerchant || '' === $actionHref) {
            return;
        }

        $data = $this->worldpayPaymentService->complete3dsChallenge($actionHref);
        if (null !== $data) {
            $this->updatePendingAction($worldpayMerchant, $data);
        }
    }

    private function renderCompletionPage(Inv $invoice, string $url_key): Response
    {
        $invoiceNumber = $invoice->getNumber() ?? 'unknown';
        /** @var InvAmount $invoice_amount_record */
        $invoice_amount_record = $this->iaR->repoInvquery($invoice->reqId());
        $isPaid = 0.00 === $invoice_amount_record->getBalance();
        $sandboxUrlArray = $this->sR->sandboxUrlArray();

        $heading = $isPaid
            ? sprintf($this->translator->translate('online.payment.payment.successful'), $invoiceNumber)
            : sprintf($this->translator->translate('online.payment.payment.processing'), $invoiceNumber);

        return $this->webViewRenderer->render('payment_completion_page', [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/paymentinformation/payment_message',
                [
                    'heading' => $heading,
                    'message' => $this->translator->translate('payment')
                        . ':' . $this->translator->translate('complete'),
                    'url' => 'inv/urlKey',
                    'url_key' => $url_key,
                    'gateway' => 'Worldpay',
                    'sandbox_url' => $sandboxUrlArray['worldpay'] ?? '',
                ],
            ),
        ]);
    }

    /**
     * @return Response|array{url_key: string, invoice: Inv, balance: float}
     */
    private function resolveWorldpayContextForAjax(CurrentRoute $currentRoute): Response|array
    {
        $url_key = $currentRoute->getArgument('url_key');
        $invoice = null !== $url_key ? $this->iR->repoUrlKeyGuestLoaded($url_key) : null;
        if (null === $url_key || null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

        /** @var InvAmount $invoice_amount_record */
        $invoice_amount_record = $this->iaR->repoInvquery($invoice->reqId());

        return ['url_key' => $url_key, 'invoice' => $invoice, 'balance' => $invoice_amount_record->getBalance() ?? 0.00];
    }

    /**
     * @param array{
     *     outcome: string, paymentId: string, transactionReference: string,
     *     selfHref: ?string, deviceDataCollection: ?array{bin: string, jwt: string, url: string},
     *     supply3dsDeviceDataHref: ?string, raw: array<array-key, mixed>,
     * } $result
     */
    private function recordProvisional(Inv $invoice, string $transactionReference, array $result): void
    {
        $this->worldpayMerchantService->saveWorldpayMerchantViaPaymentHandler(
            new WorldpayMerchant(),
            [
                'inv_id' => $invoice->reqId(),
                'merchant_response_successful' => false,
                'merchant_response_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'merchant_response' => 'Worldpay payment initiated: ' . $result['outcome'],
                'merchant_response_reference' => ($invoice->getNumber() ?? '') . '-' . $transactionReference,
                'merchant_response_transaction_reference' => $transactionReference,
                'merchant_response_payment_id' => $result['paymentId'],
                'merchant_response_self_href' => $result['selfHref'],
                'merchant_response_pending_action_href' => $result['supply3dsDeviceDataHref'],
            ],
        );
    }

    /**
     * @param array{_actions?: array{complete3dsChallenge?: array{href?: string}}, ...} $data
     */
    private function updatePendingAction(WorldpayMerchant $worldpayMerchant, array $data): void
    {
        $nextHref = $data['_actions']['complete3dsChallenge']['href'] ?? null;
        $worldpayMerchant->setPendingActionHref($nextHref);
        $this->worldpayMerchantRepository->save($worldpayMerchant);
    }

    public function worldpayWebhook(Request $request): Response
    {
        return $this->worldpayWebhookHandler->handle($request);
    }
}
