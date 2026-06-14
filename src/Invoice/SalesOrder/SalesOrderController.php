<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\{
        CustomField\CustomField,
        DeliveryLocation\DeliveryLocation,
        Inv\Inv,
        InvCustom\InvCustom, InvItem\InvItem,
        InvItemAllowanceCharge\InvItemAllowanceCharge,
        InvTaxRate\InvTaxRate,
        InvAllowanceCharge\InvAllowanceCharge,
        SalesOrder\SalesOrder,
        SalesOrderAllowanceCharge\SalesOrderAllowanceCharge,
        SalesOrderCustom\SalesOrderCustom,
        SalesOrderItem\SalesOrderItem,
        SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceCharge,
        SalesOrderTaxRate\SalesOrderTaxRate,
};
use App\Invoice\{
    BaseController, Client\ClientRepository as CR,
    CustomField\CustomFieldRepository as CFR,
    DeliveryLocation\DeliveryLocationRepository as DR,
    Group\GroupRepository as GR,
    Helpers\CustomValuesHelper as CVH, Inv\InvForm,
    Inv\InvRepository as InvRepo, Inv\InvService,
    InvAllowanceCharge\InvAllowanceChargeForm, InvAllowanceCharge\InvAllowanceChargeService,
    InvCustom\InvCustomForm, InvCustom\InvCustomService,
    InvItem\InvItemForm, InvItem\InvItemService,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvTaxRate\InvTaxRateForm, InvTaxRate\InvTaxRateService,
    SalesOrder\SalesOrderRepository as SoR,
    SalesOrderAmount\SalesOrderAmountRepository as SoAR,
    SalesOrderAmount\SalesOrderAmountService as SoAS,
    SalesOrderCustom\SalesOrderCustomRepository as SoCR,
    SalesOrderCustom\SalesOrderCustomService as SoCS,
    SalesOrderItem\SalesOrderItemRepository as SoIR,
    SalesOrderItem\SalesOrderItemService as SoIS,
    SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR,
    SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR,
    SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR,
    SalesOrderTaxRate\SalesOrderTaxRateService as SoTRS,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use App\Invoice\SalesOrder\{SalesOrderPdfService, SoDeleteFinancialDeps, SoDeleteSubEntityDeps, SoUrlKeyDeps, Widget\SalesOrdersListWidget};
use App\Widget\SalesOrderToolbar;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\DataReaderInterface as DRI;
use Yiisoft\Data\Reader\SortableDataInterface as SDI;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\User\CurrentUser;

final class SalesOrderController extends BaseController
{
    protected string $controllerName = 'invoice/salesorder';

    private readonly DataResponseFactoryInterface $factory;
    private readonly InvService $invService;
    private readonly InvAllowanceChargeService $inv_allowance_charge_service;
    private readonly InvCustomService $inv_custom_service;
    private readonly InvItemService $invItemService;
    private readonly InvTaxRateService $invTaxRateService;
    private readonly SalesOrderService $salesorderService;
    private readonly SalesOrderToolbar $salesOrderToolbar;

    public function __construct(
        SoControllerBaseDeps $base,
        SoControllerInvDeps $inv,
        SoControllerMiscDeps $misc,
    ) {
        parent::__construct(
            $base->webService, $base->userService, $base->translator,
            $base->webViewRenderer, $base->session, $base->sR, $base->flash
        );
        $this->factory                      = $misc->factory;
        $this->invService                   = $inv->invService;
        $this->inv_allowance_charge_service = $inv->invAllowanceChargeService;
        $this->inv_custom_service           = $inv->invCustomService;
        $this->invItemService               = $inv->invItemService;
        $this->invTaxRateService            = $inv->invTaxRateService;
        $this->salesorderService            = $misc->salesorderService;
        $this->salesOrderToolbar            = $misc->salesOrderToolbar;
    }

    /**
     * @param Request $request
     * @param SOAR $soaR
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function guest(
        Request $request,
        SoAR $soaR,
        CurrentRoute $currentRoute,
        SoR $soR,
        UCR $ucR,
        UIR $uiR,
    ): \Psr\Http\Message\ResponseInterface {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $pageNum = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $pageNum > 0 ? (int) $pageNum : 1;
        //status 0 => 'all';
        $status = (int) $currentRoute->getArgument('status', '0');
        /** @psalm-suppress MixedAssignment $sort_string */
        $sort_string = $query_params['sort'] ?? '-id';
        $sort =  Sort::only([
                    'status_id',
                    'number',
                    'date_created',
                    'id','client_id'])->withOrderString((string) $sort_string);

        // Get the current user and determine from (Related logic:
        // see Settings...User Account) whether they have been given
        // either guest or admin rights. These rights are unrelated to rbac
        // and serve as a second 'line of defense' to support role based admin
        // control.

        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser();
        if ($user) {
            $userId = $user->reqId();
            // Use this user's id to see whether a user has been setup under
            // UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount($userId) > 0
                     ? $uiR->repoUserInvUserIdquery($userId)
                     : null);
            if ($userinv) {
                // Determine what clients have been allocated to this user
                // (Related logic: see Settings...User Account) by looking at
                // UserClient table

                // eg. If the user is a guest-accountant, they will have been
                // allocated certain clients. A user-quest-accountant will be
                // allocated a series of clients. A user-guest-client will be
                // allocated their client number by the administrator so that
                // they can view their salesorders when they log in
                $user_clients = $ucR->getAssignedToUser($userId);
                if (!empty($user_clients)) {
                    $salesOrders = $this->salesordersStatusWithSortGuest(
                        $soR, $status, $user_clients, $sort);
                    $soLimit = $userinv->getListLimit();
                    $paginator = (new OffsetPaginator($salesOrders))
                    ->withPageSize($soLimit !== null && $soLimit > 0
                        ? $soLimit
                        : $this->sR->positiveListLimit())
                    ->withCurrentPage($currentPageNeverZero);
                    $so_statuses = $soR->getStatuses($this->translator);
                    $parameters = [
                        'alert' => $this->alert(),
                        'soaR' => $soaR,
                        'soR' => $soR,
                        'status' => $status,
                        'defaultPageSizeOffsetPaginator' =>
                            $this->sR->getSetting('default_list_limit')
                                ? (int) $this->sR->getSetting(
                                        'default_list_limit') : 1,
                        'so_statuses' => $so_statuses,
                        'paginator' => $paginator,
                    ];
                    return $this->webViewRenderer->render('guest', $parameters);
                }
                $this->flashMessage('warning',
                            $this->translator->translate('user.clients.assigned.not'));
            } // userinv
            return $this->webService->getNotFoundResponse();
        } //user
        return $this->webService->getNotFoundResponse();
    }

    public function index(
        CurrentRoute $currentRoute,
        Request $request,
        SoAR $soaR,
        SoR $soR,
        InvRepo $iR,
        HtmlResponseFactory $htmlResponseFactory,
    ): \Psr\Http\Message\ResponseInterface {
        $this->session->set('_language', $currentRoute->getArgument('_language'));
        $q                    = $request->getQueryParams();
        $routePage            = (int) $currentRoute->getArgument('page', '1');
        $queryPage            = isset($q['page']) ? (int) $q['page'] : null;
        $currentPageNeverZero = max(1, $queryPage ?? $routePage);
        $status               = (int) $currentRoute->getArgument('status', '0');
        /** @psalm-suppress MixedAssignment */
        $sortString           = isset($q['sort']) ? (string) $q['sort'] : '-id';
        $queryFilterClient    = isset($q['filterClient']) ? (string) $q['filterClient'] : null;
        $queryGroupBy         = isset($q['groupBy']) ? (string) $q['groupBy'] : 'none';
        $sort = Sort::only(['id', 'status_id', 'number', 'date_created', 'client_id'])
            /** @psalm-suppress MixedArgument */
            ->withOrderString($sortString);
        $salesorders = $this->salesordersStatusWithSort($soR, $status, $sort);
        if (isset($queryFilterClient) && $queryFilterClient !== '') {
            $salesorders = $soR->filterClient($queryFilterClient)->withSort($sort);
        }
        /** @psalm-suppress InvalidArgument */
        $paginator = (new OffsetPaginator($salesorders))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero);
        $visible      = $this->sR->getSetting('columns_all_visible') === '1';
        $gridSummary  = $this->sR->gridSummary(
            $paginator,
            $this->translator,
            (int) $this->sR->getSetting('default_list_limit'),
            $this->translator->translate('sales.orders'),
            $soR->getSpecificStatusArrayLabel((string) $status),
        );
        $optionsDataClientsDropdownFilter = $this->optionsDataClientsFilter($soR);
        if ($request->hasHeader('Hx-Request')) {
            return $htmlResponseFactory->createResponse(
                SalesOrdersListWidget::widget()
                    ->withPaginator($paginator)
                    ->withSoR($soR)
                    ->withSoAR($soaR)
                    ->withIR($iR)
                    ->withSR($this->sR)
                    ->withCsrf((string) ($request->getParsedBody()['_csrf'] ?? ''))
                    ->withVisible($visible)
                    ->withGroupBy($queryGroupBy)
                    ->withGridSummary($gridSummary)
                    ->withSortString($sortString)
                    ->withStatus($status)
                    ->withSalesOrderToolbar($this->salesOrderToolbar->render())
                    ->withOptionsDataClientsDropdownFilter($optionsDataClientsDropdownFilter)
                    ->render()
            );
        }
        $parameters = [
            'alert'                           => $this->alert(),
            'soaR'                            => $soaR,
            'soR'                             => $soR,
            'iR'                              => $iR,
            'status'                          => $status,
            'defaultPageSizeOffsetPaginator'  =>
                (int) $this->sR->getSetting('default_list_limit') ?: 1,
            'paginator'                       => $paginator,
            'groupBy'                         => $queryGroupBy,
            'visible'                         => $visible,
            'optionsDataClientsDropdownFilter' => $optionsDataClientsDropdownFilter,
            'sortString'                      => $sortString,
            'page'                            => $currentPageNeverZero,
            'salesOrderToolbar'               => $this->salesOrderToolbar->render(),
            'gridSummary'                     => $gridSummary,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * Bulk-change the status of selected sales orders.
     * Called by the SalesOrderToolbar TypeScript handler via AJAX.
     * @param Request $request
     * @param SoR $soR
     * @return Response
     */
    public function changeStatus(Request $request, SoR $soR): Response
    {
        $data = $request->getQueryParams();
        $parameters = ['success' => 0];
        /** @var array $data['keylist'] */
        $keyList = $data['keylist'] ?? [];
        $statusId = (int) ($data['status_id'] ?? 0);
        if (!empty($keyList) && $statusId >= 1 && $statusId <= 10) {
            /** @var string $value */
            foreach ($keyList as $value) {
                $so = $soR->repoSalesOrderUnLoadedquery((int) $value);
                if (null !== $so) {
                    $so->setStatusId($statusId);
                    $soR->save($so);
                    $parameters['success'] = 1;
                }
            }
            $this->flashMessage('info',
                $this->translator->translate('record.successfully.updated'));
        }
        return $this->factory->createResponse(Json::encode($parameters));
    }

    // Sales Orders are created from Quotes see quote/approve

    /**
     * Related logic: see SalesOrderRepository getStatuses function
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return Response
     */
    public function agreeToTerms(CurrentRoute $currentRoute, SoR $soR, UCR $ucR,
                                                            UIR $uiR): Response
    {
        $url_key = $currentRoute->getArgument('url_key');
        if (null !== $url_key && $soR->repoUrlKeyGuestCount($url_key) > 0) {
            $so = $soR->repoUrlKeyGuestLoaded($url_key);
            if ($so && $this->rbacObserver($so, $ucR, $uiR)) {
                $so_id = $so->reqId();
                $so->setStatusId(3);
                $soR->save($so);
                $so_statuses = $soR->getStatuses($this->translator);
                /*  @var string $status_id */
                $status_id = $so->getStatusId();
                /**
                 *  @var array $so_statuses[$status_id]
                 *  @var string $so_label
                 */
                $so_label = $so_statuses[$status_id]['label'];
                $this->flashMessage('success', $so_label
                        . ' '
                        . $this->translator->translate('record.successfully.updated'));
                return $this->webService->getRedirectResponse(
                            'salesorder/view', ['id' => $so_id]);
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * Related logic: see SalesOrderRepository getStatuses function
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return Response
     */
    public function reject(CurrentRoute $currentRoute, SoR $soR,
                                                    UCR $ucR, UIR $uiR): Response
    {
        $url_key = $currentRoute->getArgument('url_key');
        if (null !== $url_key && $soR->repoUrlKeyGuestCount($url_key) > 0) {
            $so = $soR->repoUrlKeyGuestLoaded($url_key);
            // Only the observer user can reject the salesorder
            // so check that the salesorder being rejected is linked to
            // the current user
            if ($so && $this->rbacObserver($so, $ucR, $uiR)) {
                $so_id = $so->reqId();
                // see SalesOrderRepository getStatuses function
                $so->setStatusId(9);
                $soR->save($so);
                return $this->factory->createResponse(
                    $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/salesorder_successful',
                        [
                            'heading' => $soR->getSpecificStatusArrayLabel(
                                (string) 9),
                            'message' => $this->translator->translate(
                                'record.successfully.updated'),
                            'url' => 'salesorder/view','id' => $so_id,
                        ],
                    ),
                );
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * Guest client saves Peppol PO Item ID and Line ID for sales order items
     * This is required before generating a Peppol electronic invoice
     * Related logic: see form_peppol_guest.php
     *
     * @param Request $request
     * @param SoR $soR
     * @param SoIR $soiR
     * @param SoIS $soiS
     *
     * @return Response
     */
    public function urlKeyGuestSavePeppol(
        Request $request,
        SoR $soR,
        SoIR $soiR,
        SoIS $soiS
    ): Response {
        /** @var array<string, mixed> $body */
        $body = $request->getParsedBody();
        /** @var string|null $url_key */
        $url_key = $body['url_key'] ?? null;

        if (null === $url_key) {
            return $this->factory->createResponse(
                Json::encode([
                    'success' => 0,
                    'message' => $this->translator->translate('error.occurred')
                ])
            );
        }

        // Verify the sales order exists and is accessible
        if ($soR->repoUrlKeyGuestCount($url_key) < 1) {
            return $this->factory->createResponse(
                Json::encode([
                    'success' => 0,
                    'message' =>
                            $this->translator->translate('salesorder.not.found')
                ])
            );
        }

        $salesorder = $soR->repoUrlKeyGuestLoaded($url_key);
        if (!$salesorder) {
            return $this->factory->createResponse(
                Json::encode([
                    'success' => 0,
                    'message' =>
                            $this->translator->translate('salesorder.not.found')
                ])
            );
        }

        // Only allow updates when status is 3 (Client Agreed to Terms) or 4
        // (Delivery/Completion)
        if (!in_array($salesorder->getStatusId(), [3, 4])) {
            return $this->factory->createResponse(
                Json::encode([
                    'success' => 0,
                    'message' =>
                $this->translator->translate('salesorder.peppol.invalid.status')
                ])
            );
        }

        /** @var array<string, string> $item_ids */
        $item_ids = $body['item_id'] ?? [];
        /** @var array<string, string> $peppol_po_itemids */
        $peppol_po_itemids = $body['peppol_po_itemid'] ?? [];
        /** @var array<string, string> $peppol_po_lineids */
        $peppol_po_lineids = $body['peppol_po_lineid'] ?? [];

        $updated_count = 0;

        // Update each item
        foreach ($item_ids as $item_id) {
            $item = $soiR->repoSalesOrderItemquery((int)$item_id);
            if ($item && $item->reqSalesOrderId() === $salesorder->reqId()) {
                $peppol_po_itemid = $peppol_po_itemids[$item_id] ?? '';
                $peppol_po_lineid = $peppol_po_lineids[$item_id] ?? '';

                // Update the item with Peppol data
                $array = [
                    'peppol_po_itemid' => trim($peppol_po_itemid),
                    'peppol_po_lineid' => trim($peppol_po_lineid)
                ];

                if ($soiS->savePeppolPoItemid($item, $array)) {
                    $updated_count++;
                }
                if ($soiS->savePeppolPoLineid($item, $array)) {
                    // Already counted above
                }
            }
        }

        if ($updated_count > 0) {
            return $this->factory->createResponse(
                Json::encode([
                    'success' => 1,
                    'message' =>
              $this->translator->translate('invoice.peppol.saved.successfully'),
                    'updated_items' => $updated_count
                ])
            );
        }

        return $this->factory->createResponse(
            Json::encode([
                'success' => 0,
                'message' => $this->translator->translate('no.changes.made')
            ])
        );
    }

    /**
     * @param SalesOrderRepository $soRepo
     * @param int $status
     * @param Sort $sort
     *
     * @return DRI&SDI
     *
     * @psalm-return SDI&DRI<int, SalesOrder>
     */
    private function salesordersStatusWithSort(SoR $soRepo, int $status,
        Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
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
     * @return DRI&SDI
     *
     * @psalm-return SDI&DRI<int, SalesOrder>
     */
    private function salesordersStatusWithSortGuest(SoR $soR, int $status,
    array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $soR->repoGuestStatuses($status, $user_clients)
                     ->withSort($sort);
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        SalesOrderEditDependencies $d,
    ): Response {
        $so = $this->salesorder($currentRoute, $d->soR);
        if ($so && ($this->rbacObserver($so, $d->ucR, $d->uiR) || $this->rbacAdmin()
                                                || $this->rbacAccountant())) {
            $form = SalesOrderForm::show($so);
            $dels = $d->delRepo->repoClientquery($so->reqClientId());
            $so_id = $so->reqId();
            $inv_id = $so->reqInvId();
            $inv = $inv_id > 0 ? $d->invRepo->repoInvUnloadedquery($inv_id) : null;
            $inv_number = null !== $inv ? (string) $inv->getNumber() : '';
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'salesorder/edit',
                'actionArguments' => ['id' => $so->reqId()],
                'actionArgumentsDelAdd' => [
                    'client_id' => (string) $so->reqClientId(),
                    'origin' => 'salesorder',
                    'origin_id' => (string) $so->reqClientId(),
                    'action' => 'edit',
                ],
                'optionsData' => $this->optionsData($so->reqClientId(),
                    $d->clientRepo,
                    $d->delRepo,
                    $d->gR,
                    $d->soR,
                    $d->ucR,
                ),
                'errors' => [],
                'form' => $form,
                'invNumber' => $inv_number,
                'delCount' => $d->delRepo->repoClientCount($so->reqClientId()),
                'dels' => $dels,
                'terms_and_conditions_file' =>
                    $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/terms_and_conditions_file'),
                'terms_and_conditions' =>
                    $d->settingRepository->getTermsAndConditions(),
                'no_delivery_locations' => $d->delRepo->repoClientCount(
                    $so->reqClientId()) > 0 ? '' :
                        $this->flashMessage(
                                'warning', $this->translator->translate(
                                    'quote.delivery.location.none')),
                'alert' => $this->alert(),
                'so' => $so,
                'cfR' => $d->cfR,
                'cvR' => $d->cvR,
                'so_custom_values' => $this->salesorderCustomValues($so_id, $d->socR),
                'so_statuses' => $d->soR->getStatuses($this->translator),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request) && is_array($body)) {
                    $this->salesorderService->saveSo($so, $body);
                    $this->flashMessage(
                        'success', $this->translator->translate(
                            'record.successfully.updated'));
                    return $this->webService->getRedirectResponse(
                        'salesorder/index');
                }
                $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_form_edit', $parameters);
        }
        return $this->webService->getRedirectResponse('salesorder/index');
    }

    public function delete(
        CurrentRoute $currentRoute,
        SoDeleteSubEntityDeps $subDeps,
        SoDeleteFinancialDeps $financialDeps,
    ): Response {
        try {
            $so = $this->salesorder($currentRoute, $subDeps->soR);
            if ($so) {
                $this->salesorderService->deleteSo($so, $subDeps, $financialDeps);
                $this->flashMessage('info', $this->translator->translate(
                    'record.successfully.deleted'));
                return $this->webService->getRedirectResponse('salesorder/index');
            }
            return $this->webService->getRedirectResponse('salesorder/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('salesorder/index');
        }
    }

    /**
     * @param int $so_id
     * @param SoCR $salesorder_customR
     * @return array
     */
    public function salesorderCustomValues(int $so_id,
                                                SoCR $salesorder_customR): array
    {
        // Get all the custom fields that have been registered with this
        // salesorder on creation, retrieve existing values via repo,
        // and populate custom_field_form_values array
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

    public function pdf(
        #[RouteArgument('include')] int $include,
        SalesOrderPdfService $soPdfService,
    ): \Psr\Http\Message\ResponseInterface {
        $soId = (int) $this->session->get('so_id');
        $path = $soPdfService->generate($soId, true, $include === 1);
        $parameters = $path !== '' ? ['success' => 1] : ['success' => 0];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    public function view(
        #[RouteArgument('id')]
        int $id,
        #[RouteArgument('_language')]
        string $_language,
        SalesOrderViewService $service,
    ): \Psr\Http\Message\ResponseInterface {
        $so = $this->salesorderunloaded($id, $service->core->soR, false);
        if ($so) {
            $so_id = $so->reqId();
            $this->session->set('so_id', $so_id);
            $so_tax_rates = (($service->core->sotrR->repoCount($so_id) > 0) ?
                $service->core->sotrR->repoSalesOrderquery($so_id) : null);
            $inv_id = $so->reqInvId();
            $inv = $service->meta->invRepo->repoInvUnloadedquery($inv_id);
            $invNumber = ($inv ? $inv->getNumber() : '');
            $quote_id = $so->reqQuoteId();
            $quote = $service->relation->qR->repoQuoteUnLoadedQuery($quote_id);
            $quoteNumber = $quote?->getNumber() ?? 'None';
            $so_amount = (($service->core->soaR->repoSalesOrderAmountCount(
                $so_id) > 0) ? $service->core->soaR->repoSalesOrderquery(
                    $so_id) : null);
            if ($so_amount) {
                $salesorder_custom_values = $this->salesorderCustomValues(
                        $so_id, $service->core->socR);
                $form = SalesOrderForm::show($so);
                $parameters = [
                    'alert' => $this->alert(),
                    'title' => $this->translator->translate('view'),
                    'invEdit' => $this->userService->hasPermission(
                        Permissions::EDIT_INV) ? true : false,
                    'errors' => [],
                    'form' => $form,
                    'so' => $so,
                    'soItems' => $service->core->soiR->repoSalesOrderquery($so_id),
                    'soR' => $service->core->soR,
                    'invNumber' => $invNumber,
                    'quoteNumber' => $quoteNumber,
                    'fields' => $service->core->socR->repoFields((int) $this->session->get('quote_id')),
                    'customFields' =>
                        $this->fetchCustomFieldsAndValues($service->meta->cfR, $service->meta->cvR,
                            'salesorder_custom')['customFields'],
                    'customValues' =>
                        $this->fetchCustomFieldsAndValues($service->meta->cfR, $service->meta->cvR,
                            'salesorder_custom')['customValues'],
                    'cvH' => new CVH($service->meta->settingRepository, $service->meta->cvR),
                    'terms_and_conditions' =>
                        $service->meta->settingRepository->getTermsAndConditions(),
                    'soStatuses' => $service->core->soR->getStatuses($this->translator),
                    'salesOrderCustomValues' => $salesorder_custom_values,
                    'partial_item_table' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/partial_item_table', [
                        'acsoiR' => $service->relation->acsoiR,
                        'packHandleShipTotal' => $service->relation->acsoR->getPackHandleShipTotal(
                                    $so->reqId()),
                        'included' => $this->translator->translate(
                                'item.tax.included'),
                        'excluded' => $this->translator->translate(
                                'item.tax.excluded'),
                        'invEdit' => $this->userService->hasPermission(
                                Permissions::EDIT_INV) ? true : false,
                        'editClientPeppol' => $this->userService->hasPermission(
                                Permissions::EDIT_CLIENT_PEPPOL) ? true : false,
                        'piR' => $service->items->piR,
                        'invView' => $this->userService->hasPermission(
                                Permissions::VIEW_INV) ? true : false,
                        'products' => $service->items->pR->findAllPreloaded(),
                        'soItems' => $service->core->soiR->repoSalesOrderquery($so_id),
                        'soiaR' => $service->core->soiaR,
                        'soTaxRates' => $so_tax_rates,
                        'soAmount' => $so_amount,
                        'so' => $so,
                        'language' => $_language,
                        'taxRates' => $service->items->trR->findAllPreloaded(),
                        'tasks' => $service->items->taskR->findAllPreloaded(),
                        'units' => $service->items->uR->findAllPreloaded(),
                    ]),
                    'modal_salesorder_to_pdf' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/modal_salesorder_to_pdf', [
                        'so' => $so,
                    ]),
                    'modal_so_to_invoice' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/modal_so_to_invoice', [
                        'so' => $so,
                        'gR' => $service->meta->gR,
                    ]),
                    'view_custom_fields' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/view_custom_fields', [
                        'customFields' => $this->fetchCustomFieldsAndValues(
                            $service->meta->cfR, $service->meta->cvR, 'salesorder_custom')['customFields'],
                        'customValues' => $this->fetchCustomFieldsAndValues(
                            $service->meta->cfR, $service->meta->cvR, 'salesorder_custom')['customValues'],
                        'form' => $form,
                        'salesOrderCustomValues' => $salesorder_custom_values,
                        'cvH' => new CVH($service->meta->settingRepository, $service->meta->cvR),
                    ]),
                    'partial_quote_delivery_location' => null !==
                        ($quote = $service->relation->qR->repoQuoteUnLoadedQuery($so->reqQuoteId())) ?
                        $this->viewPartialDeliveryLocation(
                            $_language, $service->relation->dR, $quote->getDeliveryLocationId())
                                : '',
                ];
                if ($this->rbacObserver($so, $service->relation->ucR, $service->relation->uiR)) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAdmin()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAccountant()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
            } // $so_amount
        } // $so->reqId()
        return $this->webService->getNotFoundResponse();
    }

    /**
     * Purpose:
     * Prevent browser manipulation and ensure that views are only accessible
     * to users 1. with the observer role's VIEW_INV permission and 2. supervise a
     * client requested salesorder and are an active current user for these
     * client's salesorders.
     * @param SalesOrder $so
     * @param UCR $ucR
     * @param UIR $uiR
     * @return bool
     */
    private function rbacObserver(SalesOrder $so, UCR $ucR, UIR $uiR) : bool {
        $statusId = $so->getStatusId();
        if (null !== $statusId
            // has observer role
            && $this->userService->hasPermission(Permissions::VIEW_INV)
            && !($this->userService->hasPermission(Permissions::EDIT_INV))
            // the salesorder has passed the 'draft' stage i.e sent / appears
            // in the observer user's guest index
            && !($statusId === 1)
            && (($soUserId = $so->reqUserId()) > 0)
            // the salesorder is intended for the current user
            && ($soUserId === $this->userService->getUser()?->reqId())
            // the salesorder client is associated with the above user
            && ($ucR->repoUserClientqueryCount($soUserId, $so->reqClientId()) > 0)) {
            $userInv = $uiR->repoUserInvUserIdquery($soUserId);
            // the current observer user is active
            if (null !== $userInv && $userInv->getActive()) {
                return true;
            }
        }
        return false;
    }

    private function rbacAccountant() : bool {
        // has accountant role
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
            && $this->userService->hasPermission(Permissions::VIEW_PAYMENT)
            && $this->userService->hasPermission(Permissions::EDIT_PAYMENT)) {
            return true;
        } else {
            return false;
        }
    }

    private function rbacAdmin() : bool {
        // has observer role
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::EDIT_INV))) {
            return true;
        } else {
            return false;
        }
    }

    //For rbac refer to AccessChecker

    /**
    * @param CurrentRoute $currentRoute
    * @param SalesOrderRepository $salesorderRepository
    * @return SalesOrder|null
    */
    private function salesorder(CurrentRoute $currentRoute,
                                        SoR $salesorderRepository): ?SalesOrder
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $salesorderRepository->repoSalesOrderLoadedquery((int) $id);
        }
        return null;
    }

     /**
     * @param int $id
     * @param SoR $soR
     * @param bool $unloaded
     * @return SalesOrder|null
     */
    private function salesorderunloaded(
        int $id,
        SoR $soR,
        bool $unloaded = false,
    ): ?SalesOrder {
        if ($id) {
            return $unloaded ? $soR->repoSalesOrderUnLoadedquery($id)
                : $soR->repoSalesOrderLoadedquery($id);
        }
        return null;
    }

    public function soToInvoiceConfirm(
        #[RouteArgument('id')] string $id = '',
        Request $request,
        FormHydrator $formHydrator,
        SoToInvoiceDependencies $d,
    ): \Psr\Http\Message\ResponseInterface {
        $body = $request->getQueryParams();
        $so_id = $id !== '' ? (int) $id : (int) ($body['so_id'] ?? '');
        $so = $d->soR->repoSalesOrderUnloadedquery($so_id);
        if ($so) {
            $client_id = ($so->reqClientId() ?: (int) ($body['client_id'] ?? ''));
            $group_id = $d->sR->getSetting('default_invoice_group');

            $inv_body = [
                'client_id' => $client_id,
                'group_id' => $group_id,
                'quote_id' => $so->reqQuoteId(),
                'so_id' => $so->reqId(),
                'status_id' => 2,
                'password' => $body['password'] ?? '',
                'number' => $d->gR->generateNumber((int) $group_id),
                'discount_amount' => (float) $so->getDiscountAmount(),
                'url_key' => $so->getUrlKey(),
                'payment_method' => 0,
                'terms' => '',
                'creditinvoice_parent_id' => '',
            ];
            $inv = new Inv();
            $form = new InvForm();
            if ($formHydrator->populateAndValidate($form, $inv_body)
                  && ($so->reqInvId() === 0)
            ) {
                /**
                 * @var string $inv_body['client_id']
                 */
                $client_id = (int) $inv_body['client_id'];
                $user_client = $d->ucR->repoUserquery($client_id);
                $user_client_count = $d->ucR->repoUserquerycount($client_id);
                if (null !== $user_client && $user_client_count == 1) {
                    $user_id = $user_client->reqUserId();
                    $user = $d->uR->findById($user_id);
                    $user_inv = $d->uiR->repoUserInvUserIdquery($user_id);
                    if (null !== $user_inv && $user_inv->getActive()) {
                        $this->invService->saveInv($user, $inv, $inv_body,
                                                                $d->sR, $d->gR);
                        $inv_id = $inv->reqId();
                        $this->soToInvoiceSoItems($so_id, $inv_id, $formHydrator, $d);
                        $this->soToInvoiceSoTaxRates($so_id, $inv_id, $d, $formHydrator);
                        $this->soToInvoiceSoCustom($so_id, $inv_id, $d, $formHydrator);
                        $this->soToInvoiceSoAmount($so, $inv, $d);
                        $this->soToInvoiceSoAllowanceCharges($so_id, $inv_id, $d, $formHydrator);
                        $so->setInvId($inv_id);
                        $so->setStatusId(8);
                        $this->flashMessage('info',
                            $this->translator->translate(
                                'salesorder.invoice.generated'));
                        $d->soR->save($so);

                        $isAjax = $request->getHeaderLine(
                            'X-Requested-With') === 'XMLHttpRequest';

                        if ($isAjax) {
                            $parameters = [
                                'success' => 1,
                                'flash_message' =>
                                    $this->translator->translate(
                                        'salesorder.copied.to.invoice'),
                                'inv_id' => $inv_id,
                            ];
                            return $this->factory->createResponse(
                                Json::encode($parameters));
                        } else {
                            return $this->webService->getRedirectResponse(
                                'inv/view', ['id' => $inv_id]);
                        }
                    }
                }
            } else {
                $isAjax = $request->getHeaderLine(
                    'X-Requested-With') === 'XMLHttpRequest';

                if ($isAjax) {
                    $parameters = [
                        'success' => 0,
                        'flash_message' => $this->translator->translate(
                            'salesorder.copied.to.invoice.not'),
                    ];
                    return $this->factory->createResponse(Json::encode($parameters));
                } else {
                    $this->flashMessage('danger',
                        $this->translator->translate(
                            'salesorder.copied.to.invoice.not'));
                    return $this->webService->getRedirectResponse(
                        'salesorder/view', ['id' => $so_id]);
                }
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    private function soToInvoiceSoItems(
        int $so_id,
        int $new_inv_id,
        FormHydrator $formHydrator,
        SoToInvoiceDependencies $d,
    ): void {
        $items = $d->soiR->repoSalesOrderItemIdquery($so_id);
        /** @var SalesOrderItem $so_item */
        foreach ($items as $so_item) {
            $origSoItemId = $so_item->reqId();
            $newInvItem = new InvItem();
            $inv_item = [
                'inv_id' => $new_inv_id,
                'so_item_id' => $origSoItemId,
                'tax_rate_id' => $so_item->getTaxRateId(),
                'product_id' => $so_item->getProduct()?->reqId(),
                'task_id' => $so_item->getTask()?->reqId(),
                'product_unit' => $so_item->getProductUnit(),
                'product_unit_id' => $so_item->getProductUnitId(),
                'peppol_po_itemid' => $so_item->getPeppolPoItemid(),
                'peppol_po_lineid' => $so_item->getPeppolPoLineid(),
                'name' => $so_item->getName(),
                'description' => $so_item->getDescription(),
                'quantity' => $so_item->getQuantity(),
                'price' => $so_item->getPrice(),
                'discount_amount' => $so_item->getDiscountAmount(),
                'order' => $so_item->getOrder(),
                'is_recurring' => 0,
                'date' => '',
            ];
            $form = new InvItemForm();
            if ($formHydrator->populateAndValidate($form, $inv_item)) {
                $savedInvItem = $this->invItemService->addInvItemProductTask(
                    $newInvItem, $inv_item, (string) $new_inv_id,
                    $d->pR, $d->taskR, $d->unR, $this->translator);
                $this->copySoItemAllowanceChargesToInv(
                        $origSoItemId, $d->acsoiR, $new_inv_id,
                        $savedInvItem, $d->aciiR);
                $tax_rate_percentage = $this->invItemService->taxratePercentage(
                        (int) $inv_item['tax_rate_id'], $d->trR);
                if (isset($inv_item['quantity'], $inv_item['price'],
                    $inv_item['discount_amount'])
                    && null !== $tax_rate_percentage
                ) {
                    $this->invItemService->saveInvItemAmount(
                        $savedInvItem->reqId(),
                        $inv_item['quantity'],
                        $inv_item['price'],
                        $inv_item['discount_amount'],
                        $tax_rate_percentage,
                        $d->iiaS,
                        $d->iiaR
                    );
                }
            }
        }
    }

    private function copySoItemAllowanceChargesToInv(
        int $origSoItemId, ACSOIR $acsoiR, int $new_inv_id,
            InvItem $newInvItem, ACIIR $aciiR): void {

        $all = $acsoiR->repoSalesOrderItemquery($origSoItemId);
        /**
         * @var SalesOrderItemAllowanceCharge $salesOrderItemAllowanceCharge
         */
        foreach ($all as $salesOrderItemAllowanceCharge) {
            $acInvItem = new InvItemAllowanceCharge();
            $acInvItem->setInv($newInvItem->getInv());
            $acInvItem->setInvItem($newInvItem);
            $acInvItem->setAllowanceCharge(
                            $salesOrderItemAllowanceCharge->getAllowanceCharge());

            // Also set FK IDs for consistency
            $acInvItem->setInvId($new_inv_id);
            $acInvItem->setInvItemId($newInvItem->reqId());
            $acInvItem->setAllowanceChargeId(
            (int) $salesOrderItemAllowanceCharge->getAllowanceCharge()?->reqId()
            );

            // Set other properties
            $acInvItem->setAmount((float)
                $salesOrderItemAllowanceCharge->getAmount());
            $acInvItem->setVatOrTax((float)
                $salesOrderItemAllowanceCharge->getVatOrTax() ?: 0.00);
            $aciiR->save($acInvItem);
        }
    }

    private function soToInvoiceSoTaxRates(
        int $so_id,
        int $inv_id,
        SoToInvoiceDependencies $d,
        FormHydrator $formHydrator,
    ): void {
        $so_tax_rates = $d->sotrR->repoSalesOrderquery($so_id);
        /** @var SalesOrderTaxRate $so_tax_rate */
        foreach ($so_tax_rates as $so_tax_rate) {
            $inv_tax_rate = [
                'inv_id' => $inv_id,
                'tax_rate_id' => $so_tax_rate->reqTaxRateId(),
                'include_item_tax' => $so_tax_rate->getIncludeItemTax(),
                'inv_tax_rate_amount' =>
                    $so_tax_rate->getSalesOrderTaxRateAmount(),
            ];
            $entity = new InvTaxRate();
            $form = new InvTaxRateForm();
            if ($formHydrator->populateAndValidate($form, $inv_tax_rate)) {
                $this->invTaxRateService->saveInvTaxRate($entity, $inv_tax_rate);
            }
        }
    }

    private function soToInvoiceSoCustom(
        int $so_id,
        int $inv_id,
        SoToInvoiceDependencies $d,
        FormHydrator $formHydrator,
    ): void {
        $so_customs = $d->socR->repoFields($so_id);
        /** @var SalesOrderCustom $so_custom */
        foreach ($so_customs as $so_custom) {
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $d->cfR->repoCustomFieldquery(
                $so_custom->reqCustomFieldId());
            if ($d->cfR->repoTableAndLabelCountquery('inv_custom',
                (string) $existing_custom_field->getLabel()) !== 0) {
                $custom_field = new CustomField();
                $custom_field->setTable('inv_custom');
                $custom_field->setLabel(
                    (string) $existing_custom_field->getLabel());
                $custom_field->setType(
                    $existing_custom_field->getType());
                $custom_field->setLocation(
                    (int) $existing_custom_field->getLocation());
                $custom_field->setOrder(
                    (int) $existing_custom_field->getOrder());
                $d->cfR->save($custom_field);
                $inv_custom = [
                    'inv_id' => $inv_id,
                    'custom_field_id' => $custom_field->reqId(),
                    'value' => $so_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm();
                if ($formHydrator->populateAndValidate($form, $inv_custom)) {
                    $this->inv_custom_service->saveInvCustom(
                        $entity, $inv_custom);
                }
            }
        }
    }

    private function soToInvoiceSoAmount(
        SalesOrder $so,
        Inv $inv,
        SoToInvoiceDependencies $d,
    ): void {
        $soA = $so->getSalesOrderAmount();
        $iA = $inv->getInvAmount();
        $iA->setInvId($inv->reqId());
        $iA->setItemSubtotal($soA->getItemSubtotal() ?? 0.00);
        $iA->setItemTaxTotal($soA->getItemTaxTotal() ?? 0.00);
        $iA->setPackhandleshipTotal($soA->getPackhandleshipTotal() ?: 0.00);
        $iA->setPackhandleshipTax($soA->getPackhandleshipTax() ?: 0.00);
        $iA->setTaxTotal($soA->getTaxTotal() ?? 0.00);
        $iA->setTotal($soA->getTotal() ?? 0.00);
        $d->iR->save($inv);
    }

    private function soToInvoiceSoAllowanceCharges(
        int $so_id,
        int $new_inv_id,
        SoToInvoiceDependencies $d,
        FormHydrator $formHydrator,
    ): void {
        $so_allowance_charges = $d->acsoR->repoACSOquery($so_id);
        /** @var SalesOrderAllowanceCharge $so_allowance_charge */
        foreach ($so_allowance_charges as $so_allowance_charge) {
            $new_inv_ac = [
                'inv_id' => $new_inv_id,
                'allowance_charge_id' =>
                    $so_allowance_charge->getAllowanceChargeId(),
                'amount' => $so_allowance_charge->getAmount(),
            ];
            $invAllowanceCharge = new InvAllowanceCharge();
            $form = InvAllowanceChargeForm::show($invAllowanceCharge, $new_inv_id);
            if ($formHydrator->populateAndValidate($form, $new_inv_ac)) {
                $this->inv_allowance_charge_service->saveInvAllowanceCharge(
                    $invAllowanceCharge, $new_inv_ac
                );
            }
        }
    }

    public function urlKey(CurrentRoute $currentRoute, CurrentUser $currentUser, SoUrlKeyDeps $deps): Response
    {
        $cfR = $deps->cfR;
        $soaR = $deps->soaR;
        $soiR = $deps->soiR;
        $soiaR = $deps->soiaR;
        $acsoiR = $deps->acsoiR;
        $soR = $deps->soR;
        $sotrR = $deps->sotrR;
        $uiR = $deps->uiR;
        $ucR = $deps->ucR;
        // Get the url key from the browser
        $url_key = $currentRoute->getArgument('key');

        // If there is no quote with such a url_key, issue a not found response
        if ($url_key === null) {
            return $this->webService->getNotFoundResponse();
        }

        // If there is a salesorder with the url key ... continue or else issue
        // not found response
        if ($soR->repoUrlKeyGuestCount($url_key) < 1) {
            return $this->webService->getNotFoundResponse();
        }
        $salesorder = $soR->repoUrlKeyGuestLoaded($url_key);
        $salesorder_tax_rates = null;
        if ($salesorder) {
            $salesorder_id = $salesorder->reqId();
            if ($sotrR->repoCount($salesorder_id) > 0) {
                $salesorder_tax_rates = $sotrR->repoSalesOrderquery(
                    $salesorder_id);
            }
// If the user exists
/**
 * @psalm-suppress PossiblyNullArgument $this->userService->getUser()?->reqId()
 */
            if (in_array($salesorder->getStatusId(), [2,3,4,5,6,7,8,9,10])
                && $uiR->repoUserInvUserIdcount((int)
                        $this->userService->getUser()?->reqId()) === 1) {
                // After signup the user was included in the userinv using
                // Settings...User Account...+
                $user_inv = $uiR->repoUserInvUserIdquery((int)
                    $this->userService->getUser()?->reqId());
                // The client has been assigned to the user id using Setting
                // ...User Account...Assigned Clients
                $user_client = $ucR->repoUserClientqueryCount(
                    $this->userService->getUser()?->reqId(),
                        $salesorder->reqClientId()) === 1 ? true : false;
                // If the userinv is a Guest => type = 1 ie. NOT an
                // administrator =>type = 0 and they are active
                // So if the user has a type of 1 they are a guest.
                if ($user_inv && $user_client && $user_inv->getActive() && $user_inv->getType() == 1) {
                    $soR->save($salesorder);
                    $custom_fields = [
                        'invoice' => $cfR->repoTablequery('inv_custom'),
                        'client' => $cfR->repoTablequery('client_custom'),
                        'sales_order' =>
                                    $cfR->repoTablequery('sales_order'),
                    ];
                    $salesorder_amount =
                        (($soaR->repoSalesOrderAmountCount(
                            $salesorder_id) > 0) ?
                                $soaR->repoSalesOrderquery(
                                    $salesorder_id) : null);
                    if ($salesorder_amount) {
                        $parameters = [
                            'renderTemplate' =>
                             $this->webViewRenderer->renderPartialAsString(
                                '//invoice/template/salesorder/public/'
                                     . ($this->sR->getSetting(
                                        'public_salesorder_template')
                                                ?: 'SalesOrder_Web'), [
                                    'isGuest' => $currentUser->isGuest(),
                                    'terms_and_conditions_file' =>
                             $this->webViewRenderer->renderPartialAsString(
                      '//invoice/salesorder/terms_and_conditions_file'),
                                    'alert' => $this->alert(),
                                    'salesorder' => $salesorder,
                                    'soiaR' => $soiaR,
                                    'acsoiR' => $acsoiR,
                                    'salesorder_amount' =>
                                                    $salesorder_amount,
                                    'items' =>
                            $soiR->repoSalesOrderquery($salesorder_id),
                                    // Get all the salesorder tax rates
                                    // that have been setup for this
                                    // salesorder
                                    'salesorder_tax_rates' =>
                                        $salesorder_tax_rates,
                                    'salesorder_url_key' => $url_key,
                                    'custom_fields' => $custom_fields,
                                    'client' => $salesorder->getClient(),
                                    // Get the details of the user of
                                    // this quote
                                    'userInv' =>
                                        $uiR->repoUserInvUserIdcount(
                                            $salesorder->reqUserId())
                                    > 0 ? $uiR->repoUserInvUserIdquery(
                                      $salesorder->reqUserId()) : null,
                                ]),
                        ];
                        return $this->webViewRenderer->render('url_key',
                            $parameters);
                    } // if salesorder_amount
                } // user_inv && getType
            } // if in_array && uiR
        } // if salesorder
        return $this->webService->getNotFoundResponse();
    }

    private function optionsData(
        int $client_id,
        CR $clientRepo,
        DR $delRepo,
        GR $groupRepo,
        SoR $salesOrderRepo,
        UCR $ucR,
    ): array {
        $dLocs = $delRepo->repoClientquery($client_id);
        $optionsDataDeliveryLocations = [];
        /**
         * @var DeliveryLocation $dLoc
         */
        foreach ($dLocs as $dLoc) {
            $dLocId = $dLoc->reqId();
              $optionsDataDeliveryLocations[$dLocId] =
                  ($dLoc->getAddress1() ?? '')
                      . ', ' . ($dLoc->getAddress2() ?? '') . ', '
                      . ($dLoc->getCity() ?? '') . ', '
                      . ($dLoc->getZip() ?? '');
        }
        $optionsDataGroup = [];
        /**
         * @var \App\Infrastructure\Persistence\Group\Group $group
         */
        foreach ($groupRepo->findAllPreloaded() as $group) {
            $optionsDataGroup[$group->reqId()] = $group->getName();
        }

        $optionsDataSalesOrderStatus = [];
        /**
         * @var string $key
         * @var array $status
         */
        foreach ($salesOrderRepo->getStatuses($this->translator) as
            $key => $status) {
            $optionsDataSalesOrderStatus[$key] = (string) $status['label'];
        }
        return [
            'client' => $clientRepo->optionsData($ucR),
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'salesOrderStatus' => $optionsDataSalesOrderStatus,
        ];
    }

    /** @return array<string, string> */
    public function optionsDataClientsFilter(SOR $soR): array
    {
        $optionsDataClients = [];
        // Get all the sales orders that have been made for clients
        $salesorders = $soR->findAllPreloaded();
        /**
         * @var SalesOrder $salesorder
         */
        foreach ($salesorders as $salesorder) {
            $client = $salesorder->getClient();
            if (null !== $client && strlen($client->getClientFullName()) > 0) {
                $fullName = $client->getClientFullName();
                $optionsDataClients[$client->getClientFullName()] =
                    !empty($fullName) ? $fullName : '';
            }
        }
        return $optionsDataClients;
    }
}
