<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Invoice\Client\ClientRepository;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\Entity\Payment;
use App\Invoice\Entity\PaymentCustom;
use App\Invoice\Entity\Inv;
use App\Invoice\Helpers\CustomValuesHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\Payment\PaymentService;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\Payment\PaymentForm;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
use App\Invoice\PaymentCustom\PaymentCustomRepository;
use App\Invoice\PaymentCustom\PaymentCustomForm;
use App\Invoice\PaymentCustom\PaymentCustomService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserClient\Exception\NoClientsAssignedToUserException;
use App\Invoice\UserInv\UserInvRepository;
use App\User\User;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class PaymentController
{
    private Session $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private PaymentService $paymentService;
    private PaymentCustomService $paymentCustomService;
    private TranslatorInterface $translator;
    private DataResponseFactoryInterface $factory;

    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        PaymentService $paymentService,
        PaymentCustomService $paymentCustomService,
        TranslatorInterface $translator,
        DataResponseFactoryInterface $factory
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->webService = $webService;
        $this->userService = $userService;
        $this->paymentService = $paymentService;
        $this->paymentCustomService = $paymentCustomService;
        $this->translator = $translator;
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewPayment')
            && !$this->userService->hasPermission('editPayment')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/payment')
                                               ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewPayment')
            && $this->userService->hasPermission('editPayment')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/payment')
                                               ->withLayout('@views/layout/invoice.php');
        }
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ACIR $aciR
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @param InvAmountRepository $iaR
     * @param PaymentMethodRepository $payment_methodRepository
     * @param PaymentCustomRepository $pcR
     * @param PaymentRepository $pmtR
     * @param CustomFieldRepository $cfR
     * @param CustomValueRepository $cvR
     * @param ClientRepository $cR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        ACIR $aciR,
        SettingRepository $settingRepository,
        InvRepository $invRepository,
        InvAmountRepository $iaR,
        PaymentMethodRepository $payment_methodRepository,
        PaymentCustomRepository $pcR,
        PaymentRepository $pmtR,
        CustomFieldRepository $cfR,
        CustomValueRepository $cvR,
        ClientRepository $cR,
        IIR $iiR,
        IIAR $iiaR,
        ITRR $itrR,
    ): Response {
        $open = $invRepository->open();
        $invRepository->open_count() == 0 ? $this->flash_message('danger', $this->translator->translate('invoice.payment.no.invoice.sent')) : '';
        $amounts = [];
        $invoice_payment_methods = [];
        /** @var Inv $open_invoice */
        foreach ($open as $open_invoice) {
            $open_invoice_id = $open_invoice->getId();
            if (null !== $open_invoice_id) {
                $inv_amount = $iaR->repoInvquery((int)$open_invoice_id);
                if (null !== $inv_amount) {
                    $amounts['invoice' . $open_invoice_id] = $settingRepository->format_amount($inv_amount->getBalance());
                }
                $invoice_payment_methods['invoice' . $open_invoice_id] = $open_invoice->getPayment_method();
            }
        }
        $numberHelper = new NumberHelper($settingRepository);
        $payment = new Payment();
        $form = new PaymentForm($payment);
        $paymentCustom = new PaymentCustom();
        $paymentCustomForm = new PaymentCustomForm($paymentCustom);
        $parameters = [
            'actionName' => 'payment/add',
            'actionArguments' => [],
            'alert' => $this->alert(),
            'errors' => [],
            'errorsCustom' => [],
            'form' => $form,
            'openInvsCount' => $invRepository->open_count(),
            'openInvs' => $open,
            // jquery script at bottom of _from to load all amounts
            'amounts' => Json::encode($amounts),
            'invoicePaymentMethods' => Json::encode($invoice_payment_methods),
            'paymentMethods' => $payment_methodRepository->count() > 0 ?
                                $payment_methodRepository->findAllPreloaded() : null,
            'cR' => $cR,
            'iaR' => $iaR,
            'cvH' => new CustomValuesHelper($settingRepository),
            'customFields' => $cfR->repoTablequery('payment_custom'),
            // Applicable to normally building up permanent selection lists eg. dropdowns
            'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('payment_custom')),
            // There will initially be no custom_values attached to this payment until they are filled in the field on the form
            //'payment_custom_values' => $this->payment_custom_values($payment_id,$pcR),
            'paymentCustomValues' => [],
            'paymentCustomForm' => $paymentCustomForm
        ];
        if ($request->getMethod() === Method::POST) {
            // Default payment method is 1 => None
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->paymentService->savePayment($payment, $body);
                    // Once the payment has been saved, retrieve the payment id for the custom fields
                    $payment_id = $payment->getId();
                    $inv_id = $payment->getInv_id();
                    // Recalculate the invoice
                    $numberHelper->calculate_inv($inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $invRepository, $pmtR);
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                    if (isset($body['custom'])) {
                        // Retrieve the custom array
                        /** @var array $custom */
                        $custom = $body['custom'];
                        /**
                         * @var int $custom_field_id
                         * @var array|string $value
                         */
                        foreach ($custom as $custom_field_id => $value) {
                            $paymentCustom = new PaymentCustom();
                            $paymentCustomForm = new PaymentCustomForm($paymentCustom);
                            $paymentCustomInput = [
                                'payment_id' => (int)$payment_id,
                                'custom_field_id' => $custom_field_id,
                                'value' => is_array($value) ? serialize($value) : $value
                            ];
                            if ($formHydrator->populate($paymentCustomForm, $paymentCustomInput)
                                && $paymentCustomForm->isValid()
                                && $this->add_custom_field($payment_id, $custom_field_id, $pcR)) {
                                $this->paymentCustomService->savePaymentCustom($paymentCustom, $paymentCustomInput);
                            }
                            $parameters['errorsCustom'] = $paymentCustomForm->getValidationResult()->getErrorMessagesIndexedByProperty();
                            $parameters['paymentCustomForm'] = $paymentCustomForm;
                        } // foreach
                        if (count($parameters['errorsCustom']) > 0) {
                            return $this->viewRenderer->render('_form', $parameters);
                        }
                    } // isset body['custom']
                    return $this->webService->getRedirectResponse('payment/index');
                } // is_array    
            } // $formHydrator
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        } // request
        return $this->viewRenderer->render('_form', $parameters);
    }

    // If the custom field already exists return false

    /**
     *
     * @param string $payment_id
     * @param int $custom_field_id
     * @param PaymentCustomRepository $pcR
     * @return bool
     */
    public function add_custom_field(string $payment_id, int $custom_field_id, PaymentCustomRepository $pcR): bool
    {
        return ($pcR->repoPaymentCustomCount($payment_id, (string)$custom_field_id) > 0 ? false : true);
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
        'flash' => $this->flash
      ]
        );
    }

    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null
    {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }

    /**
     * @param FormHydrator $formHydrator
     * @param (mixed|string)[] $array
     * @param string $payment_id
     * @param PaymentCustomRepository $pcR
     * @psalm-param array{custom: ''|mixed} $array
     * @return void
     */
    public function custom_fields(FormHydrator $formHydrator, array $array, string $payment_id, PaymentCustomRepository $pcR): void
    {
        if (is_array($array['custom'])) {
            $db_array = [];
            $values = [];
            $arrayCustom = $array['custom'];
            /**
             * @var array $custom
             * @var string $custom['name']
             */
            foreach ($arrayCustom as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    /**
                     * @var string $custom['value']
                     */
                    $values[$matches[1]][] = $custom['value'] ;
                } else {
                    /**
                     * @var string $custom['value']
                     * @var string $custom['name']
                     */
                    $values[$custom['name']] = $custom['value'];
                }
            }
            /**
             * @var string $value
             */
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    // Reduce eg.  customview[4] to 4
                    $key_value = preg_match('/\d+/', $key, $m) ? $m[0] : '';
                    $db_array[$key_value] = $value;
                }
            }
            /**
             * @var string|array $value
             */
            foreach ($db_array as $key => $value) {
                if ($value !== '') {
                    $paymentCustom = new PaymentCustom();
                    $from_custom = new PaymentCustomForm($paymentCustom);
                    $payment_custom = [];
                    $payment_custom['payment_id'] = $payment_id;
                    $payment_custom['custom_field_id'] = $key;
                    $payment_custom['value'] = is_array($value) ? serialize($value) : $value;
                    $model = ($pcR->repoPaymentCustomCount($payment_id, $key) > 0 ? $pcR->repoFormValuequery($payment_id, $key) : new PaymentCustom());
                    if (null !== $model && $formHydrator->populate($from_custom, $payment_custom) && $from_custom->isValid()) {
                        $this->paymentCustomService->savePaymentCustom($model, $payment_custom);
                    } // if null
                } // if value
            } // foreach db
        } // if !empty array
    }


    /**
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @param InvAmountRepository $iaR
     * @param PaymentRepository $pmtR
     * @param ACIR $aciR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        SettingRepository $settingRepository,
        InvRepository $invRepository,
        InvAmountRepository $iaR,
        PaymentRepository $pmtR,
        ACIR $aciR,
        IIR $iiR,
        IIAR $iiaR,
        ITRR $itrR,
    ): Response {
        try {
            $number_helper = new NumberHelper($settingRepository);
            $payment = $this->payment($currentRoute, $pmtR);
            if ($payment) {
                $inv_id = $payment->getInv()?->getId();
                $this->paymentService->deletePayment($payment);
                $number_helper->calculate_inv((string)$inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $invRepository, $pmtR);
                $this->flash_message('success', $this->translator->translate('invoice.payment.deleted'));
                return $this->webService->getRedirectResponse('payment/index');
            }
            return $this->webService->getRedirectResponse('payment/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', $this->translator->translate('invoice.payment.cannot.delete'));
            return $this->webService->getRedirectResponse('payment/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @param InvAmountRepository $iaR
     * @param PaymentRepository $pmtR
     * @param PaymentMethodRepository $payment_methodRepository
     * @param PaymentCustomRepository $pcR
     * @param CustomFieldRepository $cfR
     * @param CustomValueRepository $cvR
     * @param ClientRepository $cR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ACIR $aciR,
        SettingRepository $settingRepository,
        InvRepository $invRepository,
        InvAmountRepository $iaR,
        PaymentRepository $pmtR,
        PaymentMethodRepository $payment_methodRepository,
        PaymentCustomRepository $pcR,
        CustomFieldRepository $cfR,
        CustomValueRepository $cvR,
        ClientRepository $cR,
        IIR $iiR,
        IIAR $iiaR,
        ITRR $itrR,
    ): Response {
        $payment = $this->payment($currentRoute, $pmtR);
        if ($payment) {
            $form = new PaymentForm($payment);
            $paymentCustom = new PaymentCustom();
            $paymentCustomForm = new PaymentCustomForm($paymentCustom);
            $payment_id = $payment->getId();
            $inv_id = $payment->getId();
            $open = $invRepository->open();
            $number_helper = new NumberHelper($settingRepository);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'payment/edit',
                'actionArguments' => ['id' => $payment_id],
                'alert' => $this->alert(),
                'form' => $form,
                'errors' => [],
                'errorsCustom' => [],
                'openInvs' => $open,
                'openInvsCount' => $invRepository->open_count(),
                'paymentMethods' => $payment_methodRepository->findAllPreloaded(),
                'cR' => $cR,
                'iaR' => $iaR,
                'cvH' => new CustomValuesHelper($settingRepository),
                'customFields' => $cfR->repoTablequery('payment_custom'),
                // Applicable to normally building up permanent selection lists eg. dropdowns
                'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('payment_custom')),
                // There will initially be no custom_values attached to this payment until they are filled in the field on the form
                //'payment_custom_values' => $this->payment_custom_values($payment_id,$pcR),
                'paymentCustomValues' => $this->payment_custom_values($payment_id, $pcR),
                'edit' => true,
                'paymentCustomForm' => $paymentCustomForm
           ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $form = $this->save_form_fields($body, $currentRoute, $formHydrator, $pmtR);
                    if (isset($parameters['errors'])) {
                        // Recalculate the invoice
                        if (isset($body['custom'])) {
                            /** @var array $body['custom'] */
                            $custom = $body['custom'];
                            if ($pcR->repoPaymentCount($payment_id) > 0) {
                                $formCustom =  $this->save_custom_fields($custom, $formHydrator, $pcR, $payment_id);
                                if (null !== $formCustom) {
                                    // $parameters['errorsCustom'] can be used to provide customized labels if ->required(true) not used
                                    // currently not used
                                    $parameters['errorsCustom'] = $formCustom->getValidationResult()->getErrorMessagesIndexedByProperty();
                                    $parameters['formCustom'] = $formCustom;
                                    if (count($parameters['errorsCustom']) > 0) {
                                        return $this->viewRenderer->render('_form', $parameters);
                                    }
                                }
                            }
                        }
                        $number_helper->calculate_inv($inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $invRepository, $pmtR);
                        $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
                        return $this->webService->getRedirectResponse('payment/index');
                    }
                    if (null !== $form) {
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    }
                } // is_array    
            } // request
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('payment/index');
    }

    /**
     * @see Only return the form if there are errors otherwise return null
     * @param array $body
     * @return null|PaymentForm
     */
    public function save_form_fields(
        array $body,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        PaymentRepository $pmtR
    ): null|PaymentForm {
        $payment = $this->payment($currentRoute, $pmtR);
        if (null !== $payment) {
            $form = new PaymentForm($payment);
            if ($formHydrator->populateAndValidate($form, $body)) {
                $this->paymentService->savePayment($payment, $body);
            } else {
                return $form;
            }
            return null;
        }
        return null;
    }

    /**
     * @param array $custom
     * @param FormHydrator $formHydrator
     * @param PaymentCustomRepository $pcR
     * @param string $payment_id
     * @return null|PaymentCustomForm
     */
    public function save_custom_fields(array $custom, FormHydrator $formHydrator, PaymentCustomRepository $pcR, string $payment_id): null|PaymentCustomForm
    {
        /**
         * @var string|array $value
         */
        foreach ($custom as $custom_field_id => $value) {
            $paymentCustom = $pcR->repoFormValuequery($payment_id, (string)$custom_field_id);
            if ($paymentCustom) {
                $form = new PaymentCustomForm($paymentCustom);
                $payment_custom_input = [
                    'payment_id' => (int)$payment_id,
                    'custom_field_id' => (int)$custom_field_id,
                    'value' => is_array($value) ? serialize($value) : $value
                ];
                if ($formHydrator->populateAndValidate($form, $payment_custom_input)) {
                    $this->paymentCustomService->savePaymentCustom($paymentCustom, $payment_custom_input);
                } else {
                    // return the form early with the errors
                    return $form;
                }
                return null;
            }
            return null;
        }
        return null;
    }

    // This function is used in invoice/layout/guest

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $paymentRepository
     * @param SettingRepository $settingRepository
     * @param InvAmountRepository $iaR
     * @param UserClientRepository $ucR
     * @param UserInvRepository $uiR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function guest(
        Request $request,
        CurrentRoute $currentRoute,
        PaymentRepository $paymentRepository,
        SettingRepository $settingRepository,
        InvAmountRepository $iaR,
        UserClientRepository $ucR,
        UserInvRepository $uiR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int)$page > 0 ? (int)$page : 1;
        // Clicking on the gridview's Inv_id column hyperlink generates
        // the query_param called 'sort'
        // Clicking on the paginator does not generate the query_param 'sort'
        /** @psalm-suppress MixedAssignment $sort_string */
        $sort = $query_params['sort'] ?? '-inv_id';
        $sort_by = Sort::only(['id','inv_id','payment_date'])
                // Sort the merchant responses in descending order
                ->withOrderString((string)$sort);
        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser();
        // Set the page size limiter to 10 as default
        $userInvListLimit = 10;
        if ($user instanceof User && null !== $user->getId()) {
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string)$user->getId()) > 0
                     ? $uiR->repoUserInvUserIdquery((string)$user->getId())
                     : null);
            // Determine what clients have been allocated to this user (@see Settings...User Account)
            // by looking at UserClient table
            // eg. If the user is a guest-accountant, they will have been allocated certain clients
            // A user-quest-accountant will be allocated a series of clients
            // A user-guest-client will be allocated their client number by the administrator so that
            // they can view their invoices and make payment
            // Return an array of client ids associated with the current user
            if (null !== $userinv && null !== $user->getId()) {
                /** @psalm-suppress PossiblyNullArgument */
                $client_id_array = $ucR->get_assigned_to_user($user->getId());
                // Use the users last preferred Page Size Limiter value
                $userInvListLimit = $userinv->getListLimit();
            } else {
                $client_id_array = [];
            }
            if (!empty($client_id_array)) {
               /**
                * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $payments 
                */
                $payments = $this->payments_with_sort_guest($paymentRepository, $client_id_array, $sort_by);
                $paginator = (new OffsetPaginator($payments))
                 ->withPageSize($userInvListLimit ?? 10)
                 ->withCurrentPage($currentPageNeverZero)
                 ->withToken(PageToken::next((string)$page));
                $canEdit = $this->userService->hasPermission('editPayment');
                $canView = $this->userService->hasPermission('viewPayment');
                $parameters = [
                    'alert' => $this->alert(),
                    'canEdit' => $canEdit,
                    'canView' => $canView,
                    'page' => $page,
                    'paginator' => $paginator,
                    'sortOrder' => $query_params['sort'] ?? '',
                    'iaR' => $iaR,
                    'payments' => $this->payments($paymentRepository),
                    'max' => (int)$settingRepository->getSetting('default_list_limit'),
                ];
                return $this->viewRenderer->render('guest', $parameters);
            }
            throw new NoClientsAssignedToUserException($this->translator);
        } //if user
        return $this->webService->getRedirectResponse('payment/guest');
    }

    /**
     *
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @param UserClientRepository $ucR
     * @param UserInvRepository $uiR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function guest_online_log(
        Request $request,
        CurrentRoute $currentRoute,
        MerchantRepository $merchantRepository,
        UserClientRepository $ucR,
        UserInvRepository $uiR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        /** @psalm-suppress MixedAssignment $sort */
        $sort = $query_params['sort'] ?? '-inv_id';
        $sort_by = Sort::only(['inv_id','date', 'successful', 'driver'])
                // Sort the merchant responses in descending order
                ->withOrderString((string)$sort);
        // Retrieve the user from Yii-Demo's list of users in the User Table
        /** @var User $user */
        $user = $this->userService->getUser();
        $user_id = $user->getId();
        if (null !== $user_id) {
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount($user_id) > 0
                     ? $uiR->repoUserInvUserIdquery($user_id)
                     : null);
            $client_id_array = (null !== $userinv ? $ucR->get_assigned_to_user($user_id) : []);
            /**
             * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $merchants 
             */
            $merchants = $this->merchant_with_sort_guest($merchantRepository, $client_id_array, $sort_by);
            $paginator = (new OffsetPaginator($merchants))
             ->withPageSize(10)
             ->withCurrentPage($currentPageNeverZero)
             ->withToken(PageToken::next((string)$page));
            // No need for rbac here since the route accessChecker for payment/online_log
            // includes 'viewPayment' @see config/routes.php
            $parameters = [
                'alert' => $this->alert(),
                'page' => $page,
                'paginator' => $paginator,
                'sortOrder' => $query_params['sort'] ?? '',
                'merchants' => $this->merchants($merchantRepository),
                'max' => 10,
            ];
            return $this->viewRenderer->render('guest_online_log', $parameters);
        }
        return $this->webService->getRedirectResponse('payment/guest');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $paymentRepository
     * @param SettingRepository $settingRepository
     * @param InvAmountRepository $iaR
     */
    public function index(
        Request $request,
        CurrentRoute $currentRoute,
        PaymentRepository $paymentRepository,
        SettingRepository $settingRepository,
        InvAmountRepository $iaR
    ): \Yiisoft\DataResponse\DataResponse {
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        // Clicking on the gridview's Inv_id column hyperlink generates
        // the query_param called 'sort' which is seen in the url
        // Clicking on the paginator does not generate the query_param 'sort'
        /**
         * @var string|null $query_params['sort']
         */
        $sort_string = $query_params['sort'] ?? '-inv_id';
        $order =  OrderHelper::stringToArray($sort_string);
        $sort = Sort::only(['id','inv_id','payment_date', 'payment_date'])
                // Sort the merchant responses in descending order
                ->withOrder($order);
        /**
         * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $payments 
         */
        $payments = $this->payments_with_sort($paymentRepository, $sort);
        if (isset($query_params['paymentAmountFilter']) && !empty($query_params['paymentAmountFilter'])) {
            $payments = $paymentRepository->repoPaymentAmountFilter((string)$query_params['paymentAmountFilter']);
        }
        if (isset($query_params['paymentDateFilter']) && !empty($query_params['paymentDateFilter'])) {
            $payments = $paymentRepository->repoPaymentDateFilter((string)$query_params['paymentDateFilter']);
        }
        if (isset($query_params['paymentAmountFilter']) && !empty($query_params['paymentAmountFilter']) &&
            isset($query_params['paymentDateFilter']) && !empty($query_params['paymentDateFilter'])) {
            $payments = $paymentRepository->repoPaymentAmountWithDateFilter(
                (string)$query_params['paymentAmountFilter'],
                (string)$query_params['paymentDateFilter']
            );
        }
        $paginator = (new OffsetPaginator($payments))
         ->withPageSize((int)$settingRepository->getSetting('default_list_limit'))
         ->withCurrentPage($currentPageNeverZero)
         ->withSort($sort)
         ->withToken(PageToken::next((string)$page));
        $canEdit = $this->userService->hasPermission('editPayment');
        $canView = $this->userService->hasPermission('viewPayment');
        $parameters = [
            'alert' => $this->alert(),
            'canEdit' => $canEdit,
            'canView' => $canView,
            'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                                                    ? (int)$settingRepository->getSetting('default_list_limit') : 1,
            'page' => $page,
            'paginator' => $paginator,
            'sortOrder' => $query_params['sort'] ?? '',
            'iaR' => $iaR,
            'payments' => $this->payments($paymentRepository),
            'max' => (int)$settingRepository->getSetting('default_list_limit'),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param MerchantRepository $merchantRepository
     *
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function merchants(MerchantRepository $merchantRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $merchants = $merchantRepository->findAllPreloaded();
        return $merchants;
    }

    /**
     * 
     * @param MerchantRepository $merchantRepository
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function merchant_with_sort(MerchantRepository $merchantRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        $merchants = $merchantRepository->findAllPreloaded()
                                        ->withSort($sort);
        return $merchants;
    }

    /**
     * 
     * @param MerchantRepository $merchantRepository
     * @param array $client_id_array
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function merchant_with_sort_guest(MerchantRepository $merchantRepository, array $client_id_array, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        $merchant_responses = $merchantRepository->findOneUserManyClientsMerchantResponses($client_id_array)
                                                 ->withSort($sort);
        return $merchant_responses;
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $settingRepository
     */
    public function online_log(
        Request $request,
        CurrentRoute $currentRoute,
        MerchantRepository $merchantRepository,
        SettingRepository $settingRepository
    ): \Yiisoft\DataResponse\DataResponse {
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        /**
         * @var string|null $query_params['sort']
         */
        $sort_string = $query_params['sort'] ?? '-inv_id';
        $order =  OrderHelper::stringToArray($sort_string);
        $sort = Sort::only(['inv_id','date', 'successful', 'driver'])
                // Sort the merchant responses in descending order
                ->withOrder($order);
        /**
         * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $merchants 
         */
        $merchants = $this->merchant_with_sort($merchantRepository, $sort);
        if (isset($query_params['filterInvNumber']) && !empty($query_params['filterInvNumber'])) {
            $merchants = $merchantRepository->repoMerchantInvNumberquery((string)$query_params['filterInvNumber']);
        }
        if (isset($query_params['filterPaymentProvider']) && !empty($query_params['filterPaymentProvider'])) {
            $merchants = $merchantRepository->repoMerchantPaymentProviderquery((string)$query_params['filterPaymentProvider']);
        }
        if ((isset($query_params['filterInvNumber']) && !empty($query_params['filterInvNumber']))
          && (isset($query_params['filterPaymentProvider']) && !empty($query_params['filterPaymentProvider']))) {
            $merchants = $merchantRepository->repoMerchantInvNumberWithPaymentProvider((string)$query_params['filterInvNumber'], (string)$query_params['filterPaymentProvider']);
        }
        $paginator = (new OffsetPaginator($merchants))
         ->withPageSize((int)$settingRepository->getSetting('default_list_limit'))
         ->withCurrentPage($currentPageNeverZero)
         ->withToken(PageToken::next((string) $page));
        // No need for rbac here since the route accessChecker for payment/online_log
        // includes 'viewPayment' @see config/routes.php
        $parameters = [
            'alert' => $this->alert(),
            'page' => $page,
            'paginator' => $paginator,
            'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                                                    ? (int)$settingRepository->getSetting('default_list_limit') : 1,
            'merchants' => $this->merchants($merchantRepository),
        ];
        return $this->viewRenderer->render('online_log', $parameters);
    }

    /**
     * 
     * @param PaymentRepository $paymentRepository
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function payments_with_sort(PaymentRepository $paymentRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        $payments = $paymentRepository->findAllPreloaded()
                                      ->withSort($sort);
        return $payments;
    }

    /**
     * 
     * @param PaymentRepository $paymentRepository
     * @param array $client_id_array
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function payments_with_sort_guest(PaymentRepository $paymentRepository, array $client_id_array, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        $payments = $paymentRepository->findOneUserManyClientsPayments($client_id_array)
                                      ->withSort($sort);
        return $payments;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $paymentRepository
     * @return Payment|null
     */
    private function payment(CurrentRoute $currentRoute, PaymentRepository $paymentRepository): Payment|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            $payment = $paymentRepository->repoPaymentquery($id);
            return $payment;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function payments(PaymentRepository $paymentRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $payments = $paymentRepository->findAllPreloaded();
        return $payments;
    }

    /**
     * @param string $payment_id
     * @param PaymentCustomRepository $pcR
     * @return array
     */
    private function payment_custom_values(string $payment_id, PaymentCustomRepository $pcR): array
    {
        // Function edit: Get field's values for editing
        $custom_field_form_values = [];
        if ($pcR->repoPaymentCount($payment_id) > 0) {
            $payment_custom_fields = $pcR->repoFields($payment_id);

            /**
             * @var string $key
             * @var string $val
             */
            foreach ($payment_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' .$key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $viewPayment = $this->userService->hasPermission('viewPayment');
        if (!$viewPayment) {
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('payment/index');
        }
        return $viewPayment;
    }

    // payment/view => '#btn_save_payment_custom_fields' => payment_custom_field.js => /invoice/payment/save_custom";

    /**
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param PaymentCustomRepository $pcR
     */
    public function save_custom(FormHydrator $formHydrator, Request $request, PaymentCustomRepository $pcR): \Yiisoft\DataResponse\DataResponse
    {
        $js_data = $request->getQueryParams();
        $payment_id = (string)$js_data['payment_id'];
        $custom_field_body = [
            'custom' => (array)$js_data['custom'] ?: '',
        ];
        $this->custom_fields($formHydrator, $custom_field_body, $payment_id, $pcR);
        return $this->factory->createResponse(Json::encode(['success' => 1]));
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $paymentRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param SettingRepository $settingRepository
     * @param CustomFieldRepository $cfR
     * @param CustomValueRepository $cvR
     */
    public function view(
        CurrentRoute $currentRoute,
        PaymentRepository $paymentRepository,
        PaymentMethodRepository $paymentMethodRepository,
        SettingRepository $settingRepository,
        CustomFieldRepository $cfR,
        CustomValueRepository $cvR,
        PaymentCustomRepository $pcR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $payment = $this->payment($currentRoute, $paymentRepository);
        if ($payment) {
            $paymentId = $payment->getId();
            $form = new PaymentForm($payment);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'payment/edit',
                'actionArguments' => ['id' => $paymentId],
                'errors' => [],
                'form' => $form,
                'paymentMethods' => $paymentMethodRepository->findAllPreloaded(),
                'viewCustomFields' => $this->view_custom_fields(
                    $cfR,
                    $cvR,
                    $this->payment_custom_values($paymentId, $pcR),
                    $settingRepository
                ),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('payment/index');
    }

    /**
     * @param CustomFieldRepository $cfR
     * @param CustomValueRepository $cvR
     * @param array $payment_custom_values
     * @param SettingRepository $settingRepository
     * @return string
     */
    private function view_custom_fields(CustomFieldRepository $cfR, CustomValueRepository $cvR, array $payment_custom_values, SettingRepository $settingRepository): string
    {
        return $this->viewRenderer->renderPartialAsString('//invoice/payment/view_custom_fields', [
            'customFields' => $cfR->repoTablequery('payment_custom'),
            'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('payment_custom')),
            'paymentCustomValues' => $payment_custom_values,
            'cvH' => new CustomValuesHelper($settingRepository),
            'paymentCustomForm' => new PaymentCustomForm(new PaymentCustom()),
        ]);
    }
}
