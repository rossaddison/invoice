<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Infrastructure\Persistence\PaymentCustom\PaymentCustom;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Helpers\CustomValuesHelper;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
use App\Invoice\PaymentCustom\PaymentCustomRepository;
use App\Invoice\PaymentCustom\PaymentCustomForm;
use App\Invoice\PaymentCustom\PaymentCustomService;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Infrastructure\Persistence\User\User;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\CountableDataInterface as CDI;
use Yiisoft\Data\Reader\DataReaderInterface as DRI;
use Yiisoft\Data\Reader\LimitableDataInterface as LDI;
use Yiisoft\Data\Reader\OffsetableDataInterface as ODI;
use Yiisoft\Data\Reader\ReadableDataInterface as RDI;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\SortableDataInterface as SDI;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use App\Invoice\Helpers\InvRecalculator;
use App\Invoice\Payment\PaymentAddDeps;
use App\Invoice\Payment\PaymentCustomFieldProcessor;
use App\Invoice\Payment\PaymentEditDeps;

final class PaymentController extends BaseController
{
    protected string $controllerName = 'invoice/payment';

    public function __construct(
        private PaymentService $paymentService,
        private PaymentCustomService $paymentCustomService,
        private PaymentCustomFieldProcessor $paymentCustomFieldProcessor,
        private InvRecalculator $invRecalculator,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
                                        $webViewRenderer, $session, $sR, $flash);
        $this->paymentService = $paymentService;
        $this->paymentCustomService = $paymentCustomService;
        $this->paymentCustomFieldProcessor = $paymentCustomFieldProcessor;
        $this->invRecalculator = $invRecalculator;
        $this->factory = $factory;
    }

    public function add(
        Request $request,
        FormHydrator $fmHyd,
        PaymentAddDeps $deps,
    ): Response {
        $open = $deps->invR->open();
        $deps->invR->openCount() == 0 ?
                $this->flashMessage('danger', $this->translator->translate(
                        'payment.no.invoice.sent')) : '';
        $amounts = [];
        $invoice_payment_methods = [];
        /** @var Inv $open_invoice */
        foreach ($open as $open_invoice) {
            $open_invoice_id = $open_invoice->reqId();
            $inv_amount = $deps->iaR->repoInvquery($open_invoice_id);
             if (null !== $inv_amount) {
                 $amounts['invoice'
                     . $open_invoice_id] =
                         $this->sR->formatAmount($inv_amount->getBalance());
             }
             $invoice_payment_methods['invoice'
                 . $open_invoice_id] = $open_invoice->getPaymentMethod();
        }
        $payment = new Payment();
        $form = new PaymentForm();
        $pcForm = new PaymentCustomForm();
        $params = [
            'actionName' => 'payment/add',
            'actionArguments' => [],
            'alert' => $this->alert(),
            'errors' => [],
            'errorsCustom' => [],
            'form' => $form,
            'openInvsCount' => $deps->invR->openCount(),
            'openInvs' => $open,
            // jquery script at bottom of _from to load all amounts
            'amounts' => Json::encode($amounts),
            'invoicePaymentMethods' => Json::encode($invoice_payment_methods),
            'paymentMethods' => $deps->pmtMethodR->count() > 0
                        ? $deps->pmtMethodR->findAllPreloaded() : null,
            'cR' => $deps->cR,
            'iaR' => $deps->iaR,
            'cvH' => new CustomValuesHelper($this->sR, $deps->cvR),
            'customFields' => $this->fetchCustomFieldsAndValues($deps->cfR,
                    $deps->cvR, 'payment_custom')['customFields'],
            // Applicable to normally building up permanent selection lists eg.
            // dropdowns
            'customValues' =>
            $this->fetchCustomFieldsAndValues(
                                    $deps->cfR, $deps->cvR, 'payment_custom')['customValues'],
// There will initially be no custom_values attached to this payment until they
// are filled in the field on the form
//'payment_custom_values' => $this->paymentCustomValues($payment_id,$pcR),
            'paymentCustomValues' => [],
            'paymentCustomForm' => $pcForm,
        ];
        if ($request->getMethod() !== Method::POST) {
            return $this->webViewRenderer->render('_form', $params);
        }
        if (!$fmHyd->populateFromPostAndValidate($form, $request)) {
            $params['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $params['form'] = $form;
            return $this->webViewRenderer->render('_form', $params);
        }
        $body = $request->getParsedBody() ?? [];
        if (!is_array($body)) {
            return $this->webViewRenderer->render('_form', $params);
        }
        $this->paymentService->savePayment($payment, $body);
        $payment_id = $payment->reqId();
        $inv_id = $payment->reqInvId();
        $this->invRecalculator->recalculate($inv_id);
        $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
        if (isset($body['custom'])) {
            /** @var array<array-key, mixed> $body['custom'] */
            $customResult = $this->processPaymentCustomFields(
                $body['custom'], $payment_id, $deps->pcR, $pcForm, $fmHyd);
            $params['errorsCustom'] = $customResult['errorsCustom'];
            $params['paymentCustomForm'] = $customResult['paymentCustomForm'];
            if (count($customResult['errorsCustom']) > 0) {
                return $this->webViewRenderer->render('_form', $params);
            }
        }
        return $this->webService->getRedirectResponse('payment/index');
    }

    /**
     * @param array<array-key, mixed> $custom
     * @psalm-return array{errorsCustom: array<string, list<string>>, paymentCustomForm: PaymentCustomForm}
     */
    private function processPaymentCustomFields(
        array $custom,
        int $payment_id,
        PaymentCustomRepository $pcR,
        PaymentCustomForm $pcForm,
        FormHydrator $fmHyd,
    ): array {
        /** @var array<string, list<string>> $errorsCustom */
        $errorsCustom = [];
        /**
         * @var int $custom_field_id
         * @var array|string $value
         */
        foreach ($custom as $custom_field_id => $value) {
            $paymentCustom = new PaymentCustom();
            $pcForm = new PaymentCustomForm();
            $paymentCustomInput = [
                'payment_id' => $payment_id,
                'custom_field_id' => $custom_field_id,
                'value' => is_array($value) ? serialize($value) : $value,
            ];
            if ($fmHyd->populate($pcForm, $paymentCustomInput)
                && $pcForm->isValid()
                && $this->addCustomField($payment_id, $custom_field_id, $pcR)) {
                $this->paymentCustomService->savePaymentCustom($paymentCustom, $paymentCustomInput);
            }
            $errorsCustom = $pcForm->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return ['errorsCustom' => $errorsCustom, 'paymentCustomForm' => $pcForm];
    }

    // If the custom field already exists return false

    /**
     * @param int $payment_id
     * @param int $custom_field_id
     * @param PaymentCustomRepository $pcR
     * @return bool
     */
    public function addCustomField(int $payment_id, int $custom_field_id,
                                            PaymentCustomRepository $pcR): bool
    {
        return $pcR->repoPaymentCustomCount($payment_id, $custom_field_id) > 0 ?
            false : true;
    }

    /**
     * @param FormHydrator $fmHyd
     * @param (mixed|string)[] $array
     * @param int $payment_id
     * @param PaymentCustomRepository $pcR
     * @psalm-param array{custom: ''|mixed} $array
     */
    public function customFields(FormHydrator $fmHyd, array $array,
                        int $payment_id, PaymentCustomRepository $pcR): void
    {
        if (!is_array($array['custom'])) {
            return;
        }
        $db_array = $this->buildDbArray($array['custom']);
        $this->savePaymentCustomFields($db_array, $payment_id, $fmHyd, $pcR);
    }

    /**
     * @param array $arrayCustom
     * @return array
     */
    private function buildDbArray(array $arrayCustom): array
    {
        $values = [];
        /** @var array<string, mixed> $custom */
        foreach ($arrayCustom as $custom) {
            if (preg_match("/^(.*)\[\]$/i", (string) $custom['name'], $matches)) {
                $values[$matches[1]][] = (string) $custom['value'];
            } else {
                $values[(string) $custom['name']] = (string) $custom['value'];
            }
        }
        $db_array = [];
        /** @var string $value */
        foreach ($values as $key => $value) {
            preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
            if ($matches) {
                $key_value = preg_match('/\d+/', $key, $m) ? $m[0] : '';
                $db_array[$key_value] = $value;
            }
        }
        return $db_array;
    }

    private function savePaymentCustomFields(
        array $db_array,
        int $payment_id,
        FormHydrator $fmHyd,
        PaymentCustomRepository $pcR,
    ): void {
        /** @psalm-suppress MixedAssignment */
        foreach ($db_array as $key => $value) {
            if ($value === '') {
                continue;
            }
            $paymentCustom = new PaymentCustom();
            $from_custom = new PaymentCustomForm();
            $payment_custom = [
                'payment_id'      => $payment_id,
                'custom_field_id' => $key,
                'value'           => is_array($value) ? serialize($value) : $value,
            ];
            $model = $pcR->repoPaymentCustomCount($payment_id, (int) $key) > 0
                ? $pcR->repoFormValuequery($payment_id, (int) $key)
                : $paymentCustom;
            if (null !== $model && $fmHyd->populate($from_custom, $payment_custom)
                && $from_custom->isValid()) {
                $this->paymentCustomService->savePaymentCustom($model, $payment_custom);
            }
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $pmtR
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        PaymentRepository $pmtR,
    ): Response {
        try {
            $payment = PaymentQueryHelper::payment($currentRoute, $pmtR);
            if ($payment) {
                $inv_id = $payment->getInv()?->reqId();
                $this->paymentService->deletePayment($payment);
                $this->invRecalculator->recalculate((int) $inv_id);
                $this->flashMessage('success',
                                $this->translator->translate('payment.deleted'));
                return $this->webService->getRedirectResponse('payment/index');
            }
            return $this->webService->getRedirectResponse('payment/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger',
                        $this->translator->translate('payment.cannot.delete'));
            return $this->webService->getRedirectResponse('payment/index');
        }
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $fmHyd,
        PaymentEditDeps $deps,
    ): Response {
        $payment = PaymentQueryHelper::payment($currentRoute, $deps->pmtR);
        if (!$payment) {
            return $this->webService->getRedirectResponse('payment/index');
        }
        $form = PaymentForm::show($payment);
        $paymentCustom = new PaymentCustom();
        $pcForm = PaymentCustomForm::show($paymentCustom);
        $payment_id = $payment->reqId();
        $inv_id = $payment->reqId();
        $open = $deps->invR->open();
        $params = [
            'title' => $this->translator->translate('edit'),
            'actionName' => 'payment/edit',
            'actionArguments' => ['id' => $payment_id],
            'alert' => $this->alert(),
            'form' => $form,
            'errors' => [],
            'errorsCustom' => [],
            'openInvs' => $open,
            'openInvsCount' => $deps->invR->openCount(),
            'paymentMethods' => $deps->pmtMethodR->findAllPreloaded(),
            'cR' => $deps->cR,
            'iaR' => $deps->iaR,
            'cvH' => new CustomValuesHelper($this->sR, $deps->cvR),
            'customFields' => $this->fetchCustomFieldsAndValues($deps->cfR, $deps->cvR,
                    'payment_custom')['customFields'],
            'customValues' => $this->fetchCustomFieldsAndValues($deps->cfR, $deps->cvR,
                    'payment_custom')['customValues'],
            'paymentCustomValues' =>
                            PaymentQueryHelper::paymentCustomValues($payment_id, $deps->pcR),
            'edit' => true,
            'paymentCustomForm' => $pcForm,
        ];
        if ($request->getMethod() !== Method::POST) {
            return $this->webViewRenderer->render('_form', $params);
        }
        $body = $request->getParsedBody() ?? [];
        if (!is_array($body)) {
            return $this->webViewRenderer->render('_form', $params);
        }
        $form = $this->saveFormFields($body, $currentRoute, $fmHyd, $deps->pmtR);
        if (isset($params['errors'])) {
            $this->processPaymentEditCustom($body, $deps, $payment_id, $inv_id, $fmHyd);
            return $this->webService->getRedirectResponse('payment/index');
        }
        if (null !== $form) {
            $params['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $params['form'] = $form;
        }
        return $this->webViewRenderer->render('_form', $params);
    }

    private function processPaymentEditCustom(
        array $body,
        PaymentEditDeps $deps,
        int $payment_id,
        int $inv_id,
        FormHydrator $fmHyd,
    ): void {
        if (isset($body['custom'])) {
            /** @var array $custom */
            $custom = $body['custom'];
            if ($deps->pcR->repoPaymentCount($payment_id) > 0) {
                $this->processCustomFields(
                    ['custom' => $custom], $fmHyd, $this->paymentCustomFieldProcessor, $payment_id);
            }
        }
        $this->invRecalculator->recalculate($inv_id);
        $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
    }

/**
 * Related logic: see Only return the form if there are errors otherwise return
 * null
 * @param array $body
 * @return PaymentForm|null
 */
    public function saveFormFields(
        array $body,
        CurrentRoute $currentRoute,
        FormHydrator $fmHyd,
        PaymentRepository $pmtR,
    ): ?PaymentForm {
        $payment = PaymentQueryHelper::payment($currentRoute, $pmtR);
        if (null !== $payment) {
            $form = new PaymentForm();
            if ($fmHyd->populateAndValidate($form, $body)) {
                $this->paymentService->savePayment($payment, $body);
            } else {
                return $form;
            }
            return null;
        }
        return null;
    }



    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $payR
     * @param InvAmountRepository $iaR
     * @param UserClientRepository $ucR
     * @param UserInvRepository $uiR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function guest(
        Request $request,
        CurrentRoute $currentRoute,
        PaymentRepository $payR,
        InvAmountRepository $iaR,
        UserClientRepository $ucR,
        UserInvRepository $uiR,
    ): \Psr\Http\Message\ResponseInterface {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $page > 0 ? (int) $page : 1;
        // Clicking on the gridview's Inv_id column hyperlink generates
        // the query_param called 'sort'
        // Clicking on the paginator does not generate the query_param 'sort'
        /** @psalm-suppress MixedAssignment $sort_string */
        $sort = $query_params['sort'] ?? '-inv_id';
        $sort_by = Sort::only(['id','inv_id','payment_date'])
                // Sort the merchant responses in descending order
                ->withOrderString((string) $sort);
        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser();
        // Set the page size limiter to 10 as default
        $userInvListLimit = 10;
        if ($user instanceof User && $user->reqId() > 0) {
// Use this user's id to see whether a user has been setup under UserInv ie.
// yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount($user->reqId()) > 0
                     ? $uiR->repoUserInvUserIdquery($user->reqId())
                     : null);
// Determine what clients have been allocated to this user
// (Related logic: see Settings...User Account) by looking at UserClient table
// eg. If the user is a guest-accountant, they will have been allocated certain
// clients.
// A user-quest-accountant will be allocated a series of clients
// A user-guest-client will be allocated their client number by the administrator
// so that they can view their invoices and make payment
// Return an array of client ids associated with the current user
            if (null !== $userinv
                    && ($user->reqId() > 0)
                    && $userinv->getActive()) {
                /** @psalm-suppress PossiblyNullArgument */
                $client_id_array = $ucR->getAssignedToUser($user->reqId());
                // Use the users last preferred Page Size Limiter value
                $userInvListLimit = $userinv->getListLimit();
            } else {
                $client_id_array = [];
            }
            if (!empty($client_id_array)) {
/**
 * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $payments
 */
                $payments = PaymentQueryHelper::paymentsWithSortGuest($payR,
                                                    $client_id_array, $sort_by);
                $paginator = (new OffsetPaginator($payments))
                 ->withPageSize($userInvListLimit > 0 ? $userInvListLimit : 10)
                 ->withCurrentPage($currentPageNeverZero)
                 ->withToken(PageToken::next((string) $page));
                $canEdit = $this->userService->hasPermission(
                                                    Permissions::EDIT_PAYMENT);
                $canView = $this->userService->hasPermission(
                                                    Permissions::VIEW_PAYMENT);
                $params = [
                    'alert' => $this->alert(),
                    'canEdit' => $canEdit,
                    'canView' => $canView,
                    'page' => $page,
                    'paginator' => $paginator,
                    'sortOrder' => $query_params['sort'] ?? '',
                    'iaR' => $iaR,
                    'payments' => PaymentQueryHelper::payments($payR),
                    'max' => (int) $this->sR->getSetting('default_list_limit'),
                ];
                return $this->webViewRenderer->render('guest', $params);
            }
            $this->flashMessage('warning',
                $this->translator->translate('user.clients.assigned.not'));
        } //if user
        return $this->webService->getRedirectResponse('payment/guest');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchR
     * @param UserClientRepository $ucR
     * @param UserInvRepository $uiR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function guestOnlineLog(
        Request $request,
        CurrentRoute $currentRoute,
        MerchantRepository $merchR,
        UserClientRepository $ucR,
        UserInvRepository $uiR,
    ): \Psr\Http\Message\ResponseInterface {
        $query_params = $request->getQueryParams();
        $page = (int) $currentRoute->getArgument('page', '1');
        $currentPageNeverZero = $page > 0 ? $page : 1;
        /** @psalm-suppress MixedAssignment $sort */
        $sort = $query_params['sort'] ?? '-inv_id';
        $sort_by = Sort::only(['inv_id','date', 'successful', 'driver'])
                // Sort the merchant responses in descending order
                ->withOrderString((string) $sort);
        // Retrieve the user from Yii-Demo's list of users in the User Table
        /** @var User $user */
        $user = $this->userService->getUser();
        $user_id = $user->reqId();
        if ($user_id > 0) {
// Use this user's id to see whether a user has been setup under UserInv ie.
// yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount($user_id) > 0
                     ? $uiR->repoUserInvUserIdquery($user_id)
                     : null);
            $client_id_array = (null !== $userinv ?
                                    $ucR->getAssignedToUser($user_id) : []);
/**
 * @psalm-var RDI<array-key, array<array-key, mixed>|object>&LDI&ODI&CDI $merchants
 */

            $merchants = PaymentQueryHelper::merchantWithSortGuest($merchR,
                $client_id_array, $sort_by);
            if (!empty($client_id_array)) {
                $olLimit = $userinv?->getListLimit();
                $paginator = (new OffsetPaginator($merchants))
                 ->withPageSize($olLimit !== null && $olLimit > 0 ? $olLimit : 10)
                 ->withCurrentPage($currentPageNeverZero)
                 ->withToken(PageToken::next((string) $page));
    // No need for rbac here since the route accessChecker for payment/online_log
    // includes Permissions::VIEW_PAYMENT Related logic: see config/routes.php
                $params = [
                    'alert' => $this->alert(),
                    'page' => $page,
                    'paginator' => $paginator,
                    'sortOrder' => $query_params['sort'] ?? '',
                    'merchants' => PaymentQueryHelper::merchants($merchR),
                    'max' => 10,
                ];
                return $this->webViewRenderer->render('guest_online_log', $params);
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $payR
     * @param InvAmountRepository $iaR
     */
    public function index(
        Request $request,
        CurrentRoute $currentRoute,
        PaymentRepository $payR,
        InvAmountRepository $iaR,
    ): \Psr\Http\Message\ResponseInterface {
        $query_params = $request->getQueryParams();
        $page = (int) $currentRoute->getArgument('page', '1');
        $currentPageNeverZero = $page > 0 ? $page : 1;
        // Clicking on the gridview's Inv_id column hyperlink generates
        // the query_param called 'sort' which is seen in the url
        // Clicking on the paginator does not generate the query_param 'sort'
        /**
         * @var string|null $query_params['sort']
         */
        $sort_string = $query_params['sort'] ?? '-inv_id';
        $order = OrderHelper::stringToArray($sort_string);
        $sort = Sort::only(['id','inv_id','payment_date', 'payment_date'])
                // Sort the merchant responses in descending order
                ->withOrder($order);
/**
 * @psalm-var RDI<array-key, array<array-key, mixed>|object>&LDI&ODI&CDI $payments
 */
        $payments = PaymentQueryHelper::paymentsWithSort($payR, $sort);
        if (isset($query_params['paymentAmountFilter'])
                && !empty($query_params['paymentAmountFilter'])) {
            $payments =
                    $payR->repoPaymentAmountFilter(
                                (string) $query_params['paymentAmountFilter']);
        }
        if (isset($query_params['paymentDateFilter'])
                && !empty($query_params['paymentDateFilter'])) {
            $payments = $payR->repoPaymentDateFilter(
                    (string) $query_params['paymentDateFilter']);
        }
        if (isset($query_params['paymentAmountFilter'])
                && !empty($query_params['paymentAmountFilter'])
                && isset($query_params['paymentDateFilter'])
                && !empty($query_params['paymentDateFilter'])) {
            $payments = $payR->repoPaymentAmountWithDateFilter(
                (string) $query_params['paymentAmountFilter'],
                (string) $query_params['paymentDateFilter'],
            );
        }
        $paginator = (new OffsetPaginator($payments))
         ->withPageSize($this->sR->positiveListLimit())
         ->withCurrentPage($currentPageNeverZero)
         ->withSort($sort)
         ->withToken(PageToken::next((string) $page));
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_PAYMENT);
        $canView = $this->userService->hasPermission(Permissions::VIEW_PAYMENT);
        $params = [
            'alert' => $this->alert(),
            'canEdit' => $canEdit,
            'canView' => $canView,
            'defaultPageSizeOffsetPaginator' =>
                $this->sR->getSetting('default_list_limit') ?
                    (int) $this->sR->getSetting('default_list_limit') : 1,
            'page' => $page,
            'paginator' => $paginator,
            'sortOrder' => $query_params['sort'] ?? '',
            'iaR' => $iaR,
            'payments' => PaymentQueryHelper::payments($payR),
            'max' => (int) $this->sR->getSetting('default_list_limit'),
        ];
        return $this->webViewRenderer->render('index', $params);
    }


    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchR
     */
    public function onlineLog(
        Request $request,
        CurrentRoute $currentRoute,
        MerchantRepository $merchR,
    ): \Psr\Http\Message\ResponseInterface {
        $query_params = $request->getQueryParams();
        $page = (int) $currentRoute->getArgument('page', '1');
        $currentPageNeverZero = $page > 0 ? $page : 1;
        /**
         * @var string|null $query_params['sort']
         */
        $sort_string = $query_params['sort'] ?? '-inv_id';
        $order = OrderHelper::stringToArray($sort_string);
        $sort = Sort::only(['inv_id','date', 'successful', 'driver'])
                // Sort the merchant responses in descending order
                ->withOrder($order);
        /**
         * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $merchants
         */
        $merchants = PaymentQueryHelper::merchantWithSort($merchR, $sort);
        if (isset($query_params['filterInvNumber'])
                && !empty($query_params['filterInvNumber'])) {
            $merchants =
                    $merchR->repoMerchantInvNumberquery(
                            (string) $query_params['filterInvNumber']);
        }
        if (isset($query_params['filterPaymentProvider'])
                && !empty($query_params['filterPaymentProvider'])) {
            $merchants = $merchR->repoMerchantPaymentProviderquery(
                    (string) $query_params['filterPaymentProvider']);
        }
        if ((isset($query_params['filterInvNumber'])
                && !empty($query_params['filterInvNumber']))
                && (isset($query_params['filterPaymentProvider'])
                && !empty($query_params['filterPaymentProvider']))) {
            $merchants = $merchR->repoMerchantInvNumberWithPaymentProvider(
                    (string) $query_params['filterInvNumber'],
                    (string) $query_params['filterPaymentProvider']);
        }
        $paginator = (new OffsetPaginator($merchants))
         ->withPageSize($this->sR->positiveListLimit())
         ->withCurrentPage($currentPageNeverZero)
         ->withToken(PageToken::next((string) $page));
// No need for rbac here since the route accessChecker for payment/online_log
// includes Permissions::VIEW_PAYMENT Related logic: see config/routes.php
        $params = [
            'alert' => $this->alert(),
            'page' => $page,
            'paginator' => $paginator,
            'defaultPageSizeOffsetPaginator' =>
                        $this->sR->getSetting('default_list_limit') ?
                        (int) $this->sR->getSetting('default_list_limit') : 1,
            'merchants' => PaymentQueryHelper::merchants($merchR),
        ];
        return $this->webViewRenderer->render('online_log', $params);
    }

// payment/view => '#btn_save_payment_custom_fields' => payment_custom_field.js => /invoice/payment/save_custom";

    /**
     * @param FormHydrator $fmHyd
     * @param Request $request
     * @param PaymentCustomRepository $pcR
     */
    public function saveCustom(FormHydrator $fmHyd,
                                Request $request, PaymentCustomRepository $pcR):                                                       \Psr\Http\Message\ResponseInterface
    {
        $js_data = $request->getQueryParams();
        $payment_id = (string) $js_data['payment_id'];
        $custom_field_body = [
            'custom' => (array) $js_data['custom'] ?: '',
        ];
        $this->customFields($fmHyd, $custom_field_body, (int) $payment_id, $pcR);
        return $this->factory->createResponse(Json::encode(['success' => 1]));
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $payR
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param CustomFieldRepository $cfR
     * @param CustomValueRepository $cvR
     */
    public function view(
        CurrentRoute $currentRoute,
        PaymentRepository $payR,
        PaymentMethodRepository $paymentMethodRepository,
        CustomFieldRepository $cfR,
        CustomValueRepository $cvR,
        PaymentCustomRepository $pcR,
    ): \Psr\Http\Message\ResponseInterface {
        $payment = PaymentQueryHelper::payment($currentRoute, $payR);
        if ($payment) {
            $paymentId = $payment->reqId();
            $form = PaymentForm::show($payment);
            $params = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'payment/edit',
                'actionArguments' => ['id' => $paymentId],
                'errors' => [],
                'form' => $form,
                'paymentMethods' => $paymentMethodRepository->findAllPreloaded(),
                'viewCustomFields' => $this->viewCustomFields(
                    $cfR,
                    $cvR,
                    PaymentQueryHelper::paymentCustomValues($paymentId, $pcR),
                ),
            ];
            return $this->webViewRenderer->render('_view', $params);
        }
        return $this->webService->getRedirectResponse('payment/index');
    }

    /**
     * @param CustomFieldRepository $cfR
     * @param CustomValueRepository $cvR
     * @param array $payment_custom_values
     * @return string
     */
    private function viewCustomFields(CustomFieldRepository $cfR,
            CustomValueRepository $cvR, array $payment_custom_values): string
    {
        return $this->webViewRenderer->renderPartialAsString(
                                    '//invoice/payment/view_custom_fields', [
            'customFields' => $cfR->repoTablequery('payment_custom'),
            'customValues' =>
                $cvR->fixCfValueToCf(
                                        $cfR->repoTablequery('payment_custom')),
            'paymentCustomValues' => $payment_custom_values,
            'cvH' => new CustomValuesHelper($this->sR, $cvR),
            'paymentCustomForm' => new PaymentCustomForm(),
        ]);
    }
}
