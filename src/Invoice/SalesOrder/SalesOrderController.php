<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\BaseController;
// Entity
use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\DeliveryLocation;
use App\Invoice\Entity\Group;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvCustom;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\SalesOrder;
use App\Invoice\Entity\SalesOrderCustom;
use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Entity\SalesOrderTaxRate;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as InvRepo;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Inv\InvForm;
use App\Invoice\InvAmount\InvAmountForm;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvCustom\InvCustomForm;
use App\Invoice\InvCustom\InvCustomService;
use App\Invoice\InvTaxRate\InvTaxRateForm;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountService as SoAS;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService as SoCS;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItem\SalesOrderItemService as SoIS;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService as SoTRS;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserClient\Exception\NoClientsAssignedToUserException;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\User\UserRepository as UR;
use App\Invoice\Inv\InvService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\User\UserService;
use App\Service\WebControllerService;
// Helpers
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\PdfHelper;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class SalesOrderController extends BaseController
{
    protected string $controllerName = 'invoice';

    public function __construct(
        private DataResponseFactoryInterface $factory,
        private InvService $invService,
        private InvCustomService $inv_custom_service,
        private InvAmountService $invAmountService,
        private InvItemService $invItemService,
        private InvTaxRateService $invTaxRateService,
        private SalesOrderService $salesorderService,
        Session $session,
        SettingRepository $sR,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
    }

    /**
     * @param Request $request
     * @param SOAR $soaR
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function guest(
        Request $request,
        SoAR $soaR,
        CurrentRoute $currentRoute,
        SoR $soR,
        UCR $ucR,
        UIR $uiR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $pageNum = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int)$pageNum > 0 ? (int)$pageNum : 1;
        //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status', '0');
        /** @psalm-suppress MixedAssignment $sort_string */
        $sort_string = $query_params['sort'] ?? '-id';
        $sort = Sort::only(['status_id','number','date_created', 'id','client_id'])->withOrderString((string)$sort_string);

        // Get the current user and determine from (@see Settings...User Account) whether they have been given
        // either guest or admin rights. These rights are unrelated to rbac and serve as a second
        // 'line of defense' to support role based admin control.

        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser();
        if ($user) {
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string)$user->getId()) > 0
                     ? $uiR->repoUserInvUserIdquery((string)$user->getId())
                     : null);
            if ($userinv) {
                // Determine what clients have been allocated to this user (@see Settings...User Account)
                // by looking at UserClient table

                // eg. If the user is a guest-accountant, they will have been allocated certain clients
                // A user-quest-accountant will be allocated a series of clients
                // A user-guest-client will be allocated their client number by the administrator so that
                // they can view their salesorders when they log in
                $user_clients = $ucR->get_assigned_to_user((string)$user->getId());
                if (!empty($user_clients)) {
                    $salesOrders = $this->salesorders_status_with_sort_guest($soR, $status, $user_clients, $sort);
                    $paginator = (new OffsetPaginator($salesOrders))
                    ->withPageSize($this->sR->positiveListLimit())
                    ->withCurrentPage($currentPageNeverZero);
                    /**
                     * @var array $so_statuses
                     */
                    $so_statuses = $soR->getStatuses($this->translator);
                    $parameters = [
                        'alert' => $this->alert(),
                        'soaR' => $soaR,
                        'soR' => $soR,
                        'status' => $status,
                        'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                         ? (int)$this->sR->getSetting('default_list_limit') : 1,
                        'so_statuses' => $so_statuses,
                        'paginator' => $paginator,
                    ];
                    return $this->viewRenderer->render('guest', $parameters);
                }
                throw new NoClientsAssignedToUserException($this->translator);
            } // userinv
            return $this->webService->getNotFoundResponse();
        } //user
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CR $clientRepo
     * @param Request $request
     * @param SoAR $soaR
     * @param SOR $soR
     * @param SettingRepository $sR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(CurrentRoute $currentRoute, CR $clientRepo, Request $request, SoAR $soaR, SoR $soR, SettingRepository $sR): \Yiisoft\DataResponse\DataResponse
    {
        // If the language dropdown changes
        $this->session->set('_language', $currentRoute->getArgument('_language'));
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status', '0');
        /** @psalm-suppress MixedAssignment $sort_string */
        $sort_string = $query_params['sort'] ?? '-id';
        $sort = Sort::only(['id','status_id','number','date_created','client_id'])
                    // (@see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest quotes first => -id
                    /** @psalm-suppress MixedArgument $sort_string */
                    ->withOrderString((string)$sort_string);
        $salesorders = $this->salesorders_status_with_sort($soR, $status, $sort);
        $paginator = (new OffsetPaginator($salesorders))
        ->withPageSize($sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string)$page));
        $so_statuses = $soR->getStatuses($this->translator);
        $parameters = [
            'alert' => $this->alert(),
            'soaR' => $soaR,
            'soR' => $soR,
            'status' => $status,
            'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                         ? (int)$this->sR->getSetting('default_list_limit') : 1,
            'so_statuses' => $so_statuses,
            'paginator' => $paginator,
            'client_count' => $clientRepo->count(),
        ];
        return $this->viewRenderer->render('salesorder/index', $parameters);
    }

    // Sales Orders are created from Quotes see quote/approve

    /**
     * @see SalesOrderRepository getStatuses function
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @return Response
     */
    public function agree_to_terms(CurrentRoute $currentRoute, SoR $soR): Response
    {
        $url_key = $currentRoute->getArgument('url_key');
        if (null !== $url_key) {
            if ($soR->repoUrl_key_guest_count($url_key) > 0) {
                $so = $soR->repoUrl_key_guest_loaded($url_key);
                if ($so) {
                    $so_id = $so->getId();
                    $so->setStatus_id(3);
                    $soR->save($so);
                    /**
                     * @var array $so_statuses
                     */
                    $so_statuses = $soR->getStatuses($this->translator);
                    /*  @var string $status_id */
                    $status_id = $so->getStatus_id();
                    /**
                     *  @var array $so_statuses[$status_id]
                     *  @var string $so_label
                     */
                    $so_label = $so_statuses[$status_id]['label'];
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                        '//invoice/setting/salesorder_successful',
                        [
                            'heading' => $so_label,
                            'message' => $this->translator->translate('i.record_successfully_updated'),
                            'url' => 'salesorder/view','id' => $so_id,
                        ]
                    ));
                }
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @see SalesOrderRepository getStatuses function
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @return Response
     */
    public function reject(CurrentRoute $currentRoute, SoR $soR): Response
    {
        $url_key = $currentRoute->getArgument('url_key');
        if (null !== $url_key) {
            if ($soR->repoUrl_key_guest_count($url_key) > 0) {
                $so = $soR->repoUrl_key_guest_loaded($url_key);
                if ($so) {
                    $so_id = $so->getId();
                    // see SalesOrderRepository getStatuses function
                    $so->setStatus_id(9);
                    $soR->save($so);
                    return $this->factory->createResponse(
                        $this->viewRenderer->renderPartialAsString(
                            '//invoice/setting/salesorder_successful',
                            [
                                'heading' => $soR->getSpecificStatusArrayLabel((string)9),
                                'message' => $this->translator->translate('i.record_successfully_updated'),
                                'url' => 'salesorder/view','id' => $so_id,
                            ]
                        )
                    );
                }
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param SalesOrderRepository $soRepo
     * @param int $status
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\DataReaderInterface&\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, SalesOrder>
     */
    private function salesorders_status_with_sort(SoR $soRepo, int $status, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $soRepo->findAllWithStatus($status)
                      ->withSort($sort);
    }

    /**
     * @param SOR $soR
     * @param int $status
     * @param array $user_clients
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\DataReaderInterface&\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, SalesOrder>
     */
    private function salesorders_status_with_sort_guest(SoR $soR, int $status, array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $soR->repoGuestStatuses($status, $user_clients)
                     ->withSort($sort);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param SalesOrderRepository $salesorderRepository
     * @param CR $clientRepo
     * @param DR $delRepo
     * @param GR $gR
     * @param InvRepo $invRepo
     * @param SoR $soR
     * @param SoCR $socR
     * @param SettingRepository $settingRepository
     * @param UCR $ucR
     * @param CFR $cfR
     * @param CVR $cvR
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        SoR $salesorderRepository,
        CR $clientRepo,
        DR $delRepo,
        GR $gR,
        InvRepo $invRepo,
        SoR $soR,
        SoCR $socR,
        SettingRepository $settingRepository,
        UCR $ucR,
        CFR $cfR,
        CVR $cvR,
    ): Response {
        $so = $this->salesorder($currentRoute, $salesorderRepository);
        if ($so) {
            $form = new SalesOrderForm($so);
            $dels = $delRepo->repoClientquery($so->getClient_id());
            $so_id = $so->getId();
            $inv_id = $so->getInv_id();
            /**
             * @var Inv $inv
             */
            if (null !== $inv_id) {
                $inv = $invRepo->repoInvUnloadedquery($inv_id);
            }
            $inv_number = null !== $inv_id && null !== $inv ? (string)$inv->getNumber() : '';
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'salesorder/edit',
                'actionArguments' => ['id' => $so->getId()],
                'actionArgumentsDelAdd' => [
                    'client_id' => $so->getClient_id(),
                    'origin' => 'salesorder',
                    'origin_id' => $so->getClient_id(),
                    'action' => 'edit',
                ],
                // Only make clients that have a user account available in the drop down list
                'optionsData' => $this->optionsData(
                    (int)$so->getClient_id(),
                    $clientRepo,
                    $delRepo,
                    $gR,
                    $soR,
                    $ucR
                ),
                'errors' => [],
                'form' => $form,
                'invNumber' => $inv_number,
                // if the delivery location is zero present the link to delivery locations add
                'del_count' => $delRepo->repoClientCount($so->getClient_id()),
                'dels' => $dels,
                'terms_and_conditions_file' => $this->viewRenderer->renderPartialAsString('//invoice/salesorder/terms_and_conditions_file'),
                'terms_and_conditions' => $settingRepository->getTermsAndConditions(),
                // if there are no delivery locations add a flash message
                'no_delivery_locations' => $delRepo->repoClientCount($so->getClient_id()) > 0 ? '' : $this->flashMessage('warning', $this->translator->translate('invoice.quote.delivery.location.none')),
                'alert' => $this->alert(),
                'so' => $so,
                'cfR' => $cfR,
                'cvR' => $cvR,
                'so_custom_values' => null !== $so_id ? $this->salesorder_custom_values($so_id, $socR) : null,
                'so_statuses' => $soR->getStatuses($this->translator),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->salesorderService->saveSo($so, $body);
                        $this->flashMessage('success', $this->translator->translate('i.record_successfully_updated'));
                        return $this->webService->getRedirectResponse('salesorder/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form_edit', $parameters);
        }
        return $this->webService->getRedirectResponse('salesorder/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param SalesOrderRepository $salesorderRepository
     * @param SoCR $socR
     * @param SoCS $socS
     * @param SoIR $soiR
     * @param SoIS $soiS
     * @param SoTRR $sotrR
     * @param SoTRS $sotrS
     * @param SoAR $soaR
     * @param SoAS $soaS
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        SoR $salesorderRepository,
        SoCR $socR,
        SoCS $socS,
        SoIR $soiR,
        SoIS $soiS,
        SoTRR $sotrR,
        SoTRS $sotrS,
        SoAR $soaR,
        SoAS $soaS
    ): Response {
        try {
            $so = $this->salesorder($currentRoute, $salesorderRepository);
            if ($so) {
                $this->salesorderService->deleteSo($so, $socR, $socS, $soiR, $soiS, $sotrR, $sotrS, $soaR, $soaS);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('salesorder/index');
            }
            return $this->webService->getRedirectResponse('salesorder/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('salesorder/index');
        }
    }

    /**
     * @param string $so_id
     * @param SoCR $salesorder_customR
     * @return array
     */
    public function salesorder_custom_values(string $so_id, SoCR $salesorder_customR): array
    {
        // Get all the custom fields that have been registered with this salesorder on creation, retrieve existing values via repo, and populate
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($salesorder_customR->repoSalesOrderCount($so_id) > 0) {
            $salesorder_custom_fields = $salesorder_customR->repoFields($so_id);
            /**
             * @var string $key
             * @var string $val
             */
            foreach ($salesorder_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }

    public function pdf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, SoAR $soaR, SoCR $socR, SoIR $soiR, SoIAR $soiaR, SoR $soR, SoTRR $sotrR, SettingRepository $sR, UIR $uiR): \Yiisoft\DataResponse\DataResponse|Response
    {
        // include is a value of 0 or 1 passed from quote.js function quote_to_pdf_with(out)_custom_fields indicating whether the user
        // wants custom fields included on the quote or not.
        $include = $currentRoute->getArgument('include');
        $so_id = (string)$this->session->get('so_id');
        $salesorder_amount = (($soaR->repoSalesOrderAmountCount($so_id) > 0) ? $soaR->repoSalesOrderquery($so_id) : null);
        if ($salesorder_amount) {
            $custom = (($include === (string)1) ? true : false);
            $salesorder_custom_values = $this->salesorder_custom_values((string)$this->session->get('so_id'), $socR);
            // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
            $pdfhelper = new PdfHelper($sR, $this->session);
            // The salesorder will be streamed ie. shown in the browser, and not archived
            $stream = true;
            $so = $soR->repoSalesOrderUnloadedquery($so_id);
            if ($so) {
                $pdfhelper->generate_salesorder_pdf($so_id, $so->getUser_id(), $stream, $custom, $salesorder_amount, $salesorder_custom_values, $cR, $cvR, $cfR, $soiR, $soiaR, $soR, $sotrR, $uiR, $this->viewRenderer, $this->translator);
                $parameters = ($include == '1' ?
                [
                    'success' => 1,
                ] :
                [
                    'success' => 0,
                ]);
                return $this->factory->createResponse(Json::encode($parameters));
            } // $inv
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        } // quote_amount
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $settingRepository
     * @param PR $pR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param GR $gR
     * @param SoAR $soaR
     * @param SoIAR $soiaR
     * @param SoIR $soiR
     * @param SoR $soR
     * @param SoTRR $sotrR
     * @param TRR $trR
     * @param UNR $uR
     * @param SoCR $socR
     * @param InvRepo $invRepo
     * @return Response
     */
    public function view(
        CurrentRoute $currentRoute,
        SettingRepository $settingRepository,
        PR $pR,
        CFR $cfR,
        CVR $cvR,
        GR $gR,
        SoAR $soaR,
        SoIAR $soiaR,
        SoIR $soiR,
        SoR $soR,
        SoTRR $sotrR,
        TRR $trR,
        UNR $uR,
        SoCR $socR,
        InvRepo $invRepo
    ): Response {
        $so = $this->salesorder($currentRoute, $soR);
        if ($so) {
            $this->session->set('so_id', $so->getId());
            $salesorder_custom_values = $this->salesorder_custom_values((string)$this->session->get('so_id'), $socR);
            $so_tax_rates = (($sotrR->repoCount((string)$this->session->get('so_id')) > 0) ? $sotrR->repoSalesOrderquery((string)$this->session->get('so_id')) : null);
            $inv_id = $so->getInv_id();
            if (null !== $inv_id) {
                $inv = $invRepo->repoInvUnloadedquery($inv_id);
                $invNumber = ($inv ? $inv->getNumber() : '');
            } else {
                $invNumber = '';
            }
            if ($so_tax_rates) {
                $so_amount = (($soaR->repoSalesOrderAmountCount((string)$this->session->get('so_id')) > 0) ? $soaR->repoSalesOrderquery((string)$this->session->get('so_id')) : null);
                if ($so_amount) {
                    $form = new SalesOrderForm($so);
                    $parameters = [
                        'alert' => $this->alert(),
                        'title' => $this->translator->translate('i.view'),
                        'invEdit' => $this->userService->hasPermission('editInv') ? true : false,
                        'errors' => [],
                        'form' => $form,
                        'so' => $so,
                        'soItems' => $soiR->repoSalesOrderquery((string)$this->session->get('so_id')),
                        'soR' => $soR,
                        'invNumber' => $invNumber,
                        // Get all the fields that have been setup for this SPECIFIC salesorder in salesorder_custom.
                        'fields' => $socR->repoFields((string)$this->session->get('quote_id')),
                        // Get the standard extra custom fields built for EVERY quote.
                        'customFields' => $cfR->repoTablequery('salesorder_custom'),
                        'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('salesorder_custom')),
                        'cvH' => new CVH($settingRepository),
                        'terms_and_conditions' => $settingRepository->getTermsAndConditions(),
                        'soStatuses' => $soR->getStatuses($this->translator),
                        'salesOrderCustomValues' => $salesorder_custom_values,
                        'partial_item_table' => $this->viewRenderer->renderPartialAsString('//invoice/salesorder/partial_item_table', [
                            'invEdit' => $this->userService->hasPermission('editInv') ? true : false,
                            'invView' => $this->userService->hasPermission('viewInv') ? true : false,
                            'numberhelper' => new NumberHelper($settingRepository),
                            'soItems' => $soiR->repoSalesOrderquery((string)$this->session->get('so_id')),
                            'soiaR' => $soiaR,
                            'soTaxRates' => $so_tax_rates,
                            'so_amount' => $so_amount,
                            'so' => $soR->repoSalesOrderLoadedquery((string)$this->session->get('so_id')),
                            'trR' => $trR,
                            'uR' => $uR,
                        ]),
                        'modal_salesorder_to_pdf' => $this->viewRenderer->renderPartialAsString('//invoice/salesorder/modal_salesorder_to_pdf', [
                            'so' => $so,
                        ]),
                        'modal_so_to_invoice' => $this->viewRenderer->renderPartialAsString('//invoice/salesorder/modal_so_to_invoice', [
                            'so' => $so,
                            'gR' => $gR,
                        ]),
                        'view_custom_fields' => $this->viewRenderer->renderPartialAsString('//invoice/salesorder/view_custom_fields', [
                            'customFields' => $cfR->repoTablequery('salesorder_custom'),
                            'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('salesorder_custom')),
                            'form' => $form,
                            'salesOrderCustomValues' => $salesorder_custom_values,
                            'cvH' => new CVH($settingRepository),
                        ]),
                    ];
                    return $this->viewRenderer->render('view', $parameters);
                } // $so_amount
                return $this->webService->getNotFoundResponse();
            } // $so_tax_rates
            return $this->webService->getRedirectResponse('salesorder/index');
        } // $so->getId()
        return $this->webService->getNotFoundResponse();
    }

    //For rbac refer to AccessChecker

    /**
    * @param CurrentRoute $currentRoute
    * @param SalesOrderRepository $salesorderRepository
    * @return SalesOrder|null
    */
    private function salesorder(CurrentRoute $currentRoute, SoR $salesorderRepository): SalesOrder|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $salesorderRepository->repoSalesOrderLoadedquery($id);
        }
        return null;
    }

    /**
     * This function will be done by the Admin as soon as the sales order has 'invoice generate' status
     * The Sales Order will have the status 'invoice generated' against it
     * The Invoice will have the status 'sent' against it
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CFR $cfR
     * @param GR $gR
     * @param IIAR $iiaR
     * @param IIAS $iiaS
     * @param PR $pR
     * @param SOAR $soaR
     * @param SOCR $socR
     * @param SOIR $soiR
     * @param SOR $soR
     * @param SOTRR $sotrR
     * @param TRR $trR
     * @param UNR $unR
     * @param SettingRepository $sR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function so_to_invoice_confirm(
        Request $request,
        FormHydrator $formHydrator,
        CFR $cfR,
        GR $gR,
        IIAR $iiaR,
        IIAS $iiaS,
        PR $pR,
        SoAR $soaR,
        SoCR $socR,
        SoIR $soiR,
        SoR $soR,
        SoTRR $sotrR,
        TRR $trR,
        UNR $unR,
        SettingRepository $sR,
        UR $uR,
        UCR $ucR,
        UIR $uiR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $body = $request->getQueryParams();
        $so_id = (string)$body['so_id'];
        $so = $soR->repoSalesOrderUnloadedquery($so_id);
        if ($so) {
            $inv_body = [
                'client_id' => $body['client_id'],
                'group_id' => $body['group_id'],
                'quote_id' => $so->getQuote_id(),
                'so_id' => $so->getId(),
                'status_id' => 2,
                'password' => $body['password'] ?? '',
                'number' => $gR->generate_number((int)$body['group_id']),
                'discount_amount' => (float) $so->getDiscount_amount(),
                'discount_percent' => (float) $so->getDiscount_percent(),
                'url_key' => $so->getUrl_key(),
                'payment_method' => 0,
                'terms' => '',
                'creditinvoice_parent_id' => '',
            ];
            $inv = new Inv();
            $form = new InvForm($inv);
            if ($formHydrator->populateAndValidate($form, $inv_body) &&
                  // Salesorder has not been copied before:  inv_id = 0
                  ($so->getInv_id() === (string)0)
            ) {
                /**
                 * @var string $inv_body['client_id']
                 */
                $client_id = $inv_body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                $user_client_count = $ucR->repoUserquerycount($client_id);
                if (null !== $user_client && $user_client_count == 1) {
                    // Only one user account per client
                    $user_id = $user_client->getUser_id();
                    $user = $uR->findById($user_id);
                    if (null !== $user) {
                        $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                        if (null !== $user_inv && $user_inv->getActive()) {
                            $this->invService->saveInv($user, $inv, $inv_body, $sR, $gR);
                            $inv_id = $inv->getId();
                            if (null !== $inv_id) {
                                // Transfer each so_item to inv_item and the corresponding so_item_amount to inv_item_amount for each item
                                $this->so_to_invoice_so_items($so_id, $inv_id, $iiaR, $iiaS, $pR, $soiR, $trR, $formHydrator, $sR, $unR);
                                $this->so_to_invoice_so_tax_rates($so_id, $inv_id, $sotrR, $formHydrator);
                                $this->so_to_invoice_so_custom($so_id, $inv_id, $socR, $cfR, $formHydrator);
                                $this->so_to_invoice_so_amount($so_id, $inv_id, $soaR, $formHydrator);
                                // Update the sos inv_id.
                                $so->setInv_id($inv_id);
                                // Set salesorder's status to invoice generated
                                $so->setStatus_id(8);
                                $this->flashMessage('info', $this->translator->translate('invoice.salesorder.invoice.generated'));
                                $soR->save($so);
                                $parameters = [
                                    'success' => 1,
                                    'flash_message' => $this->translator->translate('invoice.salesorder.copied.to.invoice'),
                                ];
                                return $this->factory->createResponse(Json::encode($parameters));
                            } // null!==$inv_id
                        } // null!==$user_inv && $user_inv->getActive()
                    } // null!==$user
                }
            } else {
                $parameters = [
                    'success' => 0,
                    'flash_message' => $this->translator->translate('invoice.salesorder.copied.to.invoice.not'),
                ];
                //return response to salesorder.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));
            }
        } // so
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param string $so_id
     * @param string $inv_id
     * @param IIAR $iiaR
     * @param IIAS $iiaS
     * @param PR $pR
     * @param SOIR $soiR
     * @param TRR $trR
     * @param FormHydrator $formHydrator
     * @param SettingRepository $sR
     * @param UNR $unR
     */
    private function so_to_invoice_so_items(string $so_id, string $inv_id, IIAR $iiaR, IIAS $iiaS, PR $pR, SoIR $soiR, TRR $trR, FormHydrator $formHydrator, SettingRepository $sR, UNR $unR): void
    {
        // Get all items that belong to the salesorder
        $items = $soiR->repoSalesOrderItemIdquery($so_id);
        /** @var SalesOrderItem $so_item */
        foreach ($items as $so_item) {
            $inv_item = [
                'inv_id' => $inv_id,
                'so_item_id' => $so_item->getId(),
                'tax_rate_id' => $so_item->getTax_rate_id(),
                'product_id' => $so_item->getProduct_id(),
                'task_id' => '',
                'name' => $so_item->getName(),
                'description' => $so_item->getDescription(),
                'quantity' => $so_item->getQuantity(),
                'price' => $so_item->getPrice(),
                'discount_amount' => $so_item->getDiscount_amount(),
                'charge_amount' => $so_item->getCharge_amount(),
                'order' => $so_item->getOrder(),
                'is_recurring' => 0,
                'product_unit' => $so_item->getProduct_unit(),
                'product_unit_id' => $so_item->getProduct_unit_id(),
                // Recurring date
                'date' => '',
            ];
            // Create an equivalent invoice item for the so item
            $invItem = new InvItem();
            $form = new InvItemForm($invItem, (int)$inv_id);
            if ($formHydrator->populateAndValidate($form, $inv_item)) {
                $this->invItemService->addInvItem_product($invItem, $inv_item, $inv_id, $pR, $trR, $iiaS, $iiaR, $sR, $unR);
            }
        } // items
    }

    /**
     * @param string $so_id
     * @param string|null $inv_id
     * @param SOTRR $sotrR
     * @param FormHydrator $formHydrator
     */
    private function so_to_invoice_so_tax_rates(string $so_id, string|null $inv_id, SoTRR $sotrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the salesorder
        $so_tax_rates = $sotrR->repoSalesOrderquery($so_id);
        /** @var SalesOrderTaxRate $so_tax_rate */
        foreach ($so_tax_rates as $so_tax_rate) {
            $inv_tax_rate = [
                'inv_id' => (string)$inv_id,
                'tax_rate_id' => $so_tax_rate->getTax_rate_id(),
                'include_item_tax' => $so_tax_rate->getInclude_item_tax(),
                'inv_tax_rate_amount' => $so_tax_rate->getSo_tax_rate_amount(),
            ];
            $entity = new InvTaxRate();
            $form = new InvTaxRateForm($entity);
            if ($formHydrator->populateAndValidate($form, $inv_tax_rate)) {
                $this->invTaxRateService->saveInvTaxRate($entity, $inv_tax_rate);
            }
        } // foreach
    }

    /**
     * @param string $so_id
     * @param string|null $inv_id
     * @param SOCR $socR
     * @param CFR $cfR
     * @param FormHydrator $formHydrator
     */
    private function so_to_invoice_so_custom(
        string $so_id,
        string|null $inv_id,
        SoCR $socR,
        CFR $cfR,
        FormHydrator $formHydrator
    ): void {
        $so_customs = $socR->repoFields($so_id);
        // For each salesorder custom field, build a new custom field for 'inv_custom' using the custom_field_id to find details
        /** @var SalesOrderCustom $so_custom */
        foreach ($so_customs as $so_custom) {
            // For each so custom field, build a new custom field for 'inv_custom'
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery($so_custom->getCustom_field_id());
            if ($cfR->repoTableAndLabelCountquery('inv_custom', (string)$existing_custom_field->getLabel()) !== 0) {
                // Build an identitcal custom field for the invoice
                $custom_field = new CustomField();
                $custom_field->setTable('inv_custom');
                $custom_field->setLabel((string)$existing_custom_field->getLabel());
                $custom_field->setType($existing_custom_field->getType());
                $custom_field->setLocation((int)$existing_custom_field->getLocation());
                $custom_field->setOrder((int)$existing_custom_field->getOrder());
                $cfR->save($custom_field);
                // Build the inv_custom field record
                $inv_custom = [
                    'inv_id' => $inv_id,
                    'custom_field_id' => $custom_field->getId(),
                    'value' => $so_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm($entity);
                if ($formHydrator->populateAndValidate($form, $inv_custom)) {
                    $this->inv_custom_service->saveInvCustom($entity, $inv_custom);
                }
            } // existing_custom_field
        } // foreach
    }

    /**
     * @param string $so_id
     * @param string|null $inv_id
     * @param SOAR $soaR
     * @param FormHydrator $formHydrator
     */
    private function so_to_invoice_so_amount(string $so_id, string|null $inv_id, SoAR $soaR, FormHydrator $formHydrator): void
    {
        $so_amount = $soaR->repoSalesOrderquery($so_id);
        $inv_amount = [];
        if ($so_amount) {
            $inv_amount = [
                'inv_id' => $inv_id,
                'sign' => 1,
                'item_subtotal' => $so_amount->getItem_subtotal(),
                'item_tax_total' => $so_amount->getItem_tax_total(),
                'tax_total' => $so_amount->getTax_total(),
                'total' => $so_amount->getTotal(),
                'paid' => 0.00,
                'balance' => $so_amount->getTotal(),
            ];
        }
        $entity = new InvAmount();
        $form = new InvAmountForm($entity);
        if ($formHydrator->populateAndValidate($form, $inv_amount)) {
            $this->invAmountService->saveInvAmount($entity, $inv_amount);
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CurrentUser $currentUser
     * @param CFR $cfR
     * @param SOAR $soaR
     * @param SOIR $soiR
     * @param SOIAR $soiaR
     * @param SOR $soR
     * @param SOTRR $sotrR
     * @param UIR $uiR
     * @param UCR $ucR
     * @return Response
     */
    public function url_key(CurrentRoute $currentRoute, CurrentUser $currentUser, CFR $cfR, SoAR $soaR, SoIR $soiR, SoIAR $soiaR, SoR $soR, SoTRR $sotrR, UIR $uiR, UCR $ucR): Response
    {
        // Get the url key from the browser
        $url_key = $currentRoute->getArgument('key');

        // If there is no quote with such a url_key, issue a not found response
        if ($url_key === null) {
            return $this->webService->getNotFoundResponse();
        }

        // If there is a salesorder with the url key ... continue or else issue not found response
        if ($soR->repoUrl_key_guest_count($url_key) < 1) {
            return $this->webService->getNotFoundResponse();
        }
        $salesorder = $soR->repoUrl_key_guest_loaded($url_key);
        $salesorder_tax_rates = null;
        if ($salesorder) {
            $salesorder_id = $salesorder->getId();
            if (null !== $salesorder_id) {
                if ($sotrR->repoCount($salesorder_id) > 0) {
                    $salesorder_tax_rates = $sotrR->repoSalesOrderquery($salesorder_id);
                }
            }
            if (in_array($salesorder->getStatus_id(), [2,3,4,5,6,7,8,9,10])) {
                // If the user exists
                /**
                 * @psalm-suppress PossiblyNullArgument $this->userService->getUser()?->getId()
                 */
                if ($uiR->repoUserInvUserIdcount($this->userService->getUser()?->getId()) === 1) {
                    // After signup the user was included in the userinv using Settings...User Account...+
                    $user_inv = $uiR->repoUserInvUserIdquery($this->userService->getUser()?->getId());
                    // The client has been assigned to the user id using Setting...User Account...Assigned Clients
                    $user_client = $ucR->repoUserClientqueryCount($this->userService->getUser()?->getId(), $salesorder->getClient_id()) === 1 ? true : false;
                    if ($user_inv && $user_client) {
                        // If the userinv is a Guest => type = 1 ie. NOT an administrator =>type = 0
                        // So if the user has a type of 1 they are a guest.
                        if ($user_inv->getType() == 1) {
                            $soR->save($salesorder);
                            $custom_fields = [
                                'invoice' => $cfR->repoTablequery('inv_custom'),
                                'client' => $cfR->repoTablequery('client_custom'),
                                'sales_order' => $cfR->repoTablequery('sales_order'),
                            ];
                            if (null !== $salesorder_id) {
                                $salesorder_amount = (($soaR->repoSalesOrderAmountCount($salesorder_id) > 0) ? $soaR->repoSalesOrderquery($salesorder_id) : null);
                                if ($salesorder_amount) {
                                    $parameters = [
                                        'renderTemplate' => $this->viewRenderer->renderPartialAsString('//invoice/template/salesorder/public/' . ($this->sR->getSetting('public_salesorder_template') ?: 'SalesOrder_Web'), [
                                            'isGuest' => $currentUser->isGuest(),
                                            'terms_and_conditions_file' => $this->viewRenderer->renderPartialAsString('//invoice/salesorder/terms_and_conditions_file'),
                                            'alert' => $this->alert(),
                                            'salesorder' => $salesorder,
                                            'soiaR' => $soiaR,
                                            'salesorder_amount' => $salesorder_amount,
                                            'items' => $soiR->repoSalesOrderquery($salesorder_id),
                                            // Get all the salesorder tax rates that have been setup for this salesorder
                                            'salesorder_tax_rates' => $salesorder_tax_rates,
                                            'salesorder_url_key' => $url_key,
                                            'custom_fields' => $custom_fields,
                                            'client' => $salesorder->getClient(),
                                            // Get the details of the user of this quote
                                            'userInv' => $uiR->repoUserInvUserIdcount($salesorder->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($salesorder->getUser_id()) : null,
                                        ]),
                                    ];
                                    return $this->viewRenderer->render('salesorder/url_key', $parameters);
                                } // if salesorder_amount
                            } // if there is a salesorder id
                        } // user_inv->getType
                    } // user_inv
                } // $uiR
            } // if in_array
        } // if salesorder
        return $this->webService->getNotFoundResponse();
    }

    private function OptionsData(
        int $client_id,
        CR $clientRepo,
        DR $delRepo,
        GR $groupRepo,
        SoR $salesOrderRepo,
        UCR $ucR
    ): array {
        $dLocs = $delRepo->repoClientquery((string)$client_id);
        $optionsDataDeliveryLocations = [];
        /**
         * @var DeliveryLocation $dLoc
         */
        foreach ($dLocs as $dLoc) {
            $dLocId = $dLoc->getId();
            if (null !== $dLocId) {
                $optionsDataDeliveryLocations[$dLocId] = ($dLoc->getAddress_1() ?? '') . ', ' . ($dLoc->getAddress_2() ?? '') . ', ' . ($dLoc->getCity() ?? '') . ', ' . ($dLoc->getZip() ?? '');
            }
        }
        $optionsDataGroup = [];
        /**
         * @var Group $group
         */
        foreach ($groupRepo->findAllPreloaded() as $group) {
            $optionsDataGroup[$group->getId()] = $group->getName();
        }

        $optionsDataSalesOrderStatus = [];
        /**
         * @var string $key
         * @var array $status
         */
        foreach ($salesOrderRepo->getStatuses($this->translator) as $key => $status) {
            $optionsDataSalesOrderStatus[$key] = (string)$status['label'];
        }
        return $optionsData = [
            'client' => $clientRepo->optionsData($ucR),
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'salesOrderStatus' => $optionsDataSalesOrderStatus,
        ];
    }
}
