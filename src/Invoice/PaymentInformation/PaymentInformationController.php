<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\User\UserService;
//Helpers
use App\Invoice\Helpers\DateHelper;
//Entities
use App\Invoice\Entity\Company;
use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\Merchant;
use App\Invoice\Entity\Payment;
use App\Invoice\Entity\Setting;
use App\Invoice\Helpers\Telegram\TelegramHelper;
// Libraries
use App\Invoice\Libraries\Crypt;
//Psr
use Psr\Log\LoggerInterface as Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Repositories
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Company\CompanyRepository as compR;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository as cPR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as pmR;
use App\Invoice\Setting\SettingRepository as sR;
// Services
use App\Invoice\Merchant\MerchantService;
use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\Payment\PaymentService;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use Vjik\TelegramBot\Api\FailResult;
// Yiisoft
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Mollie\Api\Resources\Payment as MolliePayment;
use Mollie\Api\MollieApiClient as MollieClient;
use Mollie\Api\Exceptions\ApiException as MollieException;
use Stripe\Stripe;

final class PaymentInformationController
{
    use FlashMessage;

    private Crypt $crypt;

    public function __construct(
        private DataResponseFactoryInterface $factory,
        private Flash $flash,
        private MerchantService $merchantService,
        private StripePaymentService $stripePaymentService,
        private BraintreePaymentService $braintreePaymentService,
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
        $this->factory = $factory;
        $this->merchantService = $merchantService;
        $this->stripePaymentService = $stripePaymentService;
        $this->braintreePaymentService = $braintreePaymentService;
        $this->paymentService = $paymentService;
        $this->session = $session;
        $this->flash = $flash;
        $this->iaR = $iaR;
        $this->iR = $iR;
        $this->sR = $sR;
        $this->urlGenerator = $urlGenerator;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/paymentinformation')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/paymentinformation')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->webService = $webService;
        $this->crypt = new Crypt();
        $this->compR = $compR;
        $this->cPR = $cPR;
        $this->logger = $logger;
        $this->telegramToken = $this->sR->getSetting('telegram_token');
    }

    // If the checkbox 'Omnipay version' has been checked under Setting...View...Online Payment
    // for an enabled gateway, it means https://github.com/thephpleague/omnipay gateway is being used.
    // PCI compliance is NOT GUARANTEED using the Omnipay versions.
    // Unchecked means that the gateway has to follow PCI compliance testing which is more rigid
    // in terms of credit card detail collection. This ensures that NO credit card details will touch your server.

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash' => $this->flash,
                'errors' => [],
            ]
        );
    }

    // https://developer.amazon.com/docs/amazon-pay-api-v2/checkout-session.html#create-checkout-session

    /**
     * @param Request $request
     * @param currentRoute $currentRoute
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function amazon_complete(Request $request, CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse|Response
    {
        // Redirect to the invoice using the url key
        $invoice_url_key = $currentRoute->getArgument('url_key');
        /** @var Inv $invoice */
        if (null !== $invoice_url_key) {
            $sandbox_url_array = $this->sR->sandbox_url_array();
            // Get the invoice data
            if ($this->iR->repoUrl_key_guest_count($invoice_url_key) > 0) {
                $invoice = $this->iR->repoUrl_key_guest_loaded($invoice_url_key);
            } else {
                return $this->webService->getNotFoundResponse();
            }
            if ($invoice) {
                // InvoiceController/install_default_payment_methods: 4 => Card / Direct - Debit Payment Succeeded
                $invoice_id = $invoice->getId();
                $invoice_number = null !== $invoice->getNumber() ?: 'Unknown';
                $payment_method = 4;
                $invoice->setPayment_method($payment_method);
                $invoice->setStatus_id(4);
                $query_params = $request->getQueryParams();
                // The query param in the returned Url appended by amazon to the CheckoutReviewReturnUrl
                // set in the amazon_payload_json function.
                // ie. https://localhost/invoice/paymentinformation/amazon_complete/{url_key}?amazonCheckoutSessionId=.....
                /** @var string $query_params['amazonCheckoutSessionId'] */
                $checkout_session_id = $query_params['amazonCheckoutSessionId'];
                $this->iR->save($invoice);
                $invoice_amount_record = $this->iaR->repoInvquery((int)$invoice_id);
                if (null !== $invoice_amount_record) {
                    $balance = $invoice_amount_record->getBalance();
                    $total = $invoice_amount_record->getTotal();
                    $inv_amount_inv_id = $invoice_amount_record->getInv_id();
                    // The invoice amount has been paid => balance on the invoice is zero and the paid amount is full
                    $invoice_amount_record->setBalance(0);
                    if (null !== $total) {
                        $invoice_amount_record->setPaid($total);
                    }
                    $this->iaR->save($invoice_amount_record);
                    $this->record_online_payments_and_merchant_for_non_omnipay(
                        $checkout_session_id,
                        $inv_amount_inv_id,
                        $balance ?? 0.00,
                        $payment_method,
                        (string)$invoice_number,
                        'Amazon_Pay',
                        'amazon_pay',
                        $invoice_url_key,
                        // bool
                        true,
                        $sandbox_url_array
                    );
                    if ($checkout_session_id) {
                        $view_data = [
                            'render' => $this->viewRenderer->renderPartialAsString(
                                'setting/payment_message',
                                [
                                    'heading' => $this->translator->translate('payment.information.amazon.payment.session.complete') . $checkout_session_id,
                                    'message' => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                                    'url' => 'inv/url_key',
                                    'url_key' => $invoice_url_key,'gateway' => 'Amazon_Pay',
                                    'sandbox_url' => $sandbox_url_array['amazon_pay'],
                                ]
                            ),
                        ];
                        return $this->viewRenderer->render('payment_completion_page', $view_data);
                    }
                    $view_data = [
                        'render' => $this->viewRenderer->renderPartialAsString(
                            'setting/payment_message',
                            [
                                'heading' => $this->translator->translate('payment.information.amazon.payment.session.incomplete'),
                                'message' => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                                'url' => 'inv/url_key',
                                'url_key' => $invoice_url_key,'gateway' => 'Amazon_Pay',
                                'sandbox_url' => $sandbox_url_array['amazon_pay'],
                            ]
                        ),
                    ];
                    return $this->viewRenderer->render('payment_completion_page', $view_data);
                } //$invoice_amount_record
            } //$invoice
        } // null!==$invoice_url_key
        return $this->webService->getNotFoundResponse();
    }

    // https://developer.amazon.com/docs/amazon-pay-checkout/add-the-amazon-pay-button.html#2-generate-the-create-checkout-session-payload

    /**
     * @param string $url_key
     *
     * @return false|string
     */
    private function amazon_payload_json(string $url_key): string|false
    {
        $payload_array = [
            'webCheckoutDetails' => [
                // Input: Setting...Views...Online Payment...Amazon Pay
                'checkoutReviewReturnUrl' => $this->sR->getSetting('gateway_amazon_pay_returnUrl') . '/' . $url_key,
            ],
            'storeId' => $this->crypt->decode($this->sR->getSetting('gateway_amazon_pay_storeId')),
            'scopes' => [
                'name',
                'email',
                'phoneNumber',
                // Not needed since customer can retrieve bill from downloadable pdf
                'billingAddress',
            ],
        ];
        return json_encode($payload_array);
    }

    /**
     * @return string
     */
    private function amazon_private_key_file(): string
    {
        $aliases = $this->sR->get_amazon_pem_file_folder_aliases();
        $targetPath = $aliases->get('@pem_file_unique_folder');
        // 04-12-2022
        // Below private key file automatically downloaded to your browser in
        // left hand corner when creating API keys on
        // https://sellercentral-europe.amazon.com/external-payments/integration-central
        // Point 5
        // eg. 'AmazonPay_SANDBOX-AGQNCVAR7LO44CKBVHJWB4AB.pem' renamed to private.pem;
        $original_file_name = 'private.pem';
        return $targetPath . '/' . $original_file_name;
    }

    // https://developer.amazon.com/docs/amazon-pay-checkout/add-the-amazon-pay-button.html#2-generate-the-create-checkout-session-payload
    // Step 3: Sign the payload

    /**
     * @param string $url_key
     * @return string
     */
    private function amazon_signature(string $url_key): string
    {
        $amazonpay_config = [
            'public_key_id' => $this->crypt->decode($this->sR->getSetting('gateway_amazon_pay_publicKeyId')),
            'private_key' => $this->amazon_private_key_file(),
            'region' => $this->amazon_get_region(),
            'sandbox' => $this->sR->getSetting('gateway_amazon_pay_sandbox') === '1' ? true : false,
        ];
        $client = new \Amazon\Pay\API\Client($amazonpay_config);
        // For testing purposes
        // $signature = $client->testPrivateKeyIntegrity()
        //           ? $client->generateButtonSignature($this->amazon_payload_json($url_key))
        //           : '';
        //
        /**
         * @psalm-suppress MixedReturnStatement
         */
        return $client->generateButtonSignature($this->amazon_payload_json($url_key));
    }

    /**
     * @return string
     */
    public function amazon_get_region(): string
    {
        $regions = $this->sR->amazon_regions();
        // Region North America => na, Japan => jp, Europe => eu
        $region = $this->sR->getSetting('gateway_amazon_pay_region');
        if (!in_array($region, $regions)) {
            $region_value = 'eu';
        } else {
            /** @var string $regions[$region] */
            $region_value = $regions[$region];
        }
        return $region_value;
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param cR $cR
     * @param iiR $iiR
     * @param pmR $pmR
     * @return Response
     */
    public function inform(Request $request, CurrentRoute $currentRoute, cR $cR, iiR $iiR, pmR $pmR): Response
    {
        // PCI Compliance required => use version '0'
        // Omnipay is used => version = '1', Omnipay is not used => version ='0'
        // Uppercase underscore gateway key name eg. Amazon_Pay retrieved from SettingRepository
        // payment_gateways_enabled_DriverList and the driver the customer has chosen on
        // InvController/view view.php
        $client_chosen_gateway = $currentRoute->getArgument('gateway');
        if (null !== $client_chosen_gateway) {
            $url_key = $currentRoute->getArgument('url_key');
            if (null !== $url_key) {
                $sandbox_url_array = $this->sR->sandbox_url_array();
                $d = strtolower($client_chosen_gateway);
                $datehelper = new DateHelper($this->sR);
                // initialize disable_form variable
                $disable_form = false;
                $invoice = $this->iR->repoUrl_key_guest_loaded($url_key);
                if (null == $invoice) {
                    return $this->webService->getNotFoundResponse();
                }
                $invoice_id = $invoice->getId();
                // Json encode items
                /** @psalm-suppress PossiblyNullArgument $invoice_id */
                $items = $iiR->repoInvquery($invoice_id);
                $items_array = [];
                /** @var InvItem $item */
                foreach ($items as $item) {
                    $items_array[] = (string)$item->getId() . ' ' . ($item->getName() ?? '');
                }
                $invoice_amount_record = $this->iaR->repoInvquery((int)$invoice_id);
                if (null !== $invoice_amount_record) {
                    $balance = $invoice_amount_record->getBalance();
                    $total = $invoice_amount_record->getTotal();
                    // Load details that will go with the swipe payment intent
                    $yii_invoice_array = [
                        'id' => $invoice_id,
                        'balance' => $balance,
                        'customer_id' => $invoice->getClient_id(),
                        'customer' => ($invoice->getClient()?->getClient_name() ?? '') . ' ' . ($invoice->getClient()?->getClient_surname() ?? ''),
                        // Default currency is needed to generate a payment intent
                        'currency' => !empty($this->sR->getSetting('currency_code')) ? strtolower($this->sR->getSetting('currency_code')) : 'gbp',
                        'customer_email' => $invoice->getClient()?->getClient_email(),
                        // Keep a record of the invoice items in description
                        'description' => Json::encode($items_array),
                        'number' => $invoice->getNumber(),
                        'url_key' => $invoice->getUrl_key(),
                    ];
                    // Check if the invoice is payable
                    if ($balance == 0.00) {
                        $this->flashMessage('warning', $this->translator->translate('already.paid'));
                        $disable_form = true;
                    }
                    // Get additional invoice information
                    $payment_method_for_this_invoice = $pmR->repoPaymentMethodquery((string)$invoice->getPayment_method());
                    if (null !== $payment_method_for_this_invoice) {
                        $is_overdue = ($balance > 0.00 && strtotime($invoice->getDate_due()->format('Y-m-d')) < time() ? true : false);
                        // Omnipay versions: 1. Stripe
                        if ($this->sR->getSetting('gateway_' . $d . '_version') === '1') {
                            // Setup Stripe omnipay if enabled
                            if ($this->sR->getSetting('gateway_stripe_enabled') === '1' && ($this->stripe_setApiKey() == false) && ($d == 'stripe')) {
                                $this->flashMessage(
                                    'warning',
                                    $this->translator->translate('payment.information.stripe.api.key')
                                );
                            }
                            if ($this->sR->getSetting('gateway_amazon_pay_enabled') === '1' && ($d == 'amazon_pay')) {
                                $this->flashMessage(
                                    'warning',
                                    $this->translator->translate('payment.information.amazon.no.omnipay.version')
                                );
                            }
                            if ($this->sR->getSetting('gateway_braintree_enabled') === '1' && ($d == 'braintree')) {
                                $this->flashMessage(
                                    'warning',
                                    $this->translator->translate('payment.information.braintree.no.omnipay.version')
                                );
                            }

                            // Return the view
                            $omnipay_view_data = [
                                'alert' => $this->alert(),
                                'balance' => $balance,
                                'client_on_invoice' => $cR->repoClientquery($invoice->getClient_id()),
                                'disable_form' => $disable_form,
                                // Note: No PaymentInformation Entity exists => sensitive information kept off server
                                'form' => new PaymentInformationForm(),
                                'client_chosen_gateway' => $client_chosen_gateway,
                                'invoice' => $invoice,
                                'inv_url_key' => $url_key,
                                'is_overdue' => $is_overdue,
                                'partial_client_address' => $this->viewRenderer
                                    ->renderPartialAsString(
                                        '//invoice/client/partial_client_address',
                                        ['client' => $cR->repoClientquery($invoice->getClient_id())]
                                    ),
                                'payment_method' => $payment_method_for_this_invoice->getName() ?? $this->translator->translate('payment.information.none'),
                                'total' => $total,
                                'actionName' => 'paymentinformation/make_payment_omnipay',
                                'actionArguments' => ['url_key' => $url_key],
                                'companyLogo' => $this->renderPartialAsStringCompanyLogo(),
                                'title' => $this->translator->translate('payment.information.omnipay.driver.being.used'),
                            ];
                            return $this->viewRenderer->render('payment_information_omnipay', $omnipay_view_data);
                        } // Omnipay version
                        // If the Omnipay version is unchecked it is PCI compliant
                        if ($balance > 0 && $total > 0) {
                            $payment_method_name = $payment_method_for_this_invoice->getName();
                            if (null !== $payment_method_name) {
                                if ($this->sR->getSetting('gateway_' . $d . '_version') === '0') {
                                    $payment_method = $this->sR->mollieSupportedPaymentMethodArray();
                                    return $this->pciCompliantGatewayInForms(
                                        $d,
                                        $request,
                                        $client_chosen_gateway,
                                        $url_key,
                                        $balance,
                                        $cR,
                                        $invoice,
                                        (int)$invoice_id,
                                        $items_array,
                                        $yii_invoice_array,
                                        $payment_method,
                                        $disable_form,
                                        $is_overdue,
                                        $payment_method_name,
                                        $total,
                                        $sandbox_url_array
                                    );
                                }
                            } // $payment_method_name
                        } // $balance
                    } //null!==$payment_method_for_this_invoice
                } //null!==$invoice_amount_record
                //!$invoice else line 319
            } //null!==$url_key line 312
        } //null!==$client_chosen_gateway line 310
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
        array $sandbox_url_array
    ): Response {
        if (null !== $invoice->getNumber()) {
            if ($this->sR->getSetting('gateway_' . $d . '_enabled') === '1') {
                switch ($client_chosen_gateway) {
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
                            $total
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
                            $total
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
                            $sandbox_url_array
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
                            $total
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
        float $total
    ): Response {
        //$this->flash('warning','Testing: You will need to create a buyer test account under sellercental.');
        // Return the view
        $aliases = $this->sR->get_amazon_pem_file_folder_aliases();
        if (!file_exists($aliases->get('@pem_file_unique_folder') . '/private.pem')) {
            $this->flashMessage('warning', 'Amazon_Pay private.pem File Not Downloaded from Amazon and saved in Pem_unique_folder as private.pem');
            return $this->viewRenderer->render(
                'setting/payment_message',
                [
                    'heading' => '',
                    'message' => 'Amazon_Pay private.pem File Not Downloaded from Amazon and saved in Pem_unique_folder as private.pem',
                    'url' => 'inv/url_key',
                    'url_key' => $url_key,
                    'gateway' => 'Amazon_Pay',
                ]
            );
        }
        $client_language = $invoice->getClient()?->getClient_language();
        $amazon_languages = $this->sR->amazon_languages();
        $client_in_language = 'en_GB';
        if (null !== $client_language) {
            $client_in_language = $amazon_languages[$client_language];
        }
        $amazon_pci_view_data = [
            'alert' => $this->alert(),
            // https://developer.amazon.com/docs/amazon-pay-checkout/add-the-amazon-pay-button.html#2-generate-the-create-checkout-session-payload
            'amazonPayButton' => [
                'amount' => $balance,
                // format eg. en_GB
                'checkoutLanguage' => in_array(
                    $client_language,
                    $amazon_languages
                ) ?
                                               $client_in_language : 'en_GB',
                // Settings...View...General...Currency Code
                'ledgerCurrency' => $this->sR->getSetting('currency_code'),
                'merchantId' => $this->crypt->decode($this->sR->getSetting('gateway_amazon_pay_merchantId')),
                'payloadJSON' => $this->amazon_payload_json($url_key),
                // PayOnly / PayAndShip / SignIn
                'productType' => 'PayOnly',
                'publicKeyId' => $this->crypt->decode($this->sR->getSetting('gateway_amazon_pay_publicKeyId')),
                'signature' => $this->amazon_signature($url_key),
            ],
            'balance' => $balance,
            // inv/view view.php gateway choices with url's eg. inv/url_key/{url_key}/{gateway}
            'client_chosen_gateway' => $client_chosen_gateway,
            'client_on_invoice' => $cR->repoClientquery($invoice->getClient_id()),
            'crypt' => $this->crypt,
            'disable_form' => $disable_form,
            'invoice' => $invoice,
            'inv_url_key' => $url_key,
            'is_overdue' => $is_overdue,
            'json_encoded_items' => Json::encode($items_array),
            'companyLogo' => $this->renderPartialAsStringCompanyLogo(),
            'partial_client_address' => $this->viewRenderer
                                             ->renderPartialAsString(
                                                 '//invoice/client/partial_client_address',
                                                 ['client' => $cR->repoClientquery($invoice->getClient_id())]
                                             ),
            'payment_method' => $payment_method_for_this_invoice,
            'return_url' => ['paymentinformation/amazon_complete',['url_key' => $url_key]],
            'title' => 'Amazon Pay is enabled',
            'total' => $total,
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
        array $sandbox_url_array
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
            'alert' => $this->alert(),
            'return_url' => ['paymentinformation/braintree_complete', ['url_key' => $url_key]],
            'balance' => $balance,
            'body' => $request->getParsedBody() ?? [],
            'client_on_invoice' => $cR->repoClientquery($invoice->getClient_id()),
            'json_encoded_items' => Json::encode($items_array),
            'client_token' => $clientToken,
            'disable_form' => $disable_form,
            'client_chosen_gateway' => $client_chosen_gateway,
            'invoice' => $invoice,
            'inv_url_key' => $url_key,
            'is_overdue' => $is_overdue,
            'partial_client_address' => $this->viewRenderer
                                             ->renderPartialAsString(
                                                 '//invoice/client/partial_client_address',
                                                 ['client' => $cR->repoClientquery($invoice->getClient_id())]
                                             ),
            'payment_method' => $payment_method_for_this_invoice,
            'total' => $total,
            'action' => ['paymentinformation/form',['url_key' => $url_key,'gateway' => 'Braintree']],
            'companyLogo' => $this->renderPartialAsStringCompanyLogo(),
            'braintreeLogo' => $this->renderPartialAsStringBrainTreeLogo($merchantId),
            'title' => 'Braintree - PCI Compliant - Version' . $this->braintreePaymentService->getVersion() . ' - is enabled. ',
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            $paymentMethodNonce = (string)($body['payment_method_nonce'] ?? '');
            
            // Process transaction using service
            $transactionResult = $this->braintreePaymentService->processTransaction($balance, $paymentMethodNonce);
            
            if ($transactionResult['success']) {
                $payment_method = 4;
                $invoice->setPayment_method($payment_method);
                $invoice->setStatus_id(4);
                
                /** @var InvAmount $invoice_amount_record */
                $invoice_amount_record = $this->iaR->repoInvquery((int)$invoice->getId());
                if (null !== $invoice_amount_record->getTotal()) {
                    // The invoice amount has been paid => balance on the invoice is zero and the paid amount is full
                    $invoice_amount_record->setBalance(0.00);
                    $invoice_amount_record->setPaid($invoice_amount_record->getTotal() ?? 0.00);
                    $this->iaR->save($invoice_amount_record);
                    $this->record_online_payments_and_merchant_for_non_omnipay(
                        // Reference
                        $invoice->getNumber() ?? $this->translator->translate('number.no'),
                        (string)$invoice_id,
                        $balance ?: 0.00,
                        $payment_method,
                        $invoice->getNumber() ?? $this->translator->translate('number.no'),
                        'Braintree',
                        'braintree',
                        $url_key,
                        true,
                        $sandbox_url_array
                    );
                } //null!==$invoice
            }

            $view_data = [
                'render' => $this->viewRenderer->renderPartialAsString('//invoice/setting/payment_message', ['heading' => '',
                    //https://developer.paypal.com/braintree/docs/reference/general/result-objects
                    'message' => $transactionResult['success'] 
                        ? sprintf($this->translator->translate('online.payment.payment.successful'), $invoice->getNumber() ?? '')
                        : sprintf($this->translator->translate('online.payment.payment.failed'), $invoice->getNumber() ?? ''),
                    'url' => 'inv/url_key',
                    'url_key' => $url_key,
                    'gateway' => 'Braintree',
                    'sandbox_url' => $sandbox_url_array['braintree'],
                ]),
            ];
            $this->iR->save($invoice);
            return $this->viewRenderer->render('payment_completion_page', $view_data);
        } //request->getMethod Braintree
        return $this->viewRenderer->render('payment_information_braintree_pci', $braintree_pci_view_data);
    }

    /**
     * Handles Braintree payment completion
     * Note: Braintree payments are typically processed directly in braintreeInForm method,
     * but this endpoint exists for consistency and potential webhook handling
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
                        'setting/payment_message',
                        [
                            'heading' => sprintf($this->translator->translate('online.payment.payment.successful'), $invoiceNumber),
                            'message' => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                            'url' => 'inv/url_key',
                            'url_key' => $invoice_url_key,
                            'gateway' => 'Braintree',
                            'sandbox_url' => $sandbox_url_array['braintree'],
                        ]
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
        float $total
    ): Response {
        /**
         * All endpoints are initialized in the MollieClient const
         * @link https://github.com/mollie/mollie-api-php/blob/master/examples/payments/create-payment.php
         * @link https://github.com/mollie/mollie-api-php/tree/master
         */
        $mollieClient = new MollieClient();
        // Return the view
        if ($this->sR->getSetting('gateway_mollie_enabled') === '1' && ($this->mollieSetTestOrLiveApiKey($mollieClient) == false)) {
            $this->flashMessage('warning', $this->translator->translate('payment.gateway.mollie.api.key.needs.to.be.setup'));
            return $this->webService->getNotFoundResponse();
        }
        if ($this->sR->getSetting('gateway_mollie_enabled') === '1' && ($this->mollieSetTestOrLiveApiKey($mollieClient) == true)) {
            $this->flashMessage('success', $this->translator->translate('payment.gateway.mollie.api.key.has.been.setup'));
        }
        $payment = $this->mollieApiClientCreatePayment(
            $mollieClient,
            $yii_invoice_array,
            $payment_method,
            $url_key,
            $locale
        );
        $mollie_pci_view_data = [
            'alert' => $this->alert(),
            'return_url' => ['inv/url_key', ['url_key' => $url_key]],
            'balance' => $balance,
            'client_on_invoice' => $cR->repoClientquery($invoice->getClient_id()),
            'pci_client_publishable_key' => $this->crypt->decode($this->sR->getSetting('gateway_mollie_publishableKey')),
            'json_encoded_items' => Json::encode($items_array),
            'disable_form' => $disable_form,
            'client_chosen_gateway' => $client_chosen_gateway,
            'invoice' => $invoice,
            'payment' => $payment,
            'inv_url_key' => $url_key,
            'is_overdue' => $is_overdue,
            'partial_client_address' =>
                $this->viewRenderer->renderPartialAsString(
                    'client/partial_client_address',
                    [
                        'client' => $cR->repoClientquery($invoice->getClient_id()),
                    ]
                ),
            'payment_methods' => $mollieClient->methods->allEnabled(),
            'invoice_payment_method' => $payment_method_for_this_invoice ?: $this->translator->translate('none'),
            'total' => $total,
            'companyLogo' => $this->renderPartialAsStringCompanyLogo(),
            'mollieLogo' => $this->renderPartialAsStringMollieLogo(),
            'title' => $this->mollieClientVersionString() . ' - PCI Compliant - is enabled. ',
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
     * @param MollieClient $mollieClient
     * @param array $yii_invoice
     * @param string $paymentMethod
     * @param string $urlKey
     * @param string $locale
     * @throws MollieException
     * @return MolliePayment|Response
     */
    public function mollieApiClientCreatePayment(
        MollieClient $mollieClient,
        array $yii_invoice,
        string $paymentMethod,
        string $urlKey,
        string $locale
    ): MolliePayment|Response {
        /**
         * @var string $yii_invoice['id']
         */
        try {
            /**
             * Visa Card Number: 4543 4740 0224 9996
             * @link https://docs.mollie.com/overview/testing#testing-card-payments
             * @link https://docs.mollie.com/overview/testing#testing-different-types-of-cards
             * @link https://github.com/mollie/mollie-api-php
             */

            $amount = (float)$yii_invoice['balance'];
            return $mollieClient->payments->create([
                'amount' => [
                    'currency' => strtoupper((string)$yii_invoice['currency']),
                    // 2 decimal places required
                    'value' => ($amount  > 0) ? number_format($amount, 2) : 0.00,
                ],
                // Mollie locale array derived eg. en_GB
                'locale' => $locale,
                'method' => $paymentMethod,
                'description' => $yii_invoice['description'],
                // When the customer clicks on the pay Now button in payment_information_mollie_pci.php
                // with url from $payment->getCheckOutUrl() they will be redirected to e.g.
                // https://www.mollie.com/checkout/credit-card/embedded/x2ieJoYgPQ
                'redirectUrl' => $this->urlGenerator->generateAbsolute('paymentinformation/mollie_complete', ['url_key' => $urlKey, '_language' => (string)$this->session->get('_language')]),
                // 'webhookUrl' => 'optional'
                // 'cancelUrl' => 'optional',
                // 'restrictPaymentMethodsToCountry' => 'optional'
                'metadata' => [
                    'invoice_id' => $yii_invoice['id'],
                    'invoice_customer_id' => $yii_invoice['customer_id'],
                    'invoice_number' => $yii_invoice['number'] ?: '',
                    'invoice_url_key' => $urlKey,
                    'receipt_email' => $yii_invoice['customer_email'],
                    'order_id' => time(),
                ],
            ]);
            // return to MollieForm and build url for paynow button i.e. $payment->getCheckOutUrl()
            // which will direct the customer to Mollie's payment site => pci Compliant ... no credit
            // card details touch our site. Once the customer makes payment on the Mollie website
            // they will be redirected to the redirectUrl above
        } catch (MollieException) {
            /**
             * Previously: echo "API call failed here in function paymentinformation/mollieApiClientCreatePayment ". htmlspecialchars($e->getMessage());
             * @see https://cwe.mitre.org/data/definitions/200.html
             * An exception object flows to the echo statement and is leaked to the attacker.
             * This may disclose important information about the application to an attacker.
             * Courtesy of Snyk
             */
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function mollie_complete(CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse|Response
    {
        // Redirect to the invoice using the url key
        $url_key = $currentRoute->getArgument('url_key');
        $heading = '';
        $payment_method = 1;
        $metadataInvoiceUrlKey = '';
        if (null !== $url_key) {
            $sandbox_url_array = $this->sR->sandbox_url_array();
            // Get the invoice data
            /** @var Inv $invoice */
            $invoice = $this->iR->repoUrl_key_guest_loaded($url_key);
            $invoiceNumber = null !== $invoice->getNumber() ?: 'unknown';
            $mollie = new MollieClient();
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
                     * @see https://www.php.net/manual/en/class.stdclass.php
                     * @see .\vendor\mollie\mollie-api-php\src\Resources\Payment.php
                     * @var \stdClass $payment->metadata
                     */
                    $metaData = $payment->metadata;

                    $metadataInvoiceUrlKey = (string)$metaData->invoice_url_key;
                    if ($metadataInvoiceUrlKey === $url_key) {
                        $lastPayment = $payment;
                        break;
                    }
                }

                $paymentId = $lastPayment->id;

                /**
                 * @see vendor\mollie\mollie-api-php\examples\payments\webhook.php
                 */
                if ($lastPayment->isPaid() && !$lastPayment->hasRefunds() && !$lastPayment->hasChargebacks()) {
                    $invoice->setStatus_id(4);
                    // 1 None, 2 Cash, 3 Cheque, 4 Card / Direct-debit - Succeeded, 5 Card / Direct-debit - Processing, 6 Card / Direct-debit - Customer Ready
                    $payment_method = 4;
                    $invoice->setPayment_method(4);
                    $heading = sprintf($this->translator->translate('online.payment.payment.successful'), (string)$invoiceNumber);
                    $this->iR->save($invoice);
                    /** @var int $invoice->getId() */
                    $invoice_amount_record = $this->iaR->repoInvquery((int)$invoice->getId());
                    /** @var InvAmount $invoice_amount_record */
                    $balance = $invoice_amount_record->getBalance();
                    if (null !== $balance) {
                        // The invoice amount has been paid => balance on the invoice is zero and the paid amount is full
                        $invoice_amount_record->setBalance(0);
                        $invoice_amount_record->setPaid($invoice_amount_record->getTotal() ?? 0.00);
                        $this->iaR->save($invoice_amount_record);
                        $this->record_online_payments_and_merchant_for_non_omnipay(
                            // Reference
                            (string)$invoiceNumber . '-' . $lastPayment->status,
                            $invoice_amount_record->getInv_id(),
                            $balance > 0.00 ? $balance : 0.00,
                            // Card / Direct Debit - Customer Ready => 6
                            $payment_method,
                            (string)$invoiceNumber,
                            'Mollie',
                            'mollie',
                            $metadataInvoiceUrlKey,
                            true,
                            $sandbox_url_array
                        );

                        $view_data = [
                            'render' => $this->viewRenderer->renderPartialAsString(
                                'paymentinformation/payment_message',
                                [
                                    'heading' => $heading,
                                    'message' => $this->translator->translate('payment') . ':' . $this->translator->translate('complete') . 'Payment Id: ' . $paymentId,
                                    'url' => 'inv/url_key',
                                    'url_key' => $metadataInvoiceUrlKey, 'gateway' => 'Mollie',
                                    'sandbox_url' => $sandbox_url_array['mollie'],
                                ]
                            ),
                        ];
                        return $this->viewRenderer->render('payment_completion_page', $view_data);
                    } //null!==$balance
                } else {
                    /**
                     * all-0, draft-1, sent-2, viewed-3, paid-4, overdue-5, unpaid-6, reminder-7, letter-8,
                     * claim-9, judgement-10, enforcement-11, write-off-12
                     * @see src\Invoice\Inv\InvRepository
                     */
                    $invoice->setStatus_id(6);

                    // 1 None, 2 Cash, 3 Cheque, 4 Card / Direct-debit - Succeeded, 5 Card / Direct-debit - Processing, 6 Card / Direct-debit - Customer Ready
                    $payment_method = 5;
                    $invoice->setPayment_method(5);
                    $heading = sprintf(
                        $this->translator->translate('online.payment.payment.failed'),
                        (string)$invoiceNumber .
                                       ' ' .
                                       $this->translator->translate('payment.gateway.mollie.api.payment.id') .
                                       $paymentId
                    );
                    $this->iR->save($invoice);
                    $view_data = [
                        'render' => $this->viewRenderer->renderPartialAsString(
                            'paymentinformation/payment_message',
                            [
                                'heading' => $heading,
                                'message' => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                                'url' => 'inv/url_key',
                                'url_key' => $metadataInvoiceUrlKey, 'gateway' => 'Mollie',
                                'sandbox_url' => $sandbox_url_array['mollie'],
                            ]
                        ),
                    ];
                    return $this->viewRenderer->render('payment_completion_page', $view_data);
                }
            }
        } //null!==$metadataInvoiceUrlKey
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
        float $total
    ): Response {
        // Get Stripe keys and client secret from service
        $publishableKey = $this->stripePaymentService->getPublishableKey();
        $clientSecret = $this->stripePaymentService->createPaymentIntent($yii_invoice_array);

        $stripe_pci_view_data = [
            'alert' => $this->alert(),
            'return_url' => ['paymentinformation/stripe_complete', ['url_key' => $url_key]],
            'balance' => $balance,
            'client_on_invoice' => $cR->repoClientquery($invoice->getClient_id()),
            'pci_client_publishable_key' => $publishableKey,
            'json_encoded_items' => Json::encode($items_array),
            'client_secret' => $clientSecret,
            'disable_form' => $disable_form,
            'client_chosen_gateway' => $client_chosen_gateway,
            'invoice' => $invoice,
            'inv_url_key' => $url_key,
            'is_overdue' => $is_overdue,
            'partial_client_address' => $this->viewRenderer
                ->renderPartialAsString(
                    '//invoice/client/partial_client_address',
                    ['client' => $cR->repoClientquery($invoice->getClient_id())]
                ),
            'payment_method' => $payment_method_for_this_invoice ?: 'None',
            'total' => $total,
            'companyLogo' => $this->renderPartialAsStringCompanyLogo(),
            'title' => Stripe::getApiVersion() . ' - PCI Compliant - is enabled. ',
        ];
        return $this->viewRenderer->render('payment_information_stripe_pci', $stripe_pci_view_data);
    }

    // Omnipay and PCI Compliant versions use the same ApiKey
    private function stripe_setApiKey(): bool
    {
        /** @var string $sk_test */
        $sk_test = !empty($this->sR->getSetting('gateway_stripe_secretKey')) ? $this->crypt->decode($this->sR->getSetting('gateway_stripe_secretKey'))
                       : '';
        !empty($this->sR->getSetting('gateway_stripe_secretKey')) ? Stripe::setApiKey($sk_test) : '';
        return !empty($this->sR->getSetting('gateway_stripe_secretKey')) ? true : false;
    }

    public function stripe_complete(Request $request, CurrentRoute $currentRoute): Response
    {
        $invoice_url_key = $currentRoute->getArgument('url_key');
        if (null !== $invoice_url_key) {
            $sandbox_url_array = $this->sR->sandbox_url_array();
            $invoice = $this->iR->repoUrl_key_guest_loaded($invoice_url_key);
            if ($invoice === null) {
                return $this->webService->getNotFoundResponse();
            }
            $invoiceNumber = (null !== $invoice->getNumber()) ?: 'unknown';
            $query_params = $request->getQueryParams();
            $redirect_status_from_stripe = (string)($query_params['redirect_status'] ?? '');
            $result = $this->stripePaymentService->handleCompletion($invoice, $redirect_status_from_stripe);
            $invoice->setStatus_id((int)$result['status_id']);
            $invoice->setPayment_method((int)$result['payment_method']);
            $this->iR->save($invoice);
            /** @var int $invoice->getId() */
            $invoice_amount_record = $this->iaR->repoInvquery((int)$invoice->getId());
            /** @var InvAmount $invoice_amount_record */
            $balance = $invoice_amount_record->getBalance();
            if (null !== $balance) {
                // The invoice amount has been paid => balance on the invoice is zero and the paid amount is full
                $invoice_amount_record->setBalance(0);
                $invoice_amount_record->setPaid($invoice_amount_record->getTotal() ?? 0.00);
                $this->iaR->save($invoice_amount_record);
                $this->record_online_payments_and_merchant_for_non_omnipay(
                    // Reference
                    (string)$invoiceNumber . '-' . $redirect_status_from_stripe,
                    $invoice_amount_record->getInv_id(),
                    $balance ?: 0.00,
                    // Card / Direct Debit - Customer Ready => 6
                    (int)$result['payment_method'],
                    (string)$invoiceNumber,
                    'Stripe',
                    'stripe',
                    $invoice_url_key,
                    true,
                    $sandbox_url_array
                );
                $heading = $redirect_status_from_stripe == 'succeeded' ?
                  sprintf($this->translator->translate('online.payment.payment.successful'), (string)$invoiceNumber)
                  : sprintf($this->translator->translate('online.payment.payment.failed'), (string)$invoiceNumber . ' ' . ((string)$result['message'] ?: ''));
                $view_data = [
                    'render' => $this->viewRenderer->renderPartialAsString(
                        'paymentinformation/payment_message',
                        [
                            'heading' => $heading,
                            'message' => $this->translator->translate('payment') . ':' . $this->translator->translate('complete'),
                            'url' => 'inv/url_key',
                            'url_key' => $invoice_url_key,'gateway' => 'Stripe',
                            'sandbox_url' => $sandbox_url_array['stripe'],
                        ]
                    ),
                ];
                return $this->viewRenderer->render('payment_completion_page', $view_data);
            } //null!==$balance
            return $this->webService->getNotFoundResponse();
        } //null!== $invoice_url_key
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param array $yii_invoice
     * @return string|null
     */
    public function get_stripe_pci_client_secret(array $yii_invoice): string|null
    {
        $payment_intent = \Stripe\PaymentIntent::create([
            // convert the float amount to cents
            'amount' => (int) round(((float)$yii_invoice['balance'] ?: 0.00) * 100),
            'currency' => (string) $yii_invoice['currency'],
            // include the payment methods you have chosen listed in dashboard.stripe.com eg. card, bacs direct debit,
            // googlepay etc.
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            //'customer' => $yii_invoice['customer'],
            //'description' => $yii_invoice['description'],
            'receipt_email' => (string) $yii_invoice['customer_email'],
            'metadata' => [
                'invoice_id' => (string) $yii_invoice['id'],
                'invoice_customer_id' => (string) $yii_invoice['customer_id'],
                'invoice_number' => (string) $yii_invoice['number'] ?: '',
                'invoice_payment_method' => '',
                'invoice_url_key' => (string) $yii_invoice['url_key'],
            ],
        ]);
        return $payment_intent->client_secret;
    }

    /**
    * @param Request $payment_request
    * @param CurrentRoute $currentRoute
    * @return Response
    */
    public function make_payment_omnipay(
        Request $payment_request,
        CurrentRoute $currentRoute
    ): Response {
        $yii_invoice_url_key = $currentRoute->getArgument('url_key');
        if (null !== $yii_invoice_url_key) {
            // Get the invoice data
            $invoice = $this->iR->repoUrl_key_guest_count($yii_invoice_url_key) > 0 ? $this->iR->repoUrl_key_guest_loaded($yii_invoice_url_key) : null;
            if (null !== $invoice) {
                $yii_invoice_id = $invoice->getId();
                if (null !== $yii_invoice_id) {
                    // Use the invoice amount repository
                    $invoice_amount_record = $this->iaR->repoInvquery((int)$invoice->getId());
                    if (null !== $invoice_amount_record) {
                        //$yii_invoice_customer_id = $invoice->getClient_id();
                        //$yii_invoice_customer_email = $invoice->getClient()->getClient_email();
                        $yii_invoice_number = $invoice->getNumber();
                        $yii_invoice_payment_method = $invoice->getPayment_method();


                        $balance = $invoice_amount_record->getBalance();
                        if (null !== $balance) {
                            if ($this->iR->repoUrl_key_guest_count($yii_invoice_url_key) === 0) {
                                return $this->webService->getNotFoundResponse();
                            }
                            if ($payment_request->getMethod() === Method::POST) {
                                // Initialize the gateway
                                $body = $payment_request->getParsedBody() ?? [];
                                /** @var array $body['PaymentInformationForm'] */
                                $driver = (string)$body['PaymentInformationForm']['gateway_driver'] ?: '';
                                // eg. Stripe reduced to stripe
                                $d = strtolower($driver);

                                // Get the credit card data
                                $cc_number = (string)$body['PaymentInformationForm']['creditcard_number'] ?: '';
                                $cc_expire_month = (string)$body['PaymentInformationForm']['creditcard_expiry_month'] ?: '';
                                $cc_expire_year = (string)$body['PaymentInformationForm']['creditcard_expiry_year'] ?: '';
                                $cc_cvv = (string)$body['PaymentInformationForm']['creditcard_cvv'] ?: '';

                                $driver_currency = strtolower($this->sR->getSetting('gateway_' . $d . '_currency'));
                                $sandbox_url_array = $this->sR->sandbox_url_array();
                                /**
                                 * @var string $sandbox_url_array[$d]
                                 */
                                $sandbox_url = $sandbox_url_array[$d];
                                return $this->omnipay(
                                    $driver,
                                    $d,
                                    $driver_currency,
                                    $cc_number,
                                    $cc_expire_month,
                                    $cc_expire_year,
                                    $cc_cvv,
                                    $yii_invoice_id,
                                    //$yii_invoice_customer_id,
                                    //$yii_invoice_customer_email,
                                    $yii_invoice_number ?? '',
                                    // 6 => Card / Direct Debit - Customer Ready
                                    $yii_invoice_payment_method ?? 6,
                                    $yii_invoice_url_key,
                                    $balance,
                                    $sandbox_url
                                );
                            }
                            return $this->webService->getNotFoundResponse();
                        } //null!==$balance
                        return $this->webService->getNotFoundResponse();
                    } //null!==$invoice_amount_record
                    return $this->webService->getNotFoundResponse();
                } //null!==$invoice
                return $this->webService->getNotFoundResponse();
            }
            return $this->webService->getNotFoundResponse();
        }//$yii_invoice_url_key
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param string $driver
     * @param string $d
     * @param string $driver_currency
     * @param string $cc_number
     * @param string $cc_expire_month
     * @param string $cc_expire_year
     * @param string $cc_cvv
     * @param string $invoice_id
     * @param string $invoice_number
     * @param int $invoice_payment_method
     * @param string $invoice_url_key
     * @param float $balance
     * @param string $sandbox_url
     * @return Response
     */
    private function omnipay(
        string $driver,
        string $d,
        string $driver_currency,
        string $cc_number,
        string $cc_expire_month,
        string $cc_expire_year,
        string $cc_cvv,
        string $invoice_id,
        //string $invoice_customer_id,
        //string $invoice_customer_email,
        string $invoice_number,
        int $invoice_payment_method,
        string $invoice_url_key,
        float $balance,
        string $sandbox_url
    ): Response {
        $sandbox_url_array = $this->sR->sandbox_url_array();
        /** @var \Omnipay\Common\GatewayInterface $omnipay_gateway */
        $omnipay_gateway = $this->initialize_omnipay_gateway($driver);

        // The $sR->payment_gateways() array now includes a subarray namely:
        // 'version' => array(
        //            'type' => 'checkbox',
        //            'label' => 'Omnipay Version'
        // )
        // eg.
        // Omnipay repository "omnipay/stripe": "*" is being used in composer.json and
        // https://dashboard.stripe.com/settings/integration
        // Setting 'handle card information directly' has been set
        //
        // This avoids exception:
        // see https://stackoverflow.com/questions/46720159/stripe-payment-params-error-type-invalid-request-error
        // and https://dashboard.stripe.com/settings/integration
        if ($cc_number) {
            try {
                $credit_card = new \Omnipay\Common\CreditCard([
                    'number' => $cc_number,
                    'expiryMonth' => $cc_expire_month,
                    'expiryYear' => $cc_expire_year,
                    'cvv' => $cc_cvv,
                ]);
                $credit_card->validate();
            } catch (\Exception $e) {
                // Redirect the user and display failure message
                $this->flashMessage(
                    'error',
                    $this->translator->translate('online.payment.card.invalid') . '<br/>' . $e->getMessage()
                );
                return $this->factory
                    ->createResponse($this->viewRenderer
                                          ->renderPartialAsString(
                                              '//invoice/setting/payment_message',
                                              [
                                                  'heading' => '',
                                                  'message' => $this->translator->translate('online.payment.card.invalid') . '<br/>' . $e->getMessage(),
                                                  'url' => 'paymentinformation/form',
                                                  'url_key' => $invoice_url_key,
                                                  'sandbox_url' => $sandbox_url,
                                              ]
                                          ));
            }
        } else {
            $credit_card = [];
        }

        $request_information = [
            'amount' => $balance,
            'currency' => $driver_currency,
            'card' => $credit_card,
            'description' => sprintf($this->translator->translate('payment.description'), $invoice_number),
            'metadata' => [
                'invoice_number' => $invoice_number,
                'invoice_guest_url' => $invoice_url_key,
            ],
            'returnUrl' => ['paymentinformation/omnipay_payment_return', ['url_key' => $invoice_url_key, 'driver' => $driver]],
            'cancelUrl' => ['paymentinformation/omnipay_payment_cancel', ['url_key' => $invoice_url_key, 'driver' => $driver]],
        ];

        if ($d === 'worldpay') {
            // Additional param for WorldPay
            $request_information['cartId'] = $invoice_number;
        }
        $purchase_send_response = $omnipay_gateway->purchase($request_information)->send();
        $this->session->set($invoice_url_key . '_online_payment', $request_information);

        // For Merchant table inspection and testing purposes $omnipay_gateway->getApiKey() can be used here in place of '[no reference]']
        $reference = $purchase_send_response->getTransactionReference() ?? '[no transation reference]';
        // Process the response
        return $this->record_online_payments_and_merchant_for_omnipay(
            $reference,
            $invoice_id,
            $balance,
            $invoice_payment_method,
            $invoice_number,
            $driver,
            $d,
            $invoice_url_key,
            $purchase_send_response,
            $sandbox_url_array
        );
    }

    /**
     * @param string $reference
     * @param string $invoice_id
     * @param float $balance
     * @param int $invoice_payment_method
     * @param string $invoice_number
     * @param string $driver
     * @param string $d
     * @param string $invoice_url_key
     * @param mixed $response
     * @param array $sandbox_url_array
     * @return Response
     */
    private function record_online_payments_and_merchant_for_omnipay(
        string $reference,
        string $invoice_id,
        float $balance,
        int $invoice_payment_method,
        string $invoice_number,
        string $driver,
        string $d,
        string $invoice_url_key,
        mixed $response,
        array $sandbox_url_array
    ): Response {
        /** @var \Omnipay\Common\Message\RedirectResponseInterface $response */
        if ($response->isSuccessful()) {
            $payment_note = $this->translator->translate('transaction.reference') . ': ' . $reference . "\n";
            $payment_note .= $this->translator->translate('payment.provider') . ': ' . ucwords(str_replace('_', ' ', $d));

            // Set invoice to paid

            $payment_array = [
                'inv_id' => $invoice_id,
                'payment_date' => date('Y-m-d'),
                'payment_amount' => $balance,
                'payment_method_id' => $invoice_payment_method,
                'payment_note' => $payment_note,
            ];

            $payment = new Payment();
            $this->paymentService->addPayment_via_payment_handler($payment, $payment_array);

            $inv = $this->iR->repoInvLoadedquery($invoice_id);
            if (null !== $inv) {
                $invClient = $inv->getClient();
                if (null !== $invClient) {
                    $clientFullName = $invClient->getClient_full_name();
                    if (strlen($this->telegramToken) > 0) {
                        $telegramHelper = new TelegramHelper($this->telegramToken, $this->logger);
                        $telegramBotApi = $telegramHelper->getBotApi();
                        $chatId = $this->sR->getSetting('telegram_chat_id');
                        $telegramToken = $this->sR->getSetting('telegram_token');
                        // send the  successful payment note via telegram bot to the settings ... view... telegram ... chat id including the client's full name and balance
                        if ((strlen($chatId) > 0) && strlen($telegramToken) > 0) {
                            $failResultSendMessage = $telegramBotApi->sendMessage($chatId, $clientFullName . ': ' . (string)$balance . ' : ' . $payment_note);
                            if (!$failResultSendMessage instanceof FailResult) {
                                $this->flashMessage('success', $this->translator->translate('telegram.bot.api.payment.notification.success'));
                            }
                        }
                    } else {
                        if ($this->sR->getSetting('enable_telegram') == '1') {
                            $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.token.not.set'));
                        }
                    }
                }
            }
            $payment_success_msg = sprintf($this->translator->translate('online.payment.payment.successful'), $invoice_number);

            // Save gateway response
            $successful_merchant_response_array = [
                'inv_id' => $invoice_id,
                'merchant_response_successful' => true,
                'merchant_response_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'merchant_response_driver' => $driver,
                'merchant_response' => $payment_success_msg,
                'merchant_response_reference' => $reference,
            ];

            $merchant_response = new Merchant();
            $this->merchantService
                 ->saveMerchant_via_payment_handler(
                     $merchant_response,
                     $successful_merchant_response_array
                 );

            // Redirect user and display the success message
            $this->flashMessage('success', $payment_success_msg);
            return $this->factory->createResponse(
                $this->viewRenderer->renderPartialAsString(
                    'setting/payment_message',
                    [
                        'heading' => '',
                        'message' => $payment_success_msg,
                        'url' => 'inv/url_key','url_key' => $invoice_url_key,
                        'gateway' => $driver,
                        'sandbox_url' => $sandbox_url_array[$d],
                    ]
                )
            );
        }
        if ($response->isRedirect()) {
            // Redirect to offsite payment gateway
            $response->redirect();
        } else {
            // Payment failed
            // Save the response in the database
            $payment_failure_msg = sprintf($this->translator->translate('online.payment.payment.failed'), $invoice_number);

            $unsuccessful_merchant_response_array = [
                'inv_id' => $invoice_id,
                'merchant_response_successful' => false,
                'merchant_response_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'merchant_response_driver' => $driver,
                /** @var \Omnipay\Common\Message\ResponseInterface $response->getMessage() */
                'merchant_response' => $response->getMessage(),
                'merchant_response_reference' => $reference,
            ];

            $merchant_response = new Merchant();
            $this->merchantService
                 ->saveMerchant_via_payment_handler(
                     $merchant_response,
                     $unsuccessful_merchant_response_array
                 );

            // Redirect user and display the success message
            $this->flashMessage('warning', $payment_failure_msg);
            return $this->factory->createResponse(
                $this->viewRenderer->renderPartialAsString(
                    'setting/payment_message',
                    [
                        'heading' => '',
                        'message' => $payment_failure_msg . ' Response: ' . (string)$response->getMessage(),
                        'url' => 'inv/url_key',
                        'url_key' => $invoice_url_key,
                        'gateway' => $driver,
                        'sandbox_url' => $sandbox_url_array[$d],
                    ]
                )
            );
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param string $reference
     * @param string $invoice_id
     * @param float $balance
     * @param int $invoice_payment_method
     * @param string $invoice_number
     * @param string $driver
     * @param string $d
     * @param string $invoice_url_key
     * @param bool $response
     * @param array $sandbox_url_array
     * @return \Yiisoft\DataResponse\DataResponse
     */
    private function record_online_payments_and_merchant_for_non_omnipay(
        string $reference,
        string $invoice_id,
        float $balance,
        int $invoice_payment_method,
        string $invoice_number,
        string $driver,
        string $d,
        string $invoice_url_key,
        bool $response,
        array $sandbox_url_array
    ): \Yiisoft\DataResponse\DataResponse {
        if ($response) {
            $payment_note = $this->translator->translate('transaction.reference') . ': ' . $reference . "\n";
            $payment_note .= $this->translator->translate('payment.provider') . ': ' . ucwords(str_replace('_', ' ', $d));

            // Set invoice to paid
            $payment_array = [
                'inv_id' => $invoice_id,
                'payment_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'amount' => $balance,
                'payment_method_id' => $invoice_payment_method,
                'note' => $payment_note,
            ];

            $payment = new Payment();
            $this->paymentService->addPayment_via_payment_handler($payment, $payment_array);

            $payment_success_msg = sprintf($this->translator->translate('online.payment.payment.successful'), $invoice_number);

            // Save gateway response
            $successful_merchant_response_array = [
                'inv_id' => $invoice_id,
                'merchant_response_successful' => true,
                'merchant_response_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'merchant_response_driver' => $driver,
                'merchant_response' => $payment_success_msg,
                'merchant_response_reference' => $reference,
            ];

            $merchant_response = new Merchant();
            $this->merchantService
                 ->saveMerchant_via_payment_handler($merchant_response, $successful_merchant_response_array);

            // Redirect user and display the success message
            $this->flashMessage('success', $payment_success_msg);
            return $this->factory->createResponse(
                $this->viewRenderer->renderPartialAsString(
                    'setting/payment_message',
                    [
                        'heading' => '',
                        'message' => $payment_success_msg,
                        'url' => 'inv/url_key','url_key' => $invoice_url_key,
                        'gateway' => $driver,
                        'sandbox_url' => $sandbox_url_array[$d],
                    ]
                )
            );
        }
        // Payment failed
        // Save the response in the database
        $payment_failure_msg = sprintf($this->translator->translate('online.payment.payment.failed'), $invoice_number);

        $unsuccessful_merchant_response_array = [
            'inv_id' => $invoice_id,
            'merchant_response_successful' => false,
            'merchant_response_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
            'merchant_response_driver' => $driver,
            'merchant_response' => $payment_failure_msg,
            'merchant_response_reference' => $reference,
        ];

        $merchant_response = new Merchant();
        $this->merchantService
             ->saveMerchant_via_payment_handler(
                 $merchant_response,
                 $unsuccessful_merchant_response_array
             );

        // Redirect user and display the success message
        $this->flashMessage('warning', $payment_failure_msg);
        return $this->factory->createResponse(
            $this->viewRenderer->renderPartialAsString(
                'setting/payment_message',
                [
                    'heading' => '',
                    'message' => $payment_failure_msg,
                    'url' => 'inv/url_key',
                    'url_key' => $invoice_url_key,
                    'gateway' => $driver,
                    'sandbox_url' => $sandbox_url_array[$d],
                ]
            )
        );
    }

    /**
     * @param string $driver
     * @return mixed
     */
    private function initialize_omnipay_gateway(string $driver): mixed
    {
        $d = strtolower($driver);
        // get all the specific drivers settings
        $settings = $this->sR->findAllPreloaded();

        // Load the 'gateway drivers' array
        $gateway_driver_array = $this->sR->active_payment_gateways();
        // Get the specific drivers array from the whole gateway array
        /** @var array $gateway_settings */
        $gateway_settings = $gateway_driver_array[$driver] ?? [];

        $gateway_init = [];
        /** @var Setting $setting */
        foreach ($settings as $setting) {
            // eg gateway_stripe_enabled
            $haystack = $setting->getSetting_key();
            // str_contains($haystack, $needle);
            // eg. str_contains('gateway_stripe_enabled','gateway_stripe_');
            if (str_contains($haystack, 'gateway_' . $d . '_')) {
                // Sanitize the field key
                $first_strip = str_replace('gateway_' . $d . '_', '', $setting->getSetting_key());
                $key = str_replace('gateway_' . $d, '', $first_strip);

                // skip empty key
                if (!$key) {
                    continue;
                }

                // Decode password fields and checkboxes

                /**
                 * @var array $gateway_settings[$key]
                 * @var string $gateway_settings[$key]['type']
                 */

                if (isset($gateway_settings[$key]) && $gateway_settings[$key]['type'] == 'password') {
                    $value = (string)$this->crypt->decode($setting->getSetting_value());
                } elseif (isset($gateway_settings[$key]) && $gateway_settings[$key]['type'] == 'checkbox') {
                    $value = $setting->getSetting_value() == '1' ? true : false;
                } else {
                    $value = $setting->getSetting_value();
                }

                $gateway_init[$key] = $value;
            } //str contains haystack
        }

        // Load Omnipay and initialize the gateway
        $gateway = \Omnipay\Omnipay::create($driver);
        $gateway->initialize($gateway_init);

        return $gateway;
    }

    /**
     * @param string $invoice_url_key
     * @param string $driver
     * @return Response
     */
    public function omnipay_payment_return(string $invoice_url_key, string $driver): Response
    {
        $d = strtolower($driver);
        $sandbox_url_array = $this->sR->sandbox_url_array();
        $payment_msg = '';
        // See if the response can be validated

        $invoice = $this->iR->repoUrl_key_guest_count($invoice_url_key) > 0 ? $this->iR->repoUrl_key_guest_loaded($invoice_url_key) : null;
        if (null !== $invoice) {
            $invoiceNumber = null !== $invoice->getNumber() ? (string)$invoice->getNumber() : 'unknown';
            if ($this->omnipay_payment_validate($invoice_url_key, $driver, false)) {
                // Use the invoice amount repository
                $invoice_amount_record = $this->iaR->repoInvquery((int)$invoice->getId());
                if ($invoice_amount_record) {
                    $balance = $invoice_amount_record->getBalance();
                    if ($this->iR->repoUrl_key_guest_count($invoice_url_key) === 0) {
                        return $this->webService->getNotFoundResponse();
                    }
                    $payment_array = [
                        'inv_id' => $invoice->getId(),
                        'payment_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                        'payment_amount' => $balance,
                        'payment_method_id' => $this->sR->getSetting('gateway_' . $d . '_payment_method') ?: 0,
                    ];

                    $payment = new Payment();
                    $this->paymentService->addPayment_via_payment_handler($payment, $payment_array);

                    $payment_msg = sprintf($this->translator->translate('online.payment.payment.successful'), $invoiceNumber);

                    // Set the success flash message
                    $this->flashMessage('success', $payment_msg);
                } // invoice_amount_record
            } else {
                $payment_msg = sprintf($this->translator->translate('online.payment.payment.failed'), $invoiceNumber);
                // Set the failure flash message
                $this->flashMessage('error', $this->translator->translate('online.payment.payment.failed'));
            }
            // Redirect to guest invoice view with flash message
            return $this->factory->createResponse(
                $this->viewRenderer->renderPartialAsString(
                    'inv/payment_message',
                    [
                        'heading' => '',
                        'message' => $payment_msg,
                        'url' => 'inv/url_key',
                        'url_key' => $invoice_url_key,
                        'sandbox_url' => $sandbox_url_array[$d],
                    ]
                )
            );
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param string $invoice_url_key
     * @param string $driver
     * @param bool $cancelled
     * @return bool
     */
    private function omnipay_payment_validate(string $invoice_url_key, string $driver, bool $cancelled = false): bool
    {
        // Attempt to get the invoice
        // Get the invoice data
        $invoice = $this->iR->repoUrl_key_guest_count($invoice_url_key) > 0 ? $this->iR->repoUrl_key_guest_loaded($invoice_url_key) : null;

        // Use the invoice amount repository

        $payment_success = false;
        if ($invoice) {
            if (!$cancelled) {
                /** @var \Omnipay\Common\GatewayInterface $gateway */
                $gateway = $this->initialize_omnipay_gateway($driver);

                /**
                 * @var array $params['metaData']
                 * @var string $params['metaData']['url_key']
                 */
                $params = [
                    'metaData' => [
                        'url_key' => $this->session->get($invoice->getUrl_key() . '_online_payment'),
                    ],
                ];
                $payment_success = true;
                $response = $gateway->completePurchase($params)->send();
                $message = $response->getMessage() ?? 'No details provided';
                $response_transaction_reference = $response->getTransactionReference();
            } else {
                $message = 'Customer cancelled the purchase process';
                $response_transaction_reference = '';
            }

            // Create the record for ip_merchant_responses
            $successful_merchant_response_array = [
                'inv_id' => $invoice->getId(),
                'merchant_response_successful' => $payment_success,
                'merchant_response_date' => \DateTime::createFromImmutable(new \DateTimeImmutable('now')),
                'merchant_response_driver' => $driver,
                'merchant_response' => $message,
                'merchant_response_reference' => $response_transaction_reference,
            ];

            $merchant_response = new Merchant();
            $this->merchantService
                 ->saveMerchant_via_payment_handler(
                     $merchant_response,
                     $successful_merchant_response_array
                 );

            return true;
        }

        return false;
    }

    /**
     * @param $invoice_url_key
     * @param $driver
     */
    public function omnipay_payment_cancel(string $invoice_url_key, string $driver): \Yiisoft\DataResponse\DataResponse
    {
        // Validate the response
        $this->omnipay_payment_validate($invoice_url_key, $driver, true);

        // Set the cancel flash message
        $this->flashMessage('info', $this->translator->translate('online.payment.payment.cancelled'));

        $d = strtolower($driver);
        $sandbox_url_array = $this->sR->sandbox_url_array();
        // Redirect to guest invoice view with flash message
        return $this->factory->createResponse(
            $this->viewRenderer->renderPartialAsString(
                'inv/payment_message',
                [
                    'heading' => '',
                    'message' => $this->translator->translate('online.payment.payment.cancelled'),
                    'url' => 'inv/url_key',
                    'url_key' => $invoice_url_key,
                    'sandbox_url' => $sandbox_url_array[$d],
                ]
            )
        );
    }

    /**
     * @return string
     */
    public function renderPartialAsStringCompanyLogo(): string
    {
        $companies = $this->compR->findAllPreloaded();
        $companyPrivates = $this->cPR->findAllPreloaded();
        $companyLogoFileName = '';
        /**
         * @var Company $company
         */
        foreach ($companies as $company) {
            if ($company->getCurrent() == '1') {
                /**
                 * @var CompanyPrivate $private
                 */
                foreach ($companyPrivates as $private) {
                    if ($private->getCompany_id() == (string)$company->getId()) {
                        $companyLogoFileName = $private->getLogo_filename();
                    }
                }
            }
        }
        $src = (null !== $companyLogoFileName ? '/logo/' . $companyLogoFileName : '/site/logo.png');
        return $this->viewRenderer->renderPartialAsString('//invoice/paymentinformation/logo/companyLogo', [
            'src' => $src,
            // if debug_mode == '1' => reveal the source in the tooltip
            'tooltipTitle' => $this->sR->getSetting('debug_mode') == '1' ? $src : '',
        ]);
    }

    /**
     * @param string $merchantId
     * @return string
     */
    public function renderPartialAsStringBraintreeLogo(string $merchantId): string
    {
        return $this->viewRenderer->renderPartialAsString('//invoice/paymentinformation/logo/brainTreeLogo', [
            'merchantId' => $merchantId,
        ]);
    }

    /**
     * @return string
     */
    public function renderPartialAsStringMollieLogo(): string
    {
        return $this->viewRenderer->renderPartialAsString('//invoice/paymentinformation/logo/mollieLogo');
    }
}
