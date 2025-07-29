<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Invoice\Client\ClientRepository as cR;
// Helpers
use App\Invoice\Company\CompanyRepository as compR;
// Entities
use App\Invoice\CompanyPrivate\CompanyPrivateRepository as cPR;
use App\Invoice\Entity\Company;
use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\Merchant;
use App\Invoice\Entity\Payment;
use App\Invoice\Entity\Setting;
// Libraries
use App\Invoice\Helpers\DateHelper;
// Psr
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
// Repositories
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\Libraries\Crypt;
use App\Invoice\Merchant\MerchantService;
use App\Invoice\Payment\PaymentService;
use App\Invoice\PaymentInformation\Service\AmazonPayPaymentService;
use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\PaymentInformation\Service\OpenBankingPaymentService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\PaymentMethod\PaymentMethodRepository as pmR;
// Services
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Setting\Trait\OpenBankingProviders;
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
use Vjik\TelegramBot\Api\FailResult;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\AuthClient\Client\OpenBanking;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class PaymentInformationController
{
    use FlashMessage;

    use OpenBankingProviders;

    private Crypt $crypt;

    public function __construct(
        private DataResponseFactoryInterface $factory,
        private Flash $flash,
        private MerchantService $merchantService,
        private AmazonPayPaymentService $amazonPayPaymentService,
        private BraintreePaymentService $braintreePaymentService,
        private StripePaymentService $stripePaymentService,
        private OpenBankingPaymentService $openBankingPaymentService,
        private OpenBanking $openBankingOauthClient,
        private PaymentService $paymentService,
        private Session $session,
        private iaR $iaR,
        private iR $iR,
        private sR $sR,
        private UrlGenerator $urlGenerator,
        private UserService $userService,
        private Translator $translator,
        private ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private compR $compR,
        private cPR $cPR,
        private Logger $logger,
        private string $telegramToken,
    ) {
        $this->factory                   = $factory;
        $this->flash                     = $flash;
        $this->merchantService           = $merchantService;
        $this->amazonPayPaymentService   = $amazonPayPaymentService;
        $this->braintreePaymentService   = $braintreePaymentService;
        $this->stripePaymentService      = $stripePaymentService;
        $this->openBankingPaymentService = $openBankingPaymentService;
        $this->openBankingOauthClient    = $openBankingOauthClient;
        $this->paymentService            = $paymentService;
        $this->session                   = $session;
        $this->iaR                       = $iaR;
        $this->iR                        = $iR;
        $this->sR                        = $sR;
        $this->urlGenerator              = $urlGenerator;
        $this->userService               = $userService;
        $this->translator                = $translator;
        $this->viewRenderer              = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/paymentinformation')
                ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/paymentinformation')
                ->withLayout('@views/layout/invoice.php');
        }
        $this->webService    = $webService;
        $this->crypt         = new Crypt();
        $this->compR         = $compR;
        $this->cPR           = $cPR;
        $this->logger        = $logger;
        $this->telegramToken = $this->sR->getSetting('telegram_token');
    }

    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash'  => $this->flash,
                'errors' => [],
            ],
        );
    }

    public function openBankingInForm(
        string $client_chosen_gateway,
        string $url_key,
        float $balance,
        cR $cR,
        Inv $invoice,
        array $items_array,
        bool $disable_form,
        bool $is_overdue,
        string $payment_method_for_this_invoice,
        float $total,
    ): Response {
        // Get the Open Banking provider and config
        $provider       = $this->sR->getSetting('open_banking_provider');
        $providerConfig = $provider ? $this->getOpenBankingProviderConfig($provider) : null;

        // Determine if provider is 'wonderful'
        $isWonderful = 'wonderful' === $provider;

        // Prepare view data
        $viewData = [
            'alert'                  => $this->alert(),
            'balance'                => $balance,
            'client_chosen_gateway'  => $client_chosen_gateway,
            'client_on_invoice'      => $cR->repoClientquery($invoice->getClient_id()),
            'disable_form'           => $disable_form,
            'invoice'                => $invoice,
            'inv_url_key'            => $url_key,
            'is_overdue'             => $is_overdue,
            'json_encoded_items'     => Json::encode($items_array),
            'companyLogo'            => $this->renderPartialAsStringCompanyLogo(),
            'partial_client_address' => $this->viewRenderer->renderPartialAsString(
                '//invoice/client/partial_client_address',
                ['client' => $cR->repoClientquery($invoice->getClient_id())],
            ),
            'payment_method' => $payment_method_for_this_invoice,
            'provider'       => $provider,
            'title'          => 'Open Banking is enabled',
            'total'          => $total,
        ];

        if ($isWonderful) {
            $details = $this->openBankingPaymentService->paymentStatusAndDetails($url_key, (int) $balance, (int) $total, $invoice, $items_array);
            $data = (array) $details['data'];
            // Wonderful requires an authToken, not an authUrl
            $viewData['wonderfulId'] = (string) $data['id'];
            $viewData['amountFormatted'] = (string) $data['amount_formatted'];
            $viewData['reference'] = (string) $data['reference'];
            $viewData['createdAt'] = (string) $data['created_at'];
            $viewData['updatedAt'] = (string) $data['updated_at'];
            $viewData['status'] = (string) $data['status'];
            $viewData['authToken'] = true;
            $viewData['paymentLink'] = (string) $data['pay_link'];
        } else {
            // Other providers use authUrl
            $authUrl               = $this->openBankingPaymentService->getAuthUrlForProvider($providerConfig, $url_key);
            $viewData['authUrl']   = $authUrl;
            $viewData['returnUrl'] = ['paymentinformation/paymentinformation_openbanking', ['url_key' => $url_key]];
        }

        return $this->viewRenderer->render('invoice/payment_information_openbanking', $viewData);
    }

    /**
     * Related logic: see https://developer.amazon.com/docs/amazon-pay-api-v2/checkout-session.html#create-checkout-session.
     */
    public function amazon_complete(Request $request, CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse|Response
    {
        $invoice_url_key = $currentRoute->getArgument('url_key');
        if (null === $invoice_url_key) {
            return $this->webService->getNotFoundResponse();
        }

        $invoice = $this->iR->repoUrl_key_guest_count($invoice_url_key) > 0
            ? $this->iR->repoUrl_key_guest_loaded($invoice_url_key)
            : null;

        if (null === $invoice) {
            return $this->webService->getNotFoundResponse();
        }

        $query_params = $request->getQueryParams();
        /** @var string $query_params['amazonCheckoutSessionId'] */
        $checkout_session_id = $query_params['amazonCheckoutSessionId'] ?? null;

        if (null === $checkout_session_id) {
            return $this->webService->getNotFoundResponse();
        }

        $sandbox_url_array = $this->sR->sandbox_url_array();

        // Use service to check completion status and handle invoice updates
        $result = $this->amazonPayPaymentService->handleCallback([
            'amazonCheckoutSessionId' => $checkout_session_id,
            'invoice'                 => $invoice, // Pass the entity if needed in service
            'iR'                      => $this->iR,
            'iaR'                     => $this->iaR,
        ]);

        // Update invoice/payment status if successful
        if ($result['success']) {
            $view_data = [
                'render' => $this->viewRenderer->renderPartialAsString(
                    '//invoice/setting/payment_message',
                    [
                        'heading'     => $this->translator->translate('payment.information.amazon.payment.session.complete') . $checkout_session_id,
                        'message'     => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                        'url'         => 'inv/url_key',
                        'url_key'     => $invoice_url_key,
                        'gateway'     => 'Amazon_Pay',
                        'sandbox_url' => $sandbox_url_array['amazon_pay'],
                    ],
                ),
            ];
        } else {
            $view_data = [
                'render' => $this->viewRenderer->renderPartialAsString(
                    '//invoice/setting/payment_message',
                    [
                        'heading'     => $this->translator->translate('payment.information.amazon.payment.session.incomplete'),
                        'message'     => $result['message'] ?? ($this->translator->translate('payment') . ':' . $this->translator->translate('incomplete')),
                        'url'         => 'inv/url_key',
                        'url_key'     => $invoice_url_key,
                        'gateway'     => 'Amazon_Pay',
                        'sandbox_url' => $sandbox_url_array['amazon_pay'],
                    ],
                ),
            ];
        }

        return $this->viewRenderer->render('payment_completion_page', $view_data);
    }

    public function openbanking_oauth_complete(Request $request, CurrentRoute $currentRoute): Response
    {
        $url_key        = $currentRoute->getArgument('url_key');
        $query_params   = $request->getQueryParams();
        $code           = (string) $query_params['code'];
        $state          = (string) $query_params['state'];
        $codeVerifier   = (string) $this->session->get('code_verifier');
        $provider       = $this->sR->getSetting('open_banking_provider');
        $providerConfig = $provider ? $this->getOpenBankingProviderConfig($provider) : null;

        if (null !== $providerConfig && (strlen($code) > 0) && (strlen($url_key ?? '') > 0) && $codeVerifier) {
            $this->openBankingOauthClient->setAuthUrl((string) $providerConfig['authUrl']);
            $this->openBankingOauthClient->setTokenUrl((string) $providerConfig['tokenUrl']);
            $this->openBankingOauthClient->setScope(isset($providerConfig['scope']) ? (string) $providerConfig['scope'] : null);

            // Exchange code for token
            try {
                $token = $this->openBankingOauthClient->fetchAccessTokenWithCurlAndCodeVerifier(
                    $request,
                    $code,
                    [
                        'redirect_uri'  => $this->urlGenerator->generateAbsolute('paymentinformation/openbanking_oauth_complete', ['url_key' => $url_key]),
                        'code_verifier' => $codeVerifier,
                    ],
                );
                // You now have $token, proceed with further Open Banking API calls (e.g., to initiate payment)

                $this->flashMessage('success', 'Open Banking authentication successful.');
                // TODO: Implement actual payment initiation here

                return $this->viewRenderer->render('payment_completion_page', [
                    'render' => '<h2>Open Banking payment authorized.</h2>',
                ]);
            } catch (\Throwable $e) {
                $this->flashMessage('error', 'Open Banking authentication failed: ' . $e->getMessage());
            }
        }

        return $this->webService->getNotFoundResponse();
    }

    public function wonderful_complete(CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse|Response
    {
        $urlKey = $currentRoute->getArgument('url_key');
        $ref = $currentRoute->getArgument('ref');
        $view_data = [
            'render' => $this->viewRenderer->renderPartialAsString(
                '//invoice/setting/payment_message',
                [
                    'heading'     => sprintf($this->translator->translate('online.payment.payment.successful'), $ref ?? 'No ref provided'),
                    'message'     => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                    'url'         => 'inv/url_key',
                    'url_key'     => $urlKey,
                    'gateway'     => 'Wonderful',
                    'sandbox_url' => '',
                ],
            ),
        ];
        return $this->viewRenderer->render('payment_completion_page', $view_data);
    }

    public function inform(Request $request, CurrentRoute $currentRoute, cR $cR, iiR $iiR, pmR $pmR): Response
    {
        $client_chosen_gateway = $currentRoute->getArgument('gateway');
        if (null !== $client_chosen_gateway) {
            $url_key = $currentRoute->getArgument('url_key');
            if (null !== $url_key) {
                $sandbox_url_array = $this->sR->sandbox_url_array();
                $d                 = strtolower($client_chosen_gateway);
                $datehelper        = new DateHelper($this->sR);
                // initialize disable_form variable
                $disable_form = false;
                $invoice      = $this->iR->repoUrl_key_guest_loaded($url_key);
                if (null == $invoice) {
                    return $this->webService->getNotFoundResponse();
                }
                $invoice_id = $invoice->getId();
                // Json encode items
                /** @psalm-suppress PossiblyNullArgument $invoice_id */
                $items       = $iiR->repoInvquery($invoice_id);
                $items_array = [];
                /** @var InvItem $item */
                foreach ($items as $item) {
                    $items_array[] = (string) $item->getId() . ' ' . ($item->getName() ?? '');
                }
                $invoice_amount_record = $this->iaR->repoInvquery((int) $invoice_id);
                if (null !== $invoice_amount_record) {
                    $balance = $invoice_amount_record->getBalance();
                    $total   = $invoice_amount_record->getTotal();
                    // Load details that will go with the swipe payment intent
                    $yii_invoice_array = [
                        'id'          => $invoice_id,
                        'balance'     => $balance,
                        'customer_id' => $invoice->getClient_id(),
                        'customer'    => ($invoice->getClient()?->getClient_name() ?? '') . ' ' . ($invoice->getClient()?->getClient_surname() ?? ''),
                        // Default currency is needed to generate a payment intent
                        'currency'       => !empty($this->sR->getSetting('currency_code')) ? strtolower($this->sR->getSetting('currency_code')) : 'gbp',
                        'customer_email' => $invoice->getClient()?->getClient_email(),
                        // Keep a record of the invoice items in description
                        'description' => Json::encode($items_array),
                        'number'      => $invoice->getNumber(),
                        'url_key'     => $invoice->getUrl_key(),
                    ];
                    // Check if the invoice is payable
                    if (0.00 == $balance) {
                        $this->flashMessage('warning', $this->translator->translate('already.paid'));
                        $disable_form = true;
                    }
                    // Get additional invoice information
                    $payment_method_for_this_invoice = $pmR->repoPaymentMethodquery((string) $invoice->getPayment_method());
                    if (null !== $payment_method_for_this_invoice) {
                        $is_overdue = ($balance > 0.00 && strtotime($invoice->getDate_due()->format('Y-m-d')) < time() ? true : false);
                        if ($balance > 0 && $total > 0) {
                            $payment_method_name = $payment_method_for_this_invoice->getName();
                            if (null !== $payment_method_name) {
                                $payment_method = $this->sR->mollieSupportedPaymentMethodArray();
                                return $this->pciCompliantGatewayInForms(
                                    $d,
                                    $request,
                                    $client_chosen_gateway,
                                    $url_key,
                                    $balance,
                                    $cR,
                                    $invoice,
                                    (int) $invoice_id,
                                    $items_array,
                                    $yii_invoice_array,
                                    $payment_method,
                                    $disable_form,
                                    $is_overdue,
                                    $payment_method_name,
                                    $total,
                                    $sandbox_url_array,
                                );
                            } // $payment_method_name
                        } // $balance
                    } // null!==$payment_method_for_this_invoice
                } // null!==$invoice_amount_record
                // !$invoice else line 319
            } // null!==$url_key line 312
        } // null!==$client_chosen_gateway line 310

        return $this->webService->getNotFoundResponse();
    }

    public function pciCompliantGatewayInForms(
        string $d,
        Request $request,
        string $client_chosen_gateway,
        string $url_key,
        float $balance,
        cR $cR,
        Inv $invoice,
        int $invoice_id,
        array $items_array,
        array $yii_invoice_array,
        array $payment_method,
        bool $disable_form,
        bool $is_overdue,
        string $payment_method_for_this_invoice,
        float $total,
        array $sandbox_url_array,
    ): Response {
        if (null !== $invoice->getNumber()) {
            if ('1' === $this->sR->getSetting('gateway_' . $d . '_enabled')) {
                switch ($client_chosen_gateway) {
                    case 'OpenBanking':
                        return $this->openBankingInForm(
                            $client_chosen_gateway,
                            $url_key,
                            $balance,
                            $cR,
                            $invoice,
                            $items_array,
                            $disable_form,
                            $is_overdue,
                            $payment_method_for_this_invoice,
                            $total,
                        );
                    case 'Amazon_Pay':
                        return $this->amazonInForm(
                            $client_chosen_gateway,
                            $url_key,
                            $balance,
                            $cR,
                            $invoice,
                            $items_array,
                            $disable_form,
                            $is_overdue,
                            $payment_method_for_this_invoice,
                            $total,
                        );
                    case 'Stripe':
                        return $this->stripeInForm(
                            $client_chosen_gateway,
                            $url_key,
                            $balance,
                            $cR,
                            $invoice,
                            $items_array,
                            $yii_invoice_array,
                            $disable_form,
                            $is_overdue,
                            $payment_method_for_this_invoice,
                            $total,
                        );
                    case 'Braintree':
                        return $this->brainTreeInForm(
                            $request,
                            $client_chosen_gateway,
                            $url_key,
                            $balance,
                            $cR,
                            $invoice,
                            $invoice_id,
                            $items_array,
                            $disable_form,
                            $is_overdue,
                            $payment_method_for_this_invoice,
                            $total,
                            $sandbox_url_array,
                        );
                    case 'Mollie':
                        // locale is in the format en_GB as opposed to default en
                        $mollie_locale = $this->sR->getSetting('gateway_mollie_locale');

                        return $this->mollieInForm(
                            $client_chosen_gateway,
                            $url_key,
                            $balance,
                            $cR,
                            $invoice,
                            $items_array,
                            $yii_invoice_array,
                            $payment_method = 'creditcard',
                            $mollie_locale,
                            $disable_form,
                            $is_overdue,
                            $payment_method_for_this_invoice,
                            $total,
                        );
                }
            }
        } else {
            $this->flashMessage('danger', $this->translator->translate('number.no'));
        }

        return $this->webService->getNotFoundResponse();
    }

    public function amazonInForm(
        string $client_chosen_gateway,
        string $url_key,
        float $balance,
        cR $cR,
        Inv $invoice,
        array $items_array,
        bool $disable_form,
        bool $is_overdue,
        string $payment_method_for_this_invoice,
        float $total,
    ): Response {
        // Let service check for private.pem and return error message if missing
        $pemCheck = $this->amazonPayPaymentService->checkPrivatePemFile();
        if (null !== $pemCheck) {
            $this->flashMessage('warning', (string) $pemCheck['message']);

            return $this->viewRenderer->render(
                '//invoice/setting/payment_message',
                [
                    'heading' => '',
                    'message' => 'Amazon_Pay private.pem File Not Downloaded from Amazon and saved in Pem_unique_folder as private.pem',
                    'url'     => 'inv/url_key',
                    'url_key' => $url_key,
                    'gateway' => 'Amazon_Pay',
                ],
            );
        }

        // Get Amazon Pay button data from the service
        $amazonPayButton = $this->amazonPayPaymentService->getButtonData($invoice, $url_key, $balance);

        $amazon_pci_view_data = [
            'alert'                  => $this->alert(),
            'amazonPayButton'        => $amazonPayButton,
            'balance'                => $balance,
            'client_chosen_gateway'  => $client_chosen_gateway,
            'client_on_invoice'      => $cR->repoClientquery($invoice->getClient_id()),
            'crypt'                  => $this->crypt,
            'disable_form'           => $disable_form,
            'invoice'                => $invoice,
            'inv_url_key'            => $url_key,
            'is_overdue'             => $is_overdue,
            'json_encoded_items'     => Json::encode($items_array),
            'companyLogo'            => $this->renderPartialAsStringCompanyLogo(),
            'partial_client_address' => $this->viewRenderer
                ->renderPartialAsString(
                    '//invoice/client/partial_client_address',
                    ['client' => $cR->repoClientquery($invoice->getClient_id())],
                ),
            'payment_method' => $payment_method_for_this_invoice,
            'return_url'     => ['paymentinformation/amazon_complete', ['url_key' => $url_key]],
            'title'          => 'Amazon Pay is enabled',
            'total'          => $total,
        ];

        return $this->viewRenderer->render('payment_information_amazon_pci', $amazon_pci_view_data);
    }

    public function braintreeInForm(
        Request $request,
        string $client_chosen_gateway,
        string $url_key,
        float $balance,
        cR $cR,
        Inv $invoice,
        int $invoice_id,
        array $items_array,
        bool $disable_form,
        bool $is_overdue,
        string $payment_method_for_this_invoice,
        float $total,
        array $sandbox_url_array,
    ): Response {
        // Check if Braintree is properly configured
        if (!$this->braintreePaymentService->isConfigured()) {
            $this->flashMessage('warning', 'Braintree payment gateway is not properly configured.');

            return $this->webService->getNotFoundResponse();
        }

        // Create or find customer
        if (!$this->braintreePaymentService->findOrCreateCustomer($invoice)) {
            $this->flashMessage('warning', 'Unable to create or find customer in Braintree.');
        }

        // Generate client token
        $clientToken = $this->braintreePaymentService->generateClientToken();
        if (empty($clientToken)) {
            $this->flashMessage('warning', 'Unable to generate Braintree client token.');

            return $this->webService->getNotFoundResponse();
        }

        $merchantId = $this->braintreePaymentService->getMerchantId();

        // Return the view
        $braintree_pci_view_data = [
            'alert'                  => $this->alert(),
            'return_url'             => ['paymentinformation/braintree_complete', ['url_key' => $url_key]],
            'balance'                => $balance,
            'body'                   => $request->getParsedBody() ?? [],
            'client_on_invoice'      => $cR->repoClientquery($invoice->getClient_id()),
            'json_encoded_items'     => Json::encode($items_array),
            'client_token'           => $clientToken,
            'disable_form'           => $disable_form,
            'client_chosen_gateway'  => $client_chosen_gateway,
            'invoice'                => $invoice,
            'inv_url_key'            => $url_key,
            'is_overdue'             => $is_overdue,
            'partial_client_address' => $this->viewRenderer
                ->renderPartialAsString(
                    '//invoice/client/partial_client_address',
                    ['client' => $cR->repoClientquery($invoice->getClient_id())],
                ),
            'payment_method' => $payment_method_for_this_invoice,
            'total'          => $total,
            'action'         => ['paymentinformation/form', ['url_key' => $url_key, 'gateway' => 'Braintree']],
            'companyLogo'    => $this->renderPartialAsStringCompanyLogo(),
            'braintreeLogo'  => $this->renderPartialAsStringBrainTreeLogo($merchantId),
            'title'          => 'Braintree - PCI Compliant - Version' . $this->braintreePaymentService->getVersion() . ' - is enabled. ',
        ];

        if (Method::POST === $request->getMethod()) {
            $body               = $request->getParsedBody() ?? [];
            $paymentMethodNonce = (string) ($body['payment_method_nonce'] ?? '');

            // Process transaction using service
            $transactionResult = $this->braintreePaymentService->processTransaction($balance, $paymentMethodNonce);

            if ($transactionResult['success']) {
                $payment_method = 4;
                $invoice->setPayment_method($payment_method);
                $invoice->setStatus_id(4);

                /** @var InvAmount $invoice_amount_record */
                $invoice_amount_record = $this->iaR->repoInvquery((int) $invoice->getId());
                if (null !== $invoice_amount_record->getTotal()) {
                    // The invoice amount has been paid => balance on the invoice is zero and the paid amount is full
                    $invoice_amount_record->setBalance(0.00);
                    $invoice_amount_record->setPaid($invoice_amount_record->getTotal() ?? 0.00);
                    $this->iaR->save($invoice_amount_record);
                    $this->record_online_payments_and_merchant(
                        // Reference
                        $invoice->getNumber() ?? $this->translator->translate('number.no'),
                        (string) $invoice_id,
                        $balance ?: 0.00,
                        $payment_method,
                        $invoice->getNumber() ?? $this->translator->translate('number.no'),
                        'Braintree',
                        'braintree',
                        $url_key,
                        true,
                        $sandbox_url_array,
                    );
                } // null!==$invoice
            }

            $view_data = [
                'render' => $this->viewRenderer->renderPartialAsString('//invoice/setting/payment_message', ['heading' => '',
                    // https://developer.paypal.com/braintree/docs/reference/general/result-objects
                    'message' => $transactionResult['success']
                        ? sprintf($this->translator->translate('online.payment.payment.successful'), $invoice->getNumber() ?? '')
                        : sprintf($this->translator->translate('online.payment.payment.failed'), $invoice->getNumber() ?? ''),
                    'url'         => 'inv/url_key',
                    'url_key'     => $url_key,
                    'gateway'     => 'Braintree',
                    'sandbox_url' => $sandbox_url_array['braintree'],
                ]),
            ];
            $this->iR->save($invoice);

            return $this->viewRenderer->render('payment_completion_page', $view_data);
        } // request->getMethod Braintree

        return $this->viewRenderer->render('payment_information_braintree_pci', $braintree_pci_view_data);
    }

    /**
     * Handles Braintree payment completion
     * Note: Braintree payments are typically processed directly in braintreeInForm method,
     * but this endpoint exists for consistency and potential webhook handling.
     */
    public function braintree_complete(Request $request, CurrentRoute $currentRoute): Response
    {
        $invoice_url_key = $currentRoute->getArgument('url_key');
        if (null !== $invoice_url_key) {
            $sandbox_url_array = $this->sR->sandbox_url_array();

            // Get the invoice data
            if ($this->iR->repoUrl_key_guest_count($invoice_url_key) > 0) {
                $invoice = $this->iR->repoUrl_key_guest_loaded($invoice_url_key);
            } else {
                return $this->webService->getNotFoundResponse();
            }

            if ($invoice) {
                $invoiceNumber = $invoice->getNumber() ?? 'Unknown';

                // For Braintree, transactions are typically completed directly in the form POST
                // This completion handler is primarily for consistency with other payment methods
                $view_data = [
                    'render' => $this->viewRenderer->renderPartialAsString(
                        '//invoice/setting/payment_message',
                        [
                            'heading'     => sprintf($this->translator->translate('online.payment.payment.successful'), $invoiceNumber),
                            'message'     => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                            'url'         => 'inv/url_key',
                            'url_key'     => $invoice_url_key,
                            'gateway'     => 'Braintree',
                            'sandbox_url' => $sandbox_url_array['braintree'],
                        ],
                    ),
                ];

                return $this->viewRenderer->render('payment_completion_page', $view_data);
            }
        }

        return $this->webService->getNotFoundResponse();
    }

    public function mollieInForm(
        string $client_chosen_gateway,
        string $url_key,
        float $balance,
        cR $cR,
        Inv $invoice,
        array $items_array,
        array $yii_invoice_array,
        string $payment_method,
        string $locale,
        bool $disable_form,
        bool $is_overdue,
        string $payment_method_for_this_invoice,
        float $total,
    ): Response {
        /**
         * All endpoints are initialized in the MollieClient const.
         *
         * @see https://github.com/mollie/mollie-api-php/blob/master/examples/payments/create-payment.php
         * @see https://github.com/mollie/mollie-api-php/tree/master
         */
        $mollieClient = new MollieClient();
        // Return the view
        if ('1' === $this->sR->getSetting('gateway_mollie_enabled') && (false == $this->mollieSetTestOrLiveApiKey($mollieClient))) {
            $this->flashMessage('warning', $this->translator->translate('payment.gateway.mollie.api.key.needs.to.be.setup'));

            return $this->webService->getNotFoundResponse();
        }
        if ('1' === $this->sR->getSetting('gateway_mollie_enabled') && (true == $this->mollieSetTestOrLiveApiKey($mollieClient))) {
            $this->flashMessage('success', $this->translator->translate('payment.gateway.mollie.api.key.has.been.setup'));
        }
        $payment = $this->mollieApiClientCreatePayment(
            $mollieClient,
            $yii_invoice_array,
            $payment_method,
            $url_key,
            $locale,
        );
        $mollie_pci_view_data = [
            'alert'                      => $this->alert(),
            'return_url'                 => ['inv/url_key', ['url_key' => $url_key]],
            'balance'                    => $balance,
            'client_on_invoice'          => $cR->repoClientquery($invoice->getClient_id()),
            'pci_client_publishable_key' => $this->crypt->decode($this->sR->getSetting('gateway_mollie_publishableKey')),
            'json_encoded_items'         => Json::encode($items_array),
            'disable_form'               => $disable_form,
            'client_chosen_gateway'      => $client_chosen_gateway,
            'invoice'                    => $invoice,
            'payment'                    => $payment,
            'inv_url_key'                => $url_key,
            'is_overdue'                 => $is_overdue,
            'partial_client_address'     => $this->viewRenderer->renderPartialAsString(
                '//invoice/client/partial_client_address',
                [
                    'client' => $cR->repoClientquery($invoice->getClient_id()),
                ],
            ),
            'payment_methods'        => $mollieClient->methods->allEnabled(),
            'invoice_payment_method' => $payment_method_for_this_invoice ?: $this->translator->translate('none'),
            'total'                  => $total,
            'companyLogo'            => $this->renderPartialAsStringCompanyLogo(),
            'mollieLogo'             => $this->renderPartialAsStringMollieLogo(),
            'title'                  => $this->mollieClientVersionString() . ' - PCI Compliant - is enabled. ',
        ];

        return $this->viewRenderer->render('payment_information_mollie_pci', $mollie_pci_view_data);
    }

    private function mollieSetTestOrLiveApiKey(MollieClient $mollieClient): bool
    {
        /** @var string $testOrLiveApiKey */
        $testOrLiveApiKey = !empty($this->sR->getSetting('gateway_mollie_testOrLiveApiKey')) ? $this->crypt->decode($this->sR->getSetting('gateway_mollie_testOrLiveApiKey'))
                       : '';
        !empty($this->sR->getSetting('gateway_mollie_testOrLiveApiKey')) ? $mollieClient->setApiKey($testOrLiveApiKey) : '';

        return !empty($this->sR->getSetting('gateway_mollie_testOrLiveApiKey')) ? true : false;
    }

    private function mollieClientVersionString(): string
    {
        $array_version = (new MollieClient())->getVersionStrings();
        return implode($array_version);
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
        /*
         * @var string $yii_invoice['id']
         */
        try {
            /**
             * Visa Card Number: 4543 4740 0224 9996.
             *
             * @see https://docs.mollie.com/overview/testing#testing-card-payments
             * @see https://docs.mollie.com/overview/testing#testing-different-types-of-cards
             * @see https://github.com/mollie/mollie-api-php
             */
            $amount = (float) $yii_invoice['balance'];

            return $mollieClient->payments->create([
                'amount' => [
                    'currency' => strtoupper((string) $yii_invoice['currency']),
                    // 2 decimal places required
                    'value' => ($amount > 0) ? number_format($amount, 2) : 0.00,
                ],
                // Mollie locale array derived eg. en_GB
                'locale'      => $locale,
                'method'      => $paymentMethod,
                'description' => $yii_invoice['description'],
                // When the customer clicks on the pay Now button in payment_information_mollie_pci.php
                // with url from $payment->getCheckOutUrl() they will be redirected to e.g.
                // https://www.mollie.com/checkout/credit-card/embedded/x2ieJoYgPQ
                'redirectUrl' => $this->urlGenerator->generateAbsolute('paymentinformation/mollie_complete', ['url_key' => $urlKey, '_language' => (string) $this->session->get('_language')]),
                // 'webhookUrl' => 'optional'
                // 'cancelUrl' => 'optional',
                // 'restrictPaymentMethodsToCountry' => 'optional'
                'metadata' => [
                    'invoice_id'          => $yii_invoice['id'],
                    'invoice_customer_id' => $yii_invoice['customer_id'],
                    'invoice_number'      => $yii_invoice['number'] ?: '',
                    'invoice_url_key'     => $urlKey,
                    'receipt_email'       => $yii_invoice['customer_email'],
                    'order_id'            => time(),
                ],
            ]);
            // return to MollieForm and build url for paynow button i.e. $payment->getCheckOutUrl()
            // which will direct the customer to Mollie's payment site => pci Compliant ... no credit
            // card details touch our site. Once the customer makes payment on the Mollie website
            // they will be redirected to the redirectUrl above
        } catch (MollieException) {
            /*
             * Previously: echo "API call failed here in function paymentinformation/mollieApiClientCreatePayment ". htmlspecialchars($e->getMessage());
             * Related logic: see https://cwe.mitre.org/data/definitions/200.html
             * An exception object flows to the echo statement and is leaked to the attacker.
             * This may disclose important information about the application to an attacker.
             * Courtesy of Snyk
             */
        }

        return $this->webService->getNotFoundResponse();
    }

    public function mollie_complete(CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse|Response
    {
        // Redirect to the invoice using the url key
        $url_key               = $currentRoute->getArgument('url_key');
        $heading               = '';
        $payment_method        = 1;
        $metadataInvoiceUrlKey = '';
        if (null !== $url_key) {
            $sandbox_url_array = $this->sR->sandbox_url_array();
            // Get the invoice data
            /** @var Inv $invoice */
            $invoice       = $this->iR->repoUrl_key_guest_loaded($url_key);
            $invoiceNumber = null !== $invoice->getNumber() ?: 'unknown';
            $mollie        = new MollieClient();
            // We will iterate through all the payments until the urlKey matches
            // with our metadata invoice_url_key stored by Mollie
            $lastPayment = new MolliePayment($mollie);
            if ($this->mollieSetTestOrLiveApiKey($mollie)) {
                // Get all the payments made on the Mollie Website by our customer
                $payments = $mollie->payments->page();
                /**
                 * @var MolliePayment $payment
                 */
                foreach ($payments as $payment) {
                    /**
                     * Related logic: see https://www.php.net/manual/en/class.stdclass.php
                     * Related logic: see .\vendor\mollie\mollie-api-php\src\Resources\Payment.php.
                     *
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

                /*
                 * Related logic: see vendor\mollie\mollie-api-php\examples\payments\webhook.php
                 */
                if ($lastPayment->isPaid() && !$lastPayment->hasRefunds() && !$lastPayment->hasChargebacks()) {
                    $invoice->setStatus_id(4);
                    // 1 None, 2 Cash, 3 Cheque, 4 Card / Direct-debit - Succeeded, 5 Card / Direct-debit - Processing, 6 Card / Direct-debit - Customer Ready
                    $payment_method = 4;
                    $invoice->setPayment_method(4);
                    $heading = sprintf($this->translator->translate('online.payment.payment.successful'), (string) $invoiceNumber);
                    $this->iR->save($invoice);
                    /** @var int $invoice->getId() */
                    $invoice_amount_record = $this->iaR->repoInvquery((int) $invoice->getId());
                    /** @var InvAmount $invoice_amount_record */
                    $balance = $invoice_amount_record->getBalance();
                    if (null !== $balance) {
                        // The invoice amount has been paid => balance on the invoice is zero and the paid amount is full
                        $invoice_amount_record->setBalance(0);
                        $invoice_amount_record->setPaid($invoice_amount_record->getTotal() ?? 0.00);
                        $this->iaR->save($invoice_amount_record);
                        $this->record_online_payments_and_merchant(
                            // Reference
                            (string) $invoiceNumber . '-' . $lastPayment->status,
                            $invoice_amount_record->getInv_id(),
                            $balance > 0.00 ? $balance : 0.00,
                            // Card / Direct Debit - Customer Ready => 6
                            $payment_method,
                            (string) $invoiceNumber,
                            'Mollie',
                            'mollie',
                            $metadataInvoiceUrlKey,
                            true,
                            $sandbox_url_array,
                        );

                        $view_data = [
                            'render' => $this->viewRenderer->renderPartialAsString(
                                '//invoice/paymentinformation/payment_message',
                                [
                                    'heading'     => $heading,
                                    'message'     => $this->translator->translate('payment') . ':' . $this->translator->translate('complete') . 'Payment Id: ' . $paymentId,
                                    'url'         => 'inv/url_key',
                                    'url_key'     => $metadataInvoiceUrlKey, 'gateway' => 'Mollie',
                                    'sandbox_url' => $sandbox_url_array['mollie'],
                                ],
                            ),
                        ];

                        return $this->viewRenderer->render('payment_completion_page', $view_data);
                    } // null!==$balance
                } else {
                    /*
                     * all-0, draft-1, sent-2, viewed-3, paid-4, overdue-5, unpaid-6, reminder-7, letter-8,
                     * claim-9, judgement-10, enforcement-11, write-off-12
                     * Related logic: see src\Invoice\Inv\InvRepository
                     */
                    $invoice->setStatus_id(6);

                    // 1 None, 2 Cash, 3 Cheque, 4 Card / Direct-debit - Succeeded, 5 Card / Direct-debit - Processing, 6 Card / Direct-debit - Customer Ready
                    $payment_method = 5;
                    $invoice->setPayment_method(5);
                    $heading = sprintf(
                        $this->translator->translate('online.payment.payment.failed'),
                        (string) $invoiceNumber .
                                       ' ' .
                                       $this->translator->translate('payment.gateway.mollie.api.payment.id') .
                                       $paymentId,
                    );
                    $this->iR->save($invoice);
                    $view_data = [
                        'render' => $this->viewRenderer->renderPartialAsString(
                            '//invoice/paymentinformation/payment_message',
                            [
                                'heading'     => $heading,
                                'message'     => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                                'url'         => 'inv/url_key',
                                'url_key'     => $metadataInvoiceUrlKey, 'gateway' => 'Mollie',
                                'sandbox_url' => $sandbox_url_array['mollie'],
                            ],
                        ),
                    ];

                    return $this->viewRenderer->render('payment_completion_page', $view_data);
                }
            }
        } // null!==$metadataInvoiceUrlKey

        return $this->webService->getNotFoundResponse();
    }

    public function stripeInForm(
        string $client_chosen_gateway,
        string $url_key,
        float $balance,
        cR $cR,
        Inv $invoice,
        array $items_array,
        array $yii_invoice_array,
        bool $disable_form,
        bool $is_overdue,
        string $payment_method_for_this_invoice,
        float $total,
    ): Response {
        // Get Stripe keys and client secret from service
        $publishableKey = $this->stripePaymentService->getPublishableKey();
        $clientSecret   = $this->stripePaymentService->createPaymentIntent($yii_invoice_array);

        $stripe_pci_view_data = [
            'alert'                      => $this->alert(),
            'return_url'                 => ['paymentinformation/stripe_complete', ['url_key' => $url_key]],
            'balance'                    => $balance,
            'client_on_invoice'          => $cR->repoClientquery($invoice->getClient_id()),
            'pci_client_publishable_key' => $publishableKey,
            'json_encoded_items'         => Json::encode($items_array),
            'client_secret'              => $clientSecret,
            'disable_form'               => $disable_form,
            'client_chosen_gateway'      => $client_chosen_gateway,
            'invoice'                    => $invoice,
            'inv_url_key'                => $url_key,
            'is_overdue'                 => $is_overdue,
            'partial_client_address'     => $this->viewRenderer
                ->renderPartialAsString(
                    '//invoice/client/partial_client_address',
                    ['client' => $cR->repoClientquery($invoice->getClient_id())],
                ),
            'payment_method' => $payment_method_for_this_invoice ?: 'None',
            'total'          => $total,
            'companyLogo'    => $this->renderPartialAsStringCompanyLogo(),
            'title'          => Stripe::getApiVersion() . ' - PCI Compliant - is enabled. ',
        ];

        return $this->viewRenderer->render('payment_information_stripe_pci', $stripe_pci_view_data);
    }

    public function stripe_complete(Request $request, CurrentRoute $currentRoute): Response
    {
        $invoice_url_key = $currentRoute->getArgument('url_key');
        if (null !== $invoice_url_key) {
            $sandbox_url_array = $this->sR->sandbox_url_array();
            $invoice           = $this->iR->repoUrl_key_guest_loaded($invoice_url_key);
            if (null === $invoice) {
                return $this->webService->getNotFoundResponse();
            }
            $invoiceNumber               = (null !== $invoice->getNumber()) ?: 'unknown';
            $query_params                = $request->getQueryParams();
            $redirect_status_from_stripe = (string) ($query_params['redirect_status'] ?? '');
            $result                      = $this->stripePaymentService->handleCompletion($invoice, $redirect_status_from_stripe);
            $invoice->setStatus_id((int) $result['status_id']);
            $invoice->setPayment_method((int) $result['payment_method']);
            $this->iR->save($invoice);
            /** @var int $invoice->getId() */
            $invoice_amount_record = $this->iaR->repoInvquery((int) $invoice->getId());
            /** @var InvAmount $invoice_amount_record */
            $balance = $invoice_amount_record->getBalance();
            if (null !== $balance) {
                // The invoice amount has been paid => balance on the invoice is zero and the paid amount is full
                $invoice_amount_record->setBalance(0);
                $invoice_amount_record->setPaid($invoice_amount_record->getTotal() ?? 0.00);
                $this->iaR->save($invoice_amount_record);
                $this->record_online_payments_and_merchant(
                    // Reference
                    (string) $invoiceNumber . '-' . $redirect_status_from_stripe,
                    $invoice_amount_record->getInv_id(),
                    $balance ?: 0.00,
                    // Card / Direct Debit - Customer Ready => 6
                    (int) $result['payment_method'],
                    (string) $invoiceNumber,
                    'Stripe',
                    'stripe',
                    $invoice_url_key,
                    true,
                    $sandbox_url_array,
                );
                $heading = 'succeeded' == $redirect_status_from_stripe ?
                  sprintf($this->translator->translate('online.payment.payment.successful'), (string) $invoiceNumber)
                  : sprintf($this->translator->translate('online.payment.payment.failed'), (string) $invoiceNumber . ' ' . ((string) $result['message'] ?: ''));
                $view_data = [
                    'render' => $this->viewRenderer->renderPartialAsString(
                        '//invoice/paymentinformation/payment_message',
                        [
                            'heading'     => $heading,
                            'message'     => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                            'url'         => 'inv/url_key',
                            'url_key'     => $invoice_url_key, 'gateway' => 'Stripe',
                            'sandbox_url' => $sandbox_url_array['stripe'],
                        ],
                    ),
                ];

                return $this->viewRenderer->render('payment_completion_page', $view_data);
            } // null!==$balance

            return $this->webService->getNotFoundResponse();
        } // null!== $invoice_url_key

        return $this->webService->getNotFoundResponse();
    }

    public function get_stripe_pci_client_secret(array $yii_invoice): ?string
    {
        $payment_intent = \Stripe\PaymentIntent::create([
            // convert the float amount to cents
            'amount'   => (int) round(((float) $yii_invoice['balance'] ?: 0.00) * 100),
            'currency' => (string) $yii_invoice['currency'],
            // include the payment methods you have chosen listed in dashboard.stripe.com eg. card, bacs direct debit,
            // googlepay etc.
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            // 'customer' => $yii_invoice['customer'],
            // 'description' => $yii_invoice['description'],
            'receipt_email' => (string) $yii_invoice['customer_email'],
            'metadata'      => [
                'invoice_id'             => (string) $yii_invoice['id'],
                'invoice_customer_id'    => (string) $yii_invoice['customer_id'],
                'invoice_number'         => (string) $yii_invoice['number'] ?: '',
                'invoice_payment_method' => '',
                'invoice_url_key'        => (string) $yii_invoice['url_key'],
            ],
        ]);

        return $payment_intent->client_secret;
    }

    private function record_online_payments_and_merchant(
        string $reference,
        string $invoice_id,
        float $balance,
        int $invoice_payment_method,
        string $invoice_number,
        string $driver,
        string $d,
        string $invoice_url_key,
        bool $response,
        array $sandbox_url_array,
    ): \Yiisoft\DataResponse\DataResponse {
        if ($response) {
            $payment_note = $this->translator->translate('transaction.reference') . ': ' . $reference . "\n";
            $payment_note .= $this->translator->translate('payment.provider') . ': ' . ucwords(str_replace('_', ' ', $d));

            // Set invoice to paid
            $payment_array = [
                'inv_id'            => $invoice_id,
                'payment_date'      => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'amount'            => $balance,
                'payment_method_id' => $invoice_payment_method,
                'note'              => $payment_note,
            ];

            $payment = new Payment();
            $this->paymentService->addPayment_via_payment_handler($payment, $payment_array);

            $payment_success_msg = sprintf($this->translator->translate('online.payment.payment.successful'), $invoice_number);

            // Save gateway response
            $successful_merchant_response_array = [
                'inv_id'                       => $invoice_id,
                'merchant_response_successful' => true,
                'merchant_response_date'       => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'merchant_response_driver'     => $driver,
                'merchant_response'            => $payment_success_msg,
                'merchant_response_reference'  => $reference,
            ];

            $merchant_response = new Merchant();
            $this->merchantService
                ->saveMerchant_via_payment_handler($merchant_response, $successful_merchant_response_array);

            // Redirect user and display the success message
            $this->flashMessage('success', $payment_success_msg);

            return $this->factory->createResponse(
                $this->viewRenderer->renderPartialAsString(
                    '//invoice/setting/payment_message',
                    [
                        'heading'     => '',
                        'message'     => $payment_success_msg,
                        'url'         => 'inv/url_key', 'url_key' => $invoice_url_key,
                        'gateway'     => $driver,
                        'sandbox_url' => $sandbox_url_array[$d],
                    ],
                ),
            );
        }
        // Payment failed
        // Save the response in the database
        $payment_failure_msg = sprintf($this->translator->translate('online.payment.payment.failed'), $invoice_number);

        $unsuccessful_merchant_response_array = [
            'inv_id'                       => $invoice_id,
            'merchant_response_successful' => false,
            'merchant_response_date'       => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
            'merchant_response_driver'     => $driver,
            'merchant_response'            => $payment_failure_msg,
            'merchant_response_reference'  => $reference,
        ];

        $merchant_response = new Merchant();
        $this->merchantService
            ->saveMerchant_via_payment_handler(
                $merchant_response,
                $unsuccessful_merchant_response_array,
            );

        // Redirect user and display the success message
        $this->flashMessage('warning', $payment_failure_msg);

        return $this->factory->createResponse(
            $this->viewRenderer->renderPartialAsString(
                '//invoice/setting/payment_message',
                [
                    'heading'     => '',
                    'message'     => $payment_failure_msg,
                    'url'         => 'inv/url_key',
                    'url_key'     => $invoice_url_key,
                    'gateway'     => $driver,
                    'sandbox_url' => $sandbox_url_array[$d],
                ],
            ),
        );
    }

    public function renderPartialAsStringCompanyLogo(): string
    {
        $companies           = $this->compR->findAllPreloaded();
        $companyPrivates     = $this->cPR->findAllPreloaded();
        $companyLogoFileName = '';
        /**
         * @var Company $company
         */
        foreach ($companies as $company) {
            if ('1' == $company->getCurrent()) {
                /**
                 * @var CompanyPrivate $private
                 */
                foreach ($companyPrivates as $private) {
                    if ($private->getCompany_id() == (string) $company->getId()) {
                        $companyLogoFileName = $private->getLogo_filename();
                    }
                }
            }
        }
        $src = (null !== $companyLogoFileName ? '/logo/' . $companyLogoFileName : '/site/logo.png');

        return $this->viewRenderer->renderPartialAsString('//invoice/paymentinformation/logo/companyLogo', [
            'src' => $src,
            // if debug_mode == '1' => reveal the source in the tooltip
            'tooltipTitle' => '1' == $this->sR->getSetting('debug_mode') ? $src : '',
        ]);
    }

    public function renderPartialAsStringBraintreeLogo(string $merchantId): string
    {
        return $this->viewRenderer->renderPartialAsString('//invoice/paymentinformation/logo/braintreeLogo', [
            'merchantId' => $merchantId,
        ]);
    }

    public function renderPartialAsStringMollieLogo(): string
    {
        return $this->viewRenderer->renderPartialAsString('//invoice/paymentinformation/logo/mollieLogo');
    }
}
