<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Auth\Permissions;
use App\Invoice\Client\ClientRepository as cR;
// Helpers
use App\Invoice\Company\CompanyRepository as compR;
// Entities
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
// Libraries
use App\Invoice\Helpers\DateHelper;
// Psr
use App\Invoice\Inv\InvPaymentSettlementService;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
// Repositories
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\Libraries\Crypt;
use App\Invoice\PaymentInformation\PaymentInformationLogoRenderer;
use App\Invoice\PaymentInformation\PaymentInformationQueryHelper;
use App\Invoice\PaymentInformation\Service\AmazonPayPaymentService;
use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\PaymentInformation\Service\OnlinePaymentRecorderService;
use App\Invoice\PaymentInformation\Service\OpenBankingPaymentService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\PaymentInformation\Service\StripeWebhookHandler;
use App\Invoice\PaymentMethod\PaymentMethodRepository as pmR;
// Services
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\PaymentInformation\Trait\TinkPaymentTrait;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use Mollie\Api\Exceptions\ApiException as MollieException;
use Mollie\Api\MollieApiClient as MollieClient;
use Mollie\Api\Resources\Payment as MolliePayment;
use Psr\Http\Message\ResponseInterface as Response;
// Yiisoft
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Stripe\Stripe;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface as Translator;
use RossAddison\OpenBankingClient\OpenBanking;
use RossAddison\OpenBankingClient\OpenBankingProviderRegistryInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class PaymentInformationController
{
    use FlashMessage;
    use TinkPaymentTrait;

    private string $telegramToken;

    public function __construct(
        private DataResponseFactoryInterface $factory,
        private Flash $flash,
        private AmazonPayPaymentService $amazonPayPaymentService,
        private BraintreePaymentService $braintreePaymentService,
        private StripePaymentService $stripePaymentService,
        private StripeWebhookHandler $stripeWebhookHandler,
        private OnlinePaymentRecorderService $paymentRecorder,
        private OpenBankingPaymentService $openBankingPaymentService,
        private OpenBanking $openBankingOauthClient,
        private OpenBankingProviderRegistryInterface $openBankingProviderRegistry,
        private Session $session,
        private iaR $iaR,
        private iR $iR,
        private InvPaymentSettlementService $invPaymentSettlementService,
        private sR $sR,
        private UrlGenerator $urlGenerator,
        private UserService $userService,
        private Translator $translator,
        private WebViewRenderer $webViewRenderer,
        private WebControllerService $webService,
        private compR $compR,
        private Logger $logger,
        private PaymentInformationLogoRenderer $logoRenderer,
    ) {
        $this->factory                   = $factory;
        $this->flash                     = $flash;
        $this->amazonPayPaymentService   = $amazonPayPaymentService;
        $this->braintreePaymentService   = $braintreePaymentService;
        $this->stripePaymentService      = $stripePaymentService;
        $this->stripeWebhookHandler      = $stripeWebhookHandler;
        $this->paymentRecorder           = $paymentRecorder;
        $this->openBankingPaymentService = $openBankingPaymentService;
        $this->openBankingOauthClient    = $openBankingOauthClient;
        $this->openBankingProviderRegistry = $openBankingProviderRegistry;
        $this->session                   = $session;
        $this->iaR                       = $iaR;
        $this->iR                        = $iR;
        $this->sR                        = $sR;
        $this->urlGenerator              = $urlGenerator;
        $this->userService               = $userService;
        $this->translator                = $translator;
        $this->webViewRenderer           = $webViewRenderer;
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && !$this->userService->hasPermission(Permissions::EDIT_INV)) {
            $this->webViewRenderer =
                $webViewRenderer->withControllerName('invoice/paymentinformation')
                ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && $this->userService->hasPermission(Permissions::EDIT_INV)) {
            $this->webViewRenderer =
                $webViewRenderer->withControllerName('invoice/paymentinformation')
                ->withLayout('@views/layout/invoice.php');
        }
        $this->webService    = $webService;
        $this->compR         = $compR;
        $this->logger        = $logger;
        $this->telegramToken = $this->sR->getSetting('telegram_token');
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

    /**
     *  'inv' => [
     *      'id',
     *      'balance',
     *      'customer_id',
     *      'customer',
     *      'currency',
     *      'customer_email',
     *      'description',
     *      'number',
     *      'url_key'
     *   ],
     */

    public function openBankingInForm(
        PaymentInformationGatewayContext $ctx,
        array $inv,
    ): Response {
        $provider = PaymentInformationQueryHelper::extractProviderLower($ctx->client_chosen_gateway);
        $providerConfig = (null !== $provider) ?
                           $this->openBankingProviderRegistry->getProviderConfig($provider) : null;
        // Determine if provider is 'wonderful' by examining if the apiToken
        //  is filled
        $isWonderful = ($ctx->client_chosen_gateway == 'Open_Banking_With_Wonderful')
                && (strlen($this->sR->getSetting(
                           'gateway_open_banking_with_wonderful_apiToken')) > 0);
        $isTink = ($ctx->client_chosen_gateway == 'Open_Banking_With_Tink');
        // Prepare view data
        $viewData = [
            'alert'                  => $this->alert(),
            'authUrl'                => '',
            'balance'                => $ctx->balance,
            'client_chosen_gateway'  => $ctx->client_chosen_gateway,
            'client_on_invoice'      =>
                                 $ctx->cR->repoClientquery($ctx->invoice->reqClientId()),
            'disable_form'           => $ctx->disable_form,
            'invoice'                => $ctx->invoice,
            'inv_url_key'            => $ctx->url_key,
            'is_overdue'             => $ctx->is_overdue,
            'json_encoded_items'     => Json::encode($ctx->items_array),
            'companyLogo'            => $this->logoRenderer->companyLogo(),
            'partial_client_address' =>
                                     $this->webViewRenderer->renderPartialAsString(
                '//invoice/client/partial_client_address',
                ['client' => $ctx->cR->repoClientquery($ctx->invoice->reqClientId())],
            ),
            'payment_method' => $ctx->payment_method_for_this_invoice,
            'provider'       => $provider,
            'title'          => 'Open Banking with '
                        . ucfirst($provider ?? 'Not Provided') . ' is enabled',
            'total'          => $ctx->total,
        ];
        $amount = ((($ctx->balance > 0) && ($ctx->total > 0)) ? $ctx->balance : 0);
        if ($isWonderful) {
            $providerType = 'wonderful';
        } elseif ($isTink) {
            $providerType = 'tink';
        } else {
            $providerType = 'oauth';
        }
        switch ($providerType) {
            case 'wonderful':
                // The default currency is GBP so yii_invoice_array not used
                $details =
                    $this->openBankingPaymentService->paymentStatusAndDetails(
                                      $ctx->url_key, $amount, $ctx->invoice, $ctx->items_array);
                $singleKeyArray = (array) ($details['data'] ?? []);
                $data = (array) ($singleKeyArray['data'] ?? []);
                $viewData['wonderfulId'] = (string) ($data['id'] ?? '');
                $viewData['amountFormatted'] = (string) ($data['amount_formatted'] ?? '');
                $viewData['reference'] = (string) ($data['reference'] ?? '');
                $viewData['createdAt'] = (string) ($data['created_at'] ?? '');
                $viewData['updatedAt'] = (string) ($data['updated_at'] ?? '');
                $viewData['status'] = (string) ($data['status'] ?? '');
                // Wonderful requires an authToken, not an authUrl because it is
                // not oauth linked
                $viewData['authToken'] = true;
                $viewData['paymentLink'] = (string) ($data['pay_link'] ?? '');
                break;

            case 'tink':
                $viewData = $this->applyTinkProviderData($viewData, $amount, $ctx, $inv);
                break;

            default:
     // Other open banking providers use authUrl since they are oauth2.0 linked
                $authUrl = $this->openBankingPaymentService->getAuthUrlForProvider(
                                                    $providerConfig, $ctx->url_key);
                $viewData['authUrl']   = $authUrl;
                $viewData['returnUrl'] = [
                    'paymentinformation/paymentinformation_openbanking',
                    ['url_key' => $ctx->url_key], [], null];
                break;
        }
        return $this->webViewRenderer->render(
        '//invoice/paymentinformation/payment_information_openbanking', $viewData);
    }

/**
 * Update: 29 May 2025
 * Related logic: https://developer.amazon.com/docs/amazon-pay-api-v2.7/
 * checkout-session.html#create-checkout-session.
 */
    public function amazonComplete(Request $request, CurrentRoute $currentRoute): \Psr\Http\Message\ResponseInterface
    {
        $invoice_url_key = $currentRoute->getArgument('url_key');
        $invoice = null !== $invoice_url_key && $this->iR->repoUrlKeyGuestCount($invoice_url_key) > 0
            ? $this->iR->repoUrlKeyGuestLoaded($invoice_url_key)
            : null;
        $query_params = $request->getQueryParams();
        /** @var string $query_params['amazonCheckoutSessionId'] */
        $checkout_session_id = $query_params['amazonCheckoutSessionId'] ?? null;
        if (null === $invoice_url_key || null === $invoice || null === $checkout_session_id) {
            return $this->webService->getNotFoundResponse();
        }

        $sandbox_url_array = $this->sR->sandboxUrlArray();

        // Use service to check completion status and handle invoice updates
        $result = $this->amazonPayPaymentService->handleCallback([
            'amazonCheckoutSessionId' => $checkout_session_id,
            // Pass the entity if needed in service
            'invoice'                 => $invoice,
            'iR'                      => $this->iR,
            'iaR'                     => $this->iaR,
        ]);

        // Update invoice/payment status if successful
        if ($result['success']) {
            $view_data = [
                'render' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/setting/payment_message',
                    [
                        'heading'     =>
                            $this->translator->translate(
                        'payment.information.amazon.payment.session.complete')
                            . $checkout_session_id,
                        'message'     => $this->translator->translate('payment')
                            . ':' . $this->translator->translate('complete'),
                        'url'         => 'inv/urlKey',
                        'url_key'     => $invoice_url_key,
                        'gateway'     => 'Amazon_Pay',
                        'sandbox_url' => $sandbox_url_array['amazon_pay'],
                    ],
                ),
            ];
            $this->updateInvoicePaymentMethod($invoice_url_key);
        } else {
            $view_data = [
                'render' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/setting/payment_message',
                    [
                        'heading'     => $this->translator->translate(
                        'payment.information.amazon.payment.session.incomplete'),
                        'message'     => $result['message'] ??
                            ($this->translator->translate('payment')
                            . ':' . $this->translator->translate('incomplete')),
                        'url'         => 'inv/urlKey',
                        'url_key'     => $invoice_url_key,
                        'gateway'     => 'Amazon_Pay',
                        'sandbox_url' => $sandbox_url_array['amazon_pay'],
                    ],
                ),
            ];
        }

        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function openbankingOauthComplete(Request $request,
            CurrentRoute $currentRoute): Response
    {
        $url_key        = $currentRoute->getArgument('url_key');
        $query_params   = $request->getQueryParams();
        $code           = (string) $query_params['code'];
        $codeVerifier   = (string) $this->session->get('code_verifier');
        $provider       = $this->sR->getSetting('open_banking_provider');
        $providerConfig = $provider ?
                $this->openBankingProviderRegistry->getProviderConfig($provider) : null;

        if (null !== $providerConfig
                && (strlen($code) > 0)
                && (strlen($url_key ?? '') > 0)
                && $codeVerifier) {
            $this->openBankingOauthClient->setAuthUrl(
                                            (string) $providerConfig['authUrl']);
            $this->openBankingOauthClient->setTokenUrl(
                                            (string) $providerConfig['tokenUrl']);
            $this->openBankingOauthClient->setScope(
                                        isset($providerConfig['scope']) ?
                                        (string) $providerConfig['scope'] : null);

            // Exchange code for token
            try {
                //  calls (e.g., to initiate payment)

                $this->flashMessage('success', 'Open Banking authentication successful.');

                return $this->webViewRenderer->render('payment_completion_page', [
                    'render' => '<h2>Open Banking payment authorized.</h2>',
                ]);
            } catch (\Throwable $e) {
                $this->flashMessage('error', 'Open Banking authentication failed: '
                        . $e->getMessage());
            }
        }

        return $this->webService->getNotFoundResponse();
    }

    public function tinkComplete(CurrentRoute $currentRoute):
                                    \Psr\Http\Message\ResponseInterface
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $ref = $currentRoute->getArgument('ref');
        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/payment_message',
                [
                    'heading'     =>
                    sprintf($this->translator->translate(
                            'online.payment.payment.successful'), $ref ??
                                'No ref provided'),
                    'message'     => 'Ref: ' . ($ref ?? 'No ref provided'),
                    'url'         => 'inv/urlKey',
                    'url_key'     => $urlKey,
                    'gateway'     => 'Tink',
                    'sandbox_url' => '',
                ],
            ),
        ];
        if (null!==$urlKey) {
           $this->updateInvoicePaymentMethod($urlKey);
        }
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function wonderfulComplete(CurrentRoute $currentRoute): \Psr\Http\Message\ResponseInterface
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $ref = $currentRoute->getArgument('ref');
        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/payment_message',
                [
                    'heading'     => sprintf(
                            $this->translator->translate(
                                    'online.payment.payment.successful'),
                                                $ref ?? 'No ref provided'),
                    'message'     => 'Ref: ' . ($ref ?? 'No ref provided'),
                    'url'         => 'inv/urlKey',
                    'url_key'     => $urlKey,
                    'gateway'     => 'Wonderful',
                    'sandbox_url' => '',
                ],
            ),
        ];
        if (null!==$urlKey) {
           $this->updateInvoicePaymentMethod($urlKey);
        }
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function inform(Request $request, CurrentRoute $currentRoute,
                                        cR $cR, iiR $iiR, pmR $pmR): Response
    {
        $client_chosen_gateway = $currentRoute->getArgument('gateway');
        $url_key               = $currentRoute->getArgument('url_key');
        $invoice               = null !== $url_key ? $this->iR->repoUrlKeyGuestLoaded($url_key) : null;
        $invoice_id            = null !== $invoice ? $invoice->reqId() : 0;
        $invoice_amount_record = null !== $invoice ? $this->iaR->repoInvquery($invoice_id) : null;
        if (null === $client_chosen_gateway || null === $url_key || null === $invoice || null === $invoice_amount_record) {
            return $this->webService->getNotFoundResponse();
        }
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
        $client = $invoice->getClient();
        $yii_invoice_array = [
            'id'             => $invoice_id,
            'balance'        => $balance,
            'customer_id'    => $invoice->reqClientId(),
            'customer'       => ($client?->getClientName() ?? '') . ' ' . ($client?->getClientSurname() ?? ''),
            'currency'       => !empty($this->sR->getSetting('currency_code'))
                                ? strtolower($this->sR->getSetting('currency_code'))
                                : 'gbp',
            'customer_email' => $client?->getClientEmail(),
            'description'    => Json::encode($items_array),
            'number'         => $invoice->getNumber(),
            'url_key'        => $invoice->getUrlKey(),
        ];
        // NOTE: $payment_method_for_this_invoice may be null when the invoice has no
        // previously recorded payment method (payment_method = 0 or null on an unpaid
        // invoice). Fall back to '' — must NOT gate routing to the gateway form.
        $payment_method_for_this_invoice = $pmR->repoPaymentMethodquery((int) $invoice->getPaymentMethod());
        $is_overdue = $balance > 0.00 && strtotime($invoice->getDateDue()->format('Y-m-d')) < time();
        $payment_method_name = null !== $payment_method_for_this_invoice
            ? ($payment_method_for_this_invoice->getName() ?? '')
            : '';
        $ctx = new PaymentInformationGatewayContext(
            client_chosen_gateway: $client_chosen_gateway,
            url_key: $url_key,
            balance: $balance,
            cR: $cR,
            invoice: $invoice,
            items_array: $items_array,
            disable_form: $disable_form,
            is_overdue: $is_overdue,
            payment_method_for_this_invoice: $payment_method_name,
            total: $total,
        );
        return $this->pciCompliantGatewayInForms(
            $ctx,
            strtolower($client_chosen_gateway),
            $request,
            $invoice_id,
            $yii_invoice_array,
            $this->sR->sandboxUrlArray(),
        );
    }

    /**
     * Related logic: see SettingRepository function activePaymentGateways
     */
    public function pciCompliantGatewayInForms(
        PaymentInformationGatewayContext $ctx,
        string $d,
        Request $request,
        int $invoice_id,
        array $yii_invoice_array,
        array $sandbox_url_array,
    ): Response {
        if (null !== $ctx->invoice->getNumber()) {
            if ('1' === $this->sR->getSetting('gateway_' . $d . '_enabled')) {
                $gateway = $ctx->client_chosen_gateway;
                $gatewayResponse = match ($gateway) {
                    'Open_Banking_With_Wonderful', 'Open_Banking_With_Tink'
                                 => $this->openBankingInForm($ctx, $yii_invoice_array),
                    'Amazon_Pay' => $this->amazonInForm($ctx),
                    'Stripe'     => $this->stripeInForm($ctx, $yii_invoice_array),
                    'Braintree'  => $this->brainTreeInForm($ctx, $request, $invoice_id, $sandbox_url_array),
                    'Mollie'     => $this->mollieInForm(
                                        $ctx, $yii_invoice_array, 'creditcard',
                                        $this->sR->getSetting('gateway_mollie_locale')),
                    // Adyen has its own dedicated AdyenPaymentController —
                    // PaymentInformationController is already at
                    // SonarQube's php:S1448 method-count ceiling, and a
                    // browser redirect can't carry $ctx across requests
                    // anyway, so there's no reuse benefit to handling it
                    // inline here first.
                    'Adyen'      => $this->webService->getRedirectResponse(
                        'paymentinformation/adyenInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    // GoCardless has its own dedicated GoCardlessPaymentController
                    // — same reasoning as Adyen above.
                    'GoCardless' => $this->webService->getRedirectResponse(
                        'paymentinformation/goCardlessInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    // Robokassa and YooKassa each have their own dedicated
                    // controller — same reasoning as Adyen/GoCardless above.
                    'Robokassa'  => $this->webService->getRedirectResponse(
                        'paymentinformation/robokassaInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    'Yookassa'   => $this->webService->getRedirectResponse(
                        'paymentinformation/yookassaInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    // Paystack has its own dedicated PaystackPaymentController
                    // — same reasoning as Adyen/GoCardless/Robokassa/YooKassa above.
                    'Paystack'   => $this->webService->getRedirectResponse(
                        'paymentinformation/paystackInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    // Razorpay has its own dedicated RazorpayPaymentController
                    // — same reasoning as Adyen/GoCardless/Robokassa/YooKassa/Paystack above.
                    'Razorpay'   => $this->webService->getRedirectResponse(
                        'paymentinformation/razorpayInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    // Paypal has its own dedicated PaypalPaymentController
                    // — same reasoning as every other dedicated gateway controller above.
                    'Paypal'     => $this->webService->getRedirectResponse(
                        'paymentinformation/paypalInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    // Square has its own dedicated SquarePaymentController
                    // — same reasoning as every other dedicated gateway controller above.
                    'Square'     => $this->webService->getRedirectResponse(
                        'paymentinformation/squareInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    // Checkout.com has its own dedicated CheckoutComPaymentController
                    // — same reasoning as every other dedicated gateway controller above.
                    'Checkout_Com' => $this->webService->getRedirectResponse(
                        'paymentinformation/checkoutComInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    // TrueLayer has its own dedicated TrueLayerPaymentController
                    // — same reasoning as every other dedicated gateway controller above.
                    'TrueLayer' => $this->webService->getRedirectResponse(
                        'paymentinformation/trueLayerInForm',
                        ['url_key' => $ctx->url_key],
                    ),
                    default      => null,
                };
                if ($gatewayResponse !== null) {
                    return $gatewayResponse;
                }
            }
        } else {
            $this->flashMessage('danger',
                                    $this->translator->translate('number.no'));
        }

        return $this->webService->getNotFoundResponse();
    }

    public function amazonInForm(PaymentInformationGatewayContext $ctx): Response
    {
        // Let service check for private.pem and return error message if missing
        $pemCheck = $this->amazonPayPaymentService->checkPrivatePemFile();
        if (null !== $pemCheck) {
            $this->flashMessage('warning', (string) $pemCheck['message']);

            return $this->webViewRenderer->render(
                '//invoice/setting/payment_message',
                [
                    'heading' => '',
                    'message' => 'Amazon_Pay private.pem File Not Downloaded'
                    . ' from Amazon and saved in Pem_unique_folder as private.pem',
                    'url'     => 'inv/urlKey',
                    'url_key' => $ctx->url_key,
                    'gateway' => 'Amazon_Pay',
                ],
            );
        }

        // Get Amazon Pay button data from the service
        $amazonPayButton = $this->amazonPayPaymentService->getButtonData(
            $ctx->invoice, $ctx->url_key, $ctx->balance);

        $amazon_pci_view_data = [
            'alert'                  => $this->alert(),
            'amazonPayButton'        => $amazonPayButton,
            'balance'                => $ctx->balance,
            'client_chosen_gateway'  => $ctx->client_chosen_gateway,
            'client_on_invoice'      => $ctx->cR->repoClientquery($ctx->invoice->reqClientId()),
            'crypt'                  => $this->sR,
            'disable_form'           => $ctx->disable_form,
            'invoice'                => $ctx->invoice,
            'inv_url_key'            => $ctx->url_key,
            'is_overdue'             => $ctx->is_overdue,
            'json_encoded_items'     => Json::encode($ctx->items_array),
            'companyLogo'            => $this->logoRenderer->companyLogo(),
            'partial_client_address' => $this->webViewRenderer
                ->renderPartialAsString(
                    '//invoice/client/partial_client_address',
                    ['client' => $ctx->cR->repoClientquery($ctx->invoice->reqClientId())],
                ),
            'payment_method' => $ctx->payment_method_for_this_invoice,
            'return_url'     => ['paymentinformation/amazonComplete',
                                                        ['url_key' => $ctx->url_key]],
            'title'          => 'Amazon Pay is enabled',
            'total'          => $ctx->total,
        ];

        return $this->webViewRenderer->render('payment_information_amazon_pci',
                                                        $amazon_pci_view_data);
    }

    public function brainTreeInForm(
        PaymentInformationGatewayContext $ctx,
        Request $request,
        int $invoice_id,
        array $sandbox_url_array,
    ): Response {
        if (!$this->braintreePaymentService->isConfigured()) {
            $this->flashMessage('warning', 'Braintree payment gateway is not properly configured.');
            return $this->webService->getNotFoundResponse();
        }
        if (!$this->braintreePaymentService->findOrCreateCustomer($ctx->invoice)) {
            $this->flashMessage('warning', 'Unable to create or find customer in Braintree.');
        }
        $clientToken = $this->braintreePaymentService->generateClientToken();
        if (empty($clientToken)) {
            $this->flashMessage('warning', 'Unable to generate Braintree client token.');
            return $this->webService->getNotFoundResponse();
        }
        $merchantId = $this->braintreePaymentService->getMerchantId();

        // Return the view
        $braintree_pci_view_data = [
            'alert'                  => $this->alert(),
            'return_url'             => ['paymentinformation/braintree_complete', ['url_key' => $ctx->url_key]],
            'balance'                => $ctx->balance,
            'body'                   => $request->getParsedBody() ?? [],
            'client_on_invoice'      => $ctx->cR->repoClientquery($ctx->invoice->reqClientId()),
            'json_encoded_items'     => Json::encode($ctx->items_array),
            'client_token'           => $clientToken,
            'disable_form'           => $ctx->disable_form,
            'client_chosen_gateway'  => $ctx->client_chosen_gateway,
            'invoice'                => $ctx->invoice,
            'inv_url_key'            => $ctx->url_key,
            'is_overdue'             => $ctx->is_overdue,
            'partial_client_address' => $this->webViewRenderer
                ->renderPartialAsString(
                    '//invoice/client/partial_client_address',
                    ['client' => $ctx->cR->repoClientquery($ctx->invoice->reqClientId())],
                ),
            'payment_method' => $ctx->payment_method_for_this_invoice,
            'total'          => $ctx->total,
            'action'         =>
            ['paymentinformation/form', [
                'url_key' => $ctx->url_key,
                'gateway' => 'Braintree']],
            'companyLogo'    => $this->logoRenderer->companyLogo(),
            'braintreeLogo'  => $this->logoRenderer->braintreeLogo($merchantId),
            'title'          => 'Braintree - PCI Compliant - Version'
            . $this->braintreePaymentService->getVersion() . ' - is enabled. ',
        ];

        return Method::POST === $request->getMethod()
            ? $this->renderBraintreePostResponse($ctx, $request, $invoice_id, $sandbox_url_array)
            : $this->webViewRenderer->render('payment_information_braintree_pci', $braintree_pci_view_data);
    }

    private function renderBraintreePostResponse(
        PaymentInformationGatewayContext $ctx,
        Request $request,
        int $invoice_id,
        array $sandbox_url_array,
    ): Response {
        $body               = $request->getParsedBody() ?? [];
        $paymentMethodNonce = (string) ($body['payment_method_nonce'] ?? '');
        $transactionResult  = $this->braintreePaymentService->processTransaction(
            $ctx->balance, $paymentMethodNonce);
        if ($transactionResult['success']) {
            /** @var InvAmount $invoice_amount_record */
            $invoice_amount_record = $this->iaR->repoInvquery($ctx->invoice->reqId());
            if (null !== $invoice_amount_record->getTotal()) {
                $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock($ctx->invoice, $invoice_amount_record);
                $this->paymentRecorder->record(
                    new PaymentRecordContext(
                        reference: $ctx->invoice->getNumber() ??
                            $this->translator->translate('number.no'),
                        invoice_id: (string) $invoice_id,
                        balance: $ctx->balance ?: 0.00,
                        invoice_payment_method: 4,
                        invoice_number: $ctx->invoice->getNumber() ??
                            $this->translator->translate('number.no'),
                        driver: 'Braintree',
                        d: 'braintree',
                        invoice_url_key: $ctx->url_key,
                        response: true,
                        sandbox_url_array: $sandbox_url_array,
                        provider_reference: is_string($transactionResult['transaction_id'])
                            ? $transactionResult['transaction_id'] : null,
                        // Braintree has no webhook at all in this app — its
                        // Drop-in form POST is synchronous, so this is
                        // genuinely a redirect/form-POST recording, not a
                        // webhook one.
                        channel: PaymentRecordChannel::Redirect,
                    ),
                );
            }
        }
        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/payment_message', [
                    'heading'     => '',
                    'message'     => $transactionResult['success']
                        ? sprintf($this->translator->translate(
                            'online.payment.payment.successful'),
                            $ctx->invoice->getNumber() ?? '')
                        : sprintf($this->translator->translate(
                            'online.payment.payment.failed'),
                            $ctx->invoice->getNumber() ?? ''),
                    'url'         => 'inv/urlKey',
                    'url_key'     => $ctx->url_key,
                    'gateway'     => 'Braintree',
                    'sandbox_url' => $sandbox_url_array['braintree'],
                ]),
        ];
        $this->iR->save($ctx->invoice);
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

/**
 * Handles Braintree payment completion
 * Note: Braintree payments are typically processed directly in braintreeInForm
 *  method,
 * but this endpoint exists for consistency and potential webhook handling.
 */
    public function braintreeComplete(CurrentRoute $currentRoute): Response
    {
        $invoice_url_key = $currentRoute->getArgument('url_key');
        if (null !== $invoice_url_key) {
            $sandbox_url_array = $this->sR->sandboxUrlArray();

            // Get the invoice data
            if ($this->iR->repoUrlKeyGuestCount($invoice_url_key) > 0) {
                $invoice = $this->iR->repoUrlKeyGuestLoaded($invoice_url_key);
            } else {
                return $this->webService->getNotFoundResponse();
            }

            if ($invoice) {
                $invoiceNumber = $invoice->getNumber() ?? 'Unknown';

// For Braintree, transactions are typically completed directly in the form POST
// This completion handler is primarily for consistency with other payment methods
                $view_data = [
                    'render' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/payment_message',
                        [
                            'heading'     => sprintf(
                                    $this->translator->translate(
                                            'online.payment.payment.successful'), $invoiceNumber),
                            'message'     =>
                                    $this->translator->translate(
                                            'payment')
                                . ':' . $this->translator->translate('complete'),
                            'url'         => 'inv/urlKey',
                            'url_key'     => $invoice_url_key,
                            'gateway'     => 'Braintree',
                            'sandbox_url' => $sandbox_url_array['braintree'],
                        ],
                    ),
                ];

                return $this->webViewRenderer->render('payment_completion_page',
                        $view_data);
            }
        }

        return $this->webService->getNotFoundResponse();
    }

    public function mollieInForm(
        PaymentInformationGatewayContext $ctx,
        array $yii_invoice_array,
        string $payment_method,
        string $locale,
    ): Response {
        /**
         * All endpoints are initialized in the MollieClient const.
         *
         * @see https://github.com/mollie/mollie-api-php/blob/master/examples/payments/create-payment.php
         * @see https://github.com/mollie/mollie-api-php/tree/master
         */
        $mollieClient = new MollieClient();
        // Return the view
        if ('1' === $this->sR->getSetting('gateway_mollie_enabled')
                && (!PaymentInformationQueryHelper::mollieSetTestOrLiveApiKey($this->sR, $mollieClient))) {
            $this->flashMessage('warning',
                    $this->translator->translate(
                            'payment.gateway.mollie.api.key.needs.to.be.setup'));

            return $this->webService->getNotFoundResponse();
        }
        if ('1' === $this->sR->getSetting('gateway_mollie_enabled')
                && (PaymentInformationQueryHelper::mollieSetTestOrLiveApiKey($this->sR, $mollieClient))) {
            $this->flashMessage('success',
                    $this->translator->translate(
                            'payment.gateway.mollie.api.key.has.been.setup'));
        }
        $payment = $this->mollieApiClientCreatePayment(
            $mollieClient,
            $yii_invoice_array,
            $payment_method,
            $ctx->url_key,
            $locale,
        );
        $mollie_pci_view_data = [
            'alert'                      => $this->alert(),
            'return_url'                 => ['inv/urlKey', ['url_key' => $ctx->url_key]],
            'balance'                    => $ctx->balance,
            'client_on_invoice'          =>
                $ctx->cR->repoClientquery($ctx->invoice->reqClientId()),
            'pci_client_publishable_key' =>
                $this->sR->decode($this->sR->getSetting(
                        'gateway_mollie_publishableKey')),
            'json_encoded_items'         => Json::encode($ctx->items_array),
            'disable_form'               => $ctx->disable_form,
            'client_chosen_gateway'      => $ctx->client_chosen_gateway,
            'invoice'                    => $ctx->invoice,
            'payment'                    => $payment,
            'inv_url_key'                => $ctx->url_key,
            'is_overdue'                 => $ctx->is_overdue,
            'partial_client_address'     =>
                $this->webViewRenderer->renderPartialAsString(
                '//invoice/client/partial_client_address',
                [
                    'client' => $ctx->cR->repoClientquery($ctx->invoice->reqClientId()),
                ],
            ),
            'payment_methods'        => $mollieClient->methods->allEnabled(),
            'invoice_payment_method' => $ctx->payment_method_for_this_invoice ?:
                $this->translator->translate('none'),
            'total'                  => $ctx->total,
            'companyLogo'            => $this->logoRenderer->companyLogo(),
            'mollieLogo'             => $this->logoRenderer->mollieLogo(),
            'title'                  => PaymentInformationQueryHelper::mollieClientVersionString()
                . ' - PCI Compliant - is enabled. ',
        ];

        return $this->webViewRenderer->render('payment_information_mollie_pci',
            $mollie_pci_view_data);
    }

    /**
     * @throws MollieException
     */
    public function mollieApiClientCreatePayment(
        MollieClient $mollieClient,
        array $yii_invoice,
        string $paymentMethod,
        string $urlKey,
        string $locale,
    ): MolliePayment|Response {
        try {
            $amount = (float) $yii_invoice['balance'];

            return $mollieClient->payments->create([
                'amount' => [
                    'currency' => strtoupper((string) $yii_invoice['currency']),
                    'value' => number_format($amount > 0 ? $amount : 0.0, 2, '.', ''),
                ],
                'locale'      => $locale,
                'method'      => $paymentMethod,
                'description' => $yii_invoice['description'],
                'redirectUrl' => $this->urlGenerator->generateAbsolute(
                    'paymentinformation/mollieComplete',
                    [
                        'url_key'   => $urlKey,
                        '_language' => (string) $this->session->get('_language'),
                    ]
                ),
                // Mollie's classic per-payment webhook — MollieWebhookHandler
                // is the authoritative source that actually marks the
                // invoice paid; mollieComplete() only reads current state.
                // Mollie needs a publicly reachable HTTPS URL to actually
                // call this — a local/dev environment with no public
                // tunnel simply won't receive it, same limitation as any
                // other webhook-based gateway in local development.
                'webhookUrl' => $this->urlGenerator->generateAbsolute(
                    'paymentinformation/mollieWebhook',
                    [
                        '_language' => (string) $this->session->get('_language'),
                    ]
                ),
                'metadata' => [
                    'invoice_id'          => $yii_invoice['id'],
                    'invoice_customer_id' => $yii_invoice['customer_id'],
                    'invoice_number'      => $yii_invoice['number'] ?: '',
                    'invoice_url_key'     => $urlKey,
                    'receipt_email'       => $yii_invoice['customer_email'],
                    'order_id'            => time(),
                ],
            ]);

       } catch (MollieException $e) {
            // Log safely — no raw exception message exposed to the user (see CWE-200)
            $this->logger->error('Mollie payment creation failed', [
                'invoice_id' => $yii_invoice['id'] ?? 'unknown',
                'message'    => $e->getMessage(),
                'code'       => $e->getCode(),
            ]);

            throw $e; // Re-throw so the caller can handle it appropriately
        }
   }

    public function mollieComplete(CurrentRoute $currentRoute):
        \Psr\Http\Message\ResponseInterface
    {
        $url_key = $currentRoute->getArgument('url_key');
        if (null === $url_key) {
            return $this->webService->getNotFoundResponse();
        }
        $sandbox_url_array = $this->sR->sandboxUrlArray();
        /** @var Inv $invoice */
        $invoice       = $this->iR->repoUrlKeyGuestLoaded($url_key);
        $invoiceNumber = $invoice->getNumber() ?? 'unknown';
        $mollie        = new MollieClient();
        $lastPayment   = new MolliePayment($mollie);
        if (!PaymentInformationQueryHelper::mollieSetTestOrLiveApiKey($this->sR, $mollie)) {
            return $this->webService->getNotFoundResponse();
        }
        $payments = $mollie->payments->page();
        $metadataInvoiceUrlKey = '';
        /**
         * @var MolliePayment $payment
         */
        foreach ($payments as $payment) {
            /**
             * Related logic: see https://www.php.net/manual/en/class.stdclass.php
             * Related logic: see .\vendor\mollie\mollie-api-php\src\Resources\Payment.php.
             * @var \stdClass $payment->metadata
             */
            $metaData = $payment->metadata;
            $metadataInvoiceUrlKey = (string) $metaData->invoice_url_key;
            if ($metadataInvoiceUrlKey === $url_key) {
                $lastPayment = $payment;
                break;
            }
        }
        $paymentId = $lastPayment->id;
        // Related logic: see vendor\mollie\mollie-api-php\examples\payments\webhook.php
        if ($lastPayment->isPaid() && !$lastPayment->hasRefunds() && !$lastPayment->hasChargebacks()) {
            $payment_method = 4;
            $heading = sprintf($this->translator->translate('online.payment.payment.successful'), $invoiceNumber);
            /** @var int $invoice->reqId() */
            $invoice_amount_record = $this->iaR->repoInvquery($invoice->reqId());
            /** @var InvAmount $invoice_amount_record */
            $balance = $invoice_amount_record->getBalance();
            // Guard against double-recording: MollieWebhookHandler may have
            // already marked this invoice paid (Mollie's own docs: "the
            // webhook may be called after the customer has already been
            // redirected" — it can just as easily arrive first), in which
            // case balance is already 0 and re-running this would create a
            // duplicate Merchant audit row via paymentRecorder->record().
            // markInvoicePaidAndAdjustStock() itself is also idempotent
            // (status_id 4 already set is a no-op), so this is belt and
            // braces against the same race from two directions.
            if (null !== $balance && $balance > 0.00) {
                $this->invPaymentSettlementService->markInvoicePaidAndAdjustStock(
                    $invoice,
                    $invoice_amount_record,
                    $payment_method,
                );
                $this->paymentRecorder->record(
                    new PaymentRecordContext(
                        reference: $invoiceNumber . '-' . $lastPayment->status,
                        invoice_id: (string) $invoice_amount_record->reqInvId(),
                        balance: $balance > 0.00 ? $balance : 0.00,
                        invoice_payment_method: $payment_method,
                        invoice_number: $invoiceNumber,
                        driver: 'Mollie',
                        d: 'mollie',
                        invoice_url_key: $metadataInvoiceUrlKey,
                        response: true,
                        sandbox_url_array: $sandbox_url_array,
                        provider_reference: $paymentId,
                        // Legacy fallback: MollieWebhookHandler normally
                        // records this via webhook first, but this
                        // reverse-lookup can still be the one that actually
                        // wins the race — see the guard comment above.
                        channel: PaymentRecordChannel::Redirect,
                    ),
                );
            }
            $view_data = [
                'render' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/paymentinformation/payment_message',
                    [
                        'heading'     => $heading,
                        'message'     => $this->translator->translate('payment')
                                        . ':' . $this->translator->translate('complete')
                                        . 'Payment Id: ' . $paymentId,
                        'url'         => 'inv/urlKey',
                        'url_key'     => $metadataInvoiceUrlKey,
                        'gateway'     => 'Mollie',
                        'sandbox_url' => $sandbox_url_array['mollie'],
                    ],
                ),
            ];
        } else {
            // all-0, draft-1, sent-2, viewed-3, paid-4, overdue-5, unpaid-6
            $invoice->setStatusId(6);
            $invoice->setPaymentMethod(5);
            $heading = sprintf(
                $this->translator->translate('online.payment.payment.failed'),
                $invoiceNumber . ' '
                    . $this->translator->translate('payment.gateway.mollie.api.payment.id')
                    . $paymentId,
            );
            $this->iR->save($invoice);
            $view_data = [
                'render' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/paymentinformation/payment_message',
                    [
                        'heading'     => $heading,
                        'message'     => $this->translator->translate('payment')
                                        . ':' . $this->translator->translate('complete'),
                        'url'         => 'inv/urlKey',
                        'url_key'     => $metadataInvoiceUrlKey,
                        'gateway'     => 'Mollie',
                        'sandbox_url' => $sandbox_url_array['mollie'],
                    ],
                ),
            ];
        }
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    public function stripeInForm(
        PaymentInformationGatewayContext $ctx,
        array $yii_invoice_array,
    ): Response {
        if (!$this->stripePaymentService->isConfigured()) {
            $this->flashMessage('warning', 'Stripe payment gateway is not properly configured.');
            return $this->webService->getNotFoundResponse();
        }
        // Get Stripe keys and client secret from service
        $publishableKey = $this->stripePaymentService->getPublishableKey();
        $clientSecret   = $this->stripePaymentService->createPaymentIntent(
            $yii_invoice_array);

        $stripe_pci_view_data = [
            'alert'                      => $this->alert(),
            'return_url'                 =>
                ['paymentinformation/stripe_complete', ['url_key' => $ctx->url_key]],
            'balance'                    => $ctx->balance,
            'client_on_invoice'          =>
                $ctx->cR->repoClientquery($ctx->invoice->reqClientId()),
            'pci_client_publishable_key' => $publishableKey,
            'json_encoded_items'         => Json::encode($ctx->items_array),
            'client_secret'              => $clientSecret,
            'disable_form'               => $ctx->disable_form,
            'client_chosen_gateway'      => $ctx->client_chosen_gateway,
            'invoice'                    => $ctx->invoice,
            'inv_url_key'                => $ctx->url_key,
            'is_overdue'                 => $ctx->is_overdue,
            'partial_client_address'     => $this->webViewRenderer
                ->renderPartialAsString(
                    '//invoice/client/partial_client_address',
                    ['client' => $ctx->cR->repoClientquery($ctx->invoice->reqClientId())],
                ),
            'payment_method' => $ctx->payment_method_for_this_invoice ?: 'None',
            'total'          => $ctx->total,
            'companyLogo'    => $this->logoRenderer->companyLogo(),
            'stripeLogo'     => $this->logoRenderer->stripeLogo(),
            'title'          => Stripe::getApiVersion()
                                    . ' - PCI Compliant - is enabled. ',
        ];

        return $this->webViewRenderer->render('payment_information_stripe_pci',
                $stripe_pci_view_data);
    }

    /**
     * Read-only: the invoice is marked paid exclusively by stripeWebhook(),
     * which is authoritative because it's driven by a Stripe-signed event
     * rather than this client-supplied redirect. This action only re-reads
     * current state to decide which message to show — it never writes.
     */
    public function stripeComplete(
                        Request $request, CurrentRoute $currentRoute): Response
    {
        $invoiceUrlKey = $currentRoute->getArgument('url_key');
        if (null === $invoiceUrlKey) {
            return $this->webService->getNotFoundResponse();
        }
        $sandboxUrlArray = $this->sR->sandboxUrlArray();
        $invoice = $this->iR->repoUrlKeyGuestLoaded($invoiceUrlKey);
        if (null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }
        $invoiceNumber   = $invoice->getNumber() ?? 'unknown';
        $query_params    = $request->getQueryParams();
        $redirectStatus  = (string) ($query_params['redirect_status'] ?? '');
        /** @var InvAmount $invoice_amount_record */
        $invoice_amount_record = $this->iaR->repoInvquery($invoice->reqId());
        $isPaid = 0.00 === $invoice_amount_record->getBalance();

        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/paymentinformation/payment_message',
                [
                    'heading'     => PaymentInformationQueryHelper::stripeCompleteHeading(
                        $this->translator, $isPaid, $invoiceNumber, $redirectStatus),
                    'message'     =>
                        $this->translator->translate('payment')
                            . ':'
                            . $this->translator->translate('complete'),
                    'url'         => 'inv/urlKey',
                    'url_key'     => $invoiceUrlKey,
                    'gateway' => 'Stripe',
                    'sandbox_url' => $sandboxUrlArray['stripe'],
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }

    /**
     * Receives Stripe's signed payment_intent.* events. Delegates to
     * StripeWebhookHandler — extracted out of this controller to keep it
     * under SonarQube's per-class method count (php:S1448) and to let the
     * webhook's guard-clause chain stay under the per-method return count
     * (php:S1142) without collapsing everything into one long method. Route
     * is CSRF-exempt and unauthenticated by design; see
     * routes-payment-information.php and CsrfExemptMiddleware.
     */
    public function stripeWebhook(Request $request): Response
    {
        return $this->stripeWebhookHandler->handle($request);
    }

    private function updateInvoicePaymentMethod(string $urlKey): void {
        if (null !== ($invoice = $this->iR->repoUrlKeyGuestLoaded($urlKey))) {
            $invoice->setPaymentMethod(4);
            $this->iR->save($invoice);
        }
    }
}
