<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\{
        CustomField\CustomField,
        DeliveryLocation\DeliveryLocation,
        Inv\Inv,
        User\User,
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
    private readonly SalesOrderToInvoiceConverter $soToInvConverter;
    private readonly SalesOrderRbacGuard $rbac;

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
        $this->soToInvConverter = new SalesOrderToInvoiceConverter(
            $this->invItemService,
            $this->invTaxRateService,
            $this->inv_custom_service,
            $this->inv_allowance_charge_service,
            $this->translator,
        );
        $this->rbac = new SalesOrderRbacGuard($this->userService);
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

        $user = $this->userService->getUser();
        if (!$user) {
            return $this->webService->getNotFoundResponse();
        }
        $userId = $user->reqId();
        $userinv = $uiR->repoUserInvUserIdcount($userId) > 0
            ? $uiR->repoUserInvUserIdquery($userId)
            : null;
        if (!$userinv) {
            return $this->webService->getNotFoundResponse();
        }
        $user_clients = $ucR->getAssignedToUser($userId);
        if (empty($user_clients)) {
            $this->flashMessage('warning', $this->translator->translate('user.clients.assigned.not'));
            return $this->webService->getNotFoundResponse();
        }
        $salesOrders = $soR->repoGuestStatuses($status, $user_clients)->withSort($sort);
        $soLimit = $userinv->getListLimit();
        $paginator = (new OffsetPaginator($salesOrders))
            ->withPageSize($soLimit !== null && $soLimit > 0 ? $soLimit : $this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero);
        $so_statuses = $soR->getStatuses($this->translator);
        $parameters = [
            'alert'                          => $this->alert(),
            'soaR'                           => $soaR,
            'soR'                            => $soR,
            'status'                         => $status,
            'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                ? (int) $this->sR->getSetting('default_list_limit') : 1,
            'so_statuses'                    => $so_statuses,
            'paginator'                      => $paginator,
        ];
        return $this->webViewRenderer->render('guest', $parameters);
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
        $salesorders = $soR->findAllWithStatus($status)->withSort($sort);
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
            if ($so && $this->rbac->isObserver($so, $ucR, $uiR)) {
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
            if ($so && $this->rbac->isObserver($so, $ucR, $uiR)) {
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
        $salesorder = null;
        $errorMessage = null;

        if (null === $url_key) {
            $errorMessage = $this->translator->translate('error.occurred');
        } elseif ($soR->repoUrlKeyGuestCount($url_key) < 1) {
            $errorMessage = $this->translator->translate('salesorder.not.found');
        } else {
            $salesorder = $soR->repoUrlKeyGuestLoaded($url_key);
            if (!$salesorder) {
                $errorMessage = $this->translator->translate('salesorder.not.found');
            } elseif (!in_array($salesorder->getStatusId(), [3, 4])) {
                // Only allow updates when status is 3 (Client Agreed to Terms) or 4
                // (Delivery/Completion)
                $errorMessage = $this->translator->translate('salesorder.peppol.invalid.status');
            }
        }

        if ($errorMessage !== null) {
            return $this->factory->createResponse(
                Json::encode(['success' => 0, 'message' => $errorMessage])
            );
        }

        assert($salesorder instanceof SalesOrder);
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
                $array = [
                    'peppol_po_itemid' => trim($peppol_po_itemid),
                    'peppol_po_lineid' => trim($peppol_po_lineid)
                ];
                if ($soiS->savePeppolPoItemid($item, $array)) {
                    $updated_count++;
                }
                $soiS->savePeppolPoLineid($item, $array);
            }
        }

        return $this->factory->createResponse(
            Json::encode($updated_count > 0
                ? [
                    'success' => 1,
                    'message' => $this->translator->translate('invoice.peppol.saved.successfully'),
                    'updated_items' => $updated_count,
                  ]
                : ['success' => 0, 'message' => $this->translator->translate('no.changes.made')]
            )
        );
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        SalesOrderEditDependencies $d,
    ): Response {
        $so = $this->salesorder($currentRoute, $d->soR);
        if ($so && ($this->rbac->isObserver($so, $d->ucR, $d->uiR) || $this->rbac->isAdmin()
                                                || $this->rbac->isAccountant())) {
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
        if (!$so) {
            return $this->webService->getNotFoundResponse();
        }
        $so_id = $so->reqId();
        $this->session->set('so_id', $so_id);
        $so_tax_rates = $service->core->sotrR->repoCount($so_id) > 0
            ? $service->core->sotrR->repoSalesOrderquery($so_id) : null;
        $inv_id = $so->reqInvId();
        $inv = $service->meta->invRepo->repoInvUnloadedquery($inv_id);
        $invNumber = $inv ? $inv->getNumber() : '';
        $quote_id = $so->reqQuoteId();
        $quote = $service->relation->qR->repoQuoteUnLoadedQuery($quote_id);
        $quoteNumber = $quote?->getNumber() ?? 'None';
        $so_amount = $service->core->soaR->repoSalesOrderAmountCount($so_id) > 0
            ? $service->core->soaR->repoSalesOrderquery($so_id) : null;
        if (!$so_amount) {
            return $this->webService->getNotFoundResponse();
        }
        $salesorder_custom_values = $this->salesorderCustomValues($so_id, $service->core->socR);
        $form = SalesOrderForm::show($so);
        $parameters = [
            'alert'              => $this->alert(),
            'title'              => $this->translator->translate('view'),
            'invEdit'            => $this->userService->hasPermission(Permissions::EDIT_INV) ? true : false,
            'errors'             => [],
            'form'               => $form,
            'so'                 => $so,
            'soItems'            => $service->core->soiR->repoSalesOrderquery($so_id),
            'soR'                => $service->core->soR,
            'invNumber'          => $invNumber,
            'quoteNumber'        => $quoteNumber,
            'fields'             => $service->core->socR->repoFields((int) $this->session->get('quote_id')),
            'customFields'       => $this->fetchCustomFieldsAndValues($service->meta->cfR, $service->meta->cvR, 'salesorder_custom')['customFields'],
            'customValues'       => $this->fetchCustomFieldsAndValues($service->meta->cfR, $service->meta->cvR, 'salesorder_custom')['customValues'],
            'cvH'                => new CVH($service->meta->settingRepository, $service->meta->cvR),
            'terms_and_conditions' => $service->meta->settingRepository->getTermsAndConditions(),
            'soStatuses'         => $service->core->soR->getStatuses($this->translator),
            'salesOrderCustomValues' => $salesorder_custom_values,
            'partial_item_table' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/salesorder/partial_item_table', [
                    'acsoiR'            => $service->relation->acsoiR,
                    'packHandleShipTotal' => $service->relation->acsoR->getPackHandleShipTotal($so->reqId()),
                    'included'          => $this->translator->translate('item.tax.included'),
                    'excluded'          => $this->translator->translate('item.tax.excluded'),
                    'invEdit'           => $this->userService->hasPermission(Permissions::EDIT_INV) ? true : false,
                    'editClientPeppol'  => $this->userService->hasPermission(Permissions::EDIT_CLIENT_PEPPOL) ? true : false,
                    'piR'               => $service->items->piR,
                    'invView'           => $this->userService->hasPermission(Permissions::VIEW_INV) ? true : false,
                    'products'          => $service->items->pR->findAllPreloaded(),
                    'soItems'           => $service->core->soiR->repoSalesOrderquery($so_id),
                    'soiaR'             => $service->core->soiaR,
                    'soTaxRates'        => $so_tax_rates,
                    'soAmount'          => $so_amount,
                    'so'                => $so,
                    'language'          => $_language,
                    'taxRates'          => $service->items->trR->findAllPreloaded(),
                    'tasks'             => $service->items->taskR->findAllPreloaded(),
                    'units'             => $service->items->uR->findAllPreloaded(),
                ]),
            'modal_salesorder_to_pdf' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/salesorder/modal_salesorder_to_pdf', ['so' => $so]),
            'modal_so_to_invoice' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/salesorder/modal_so_to_invoice', ['so' => $so, 'gR' => $service->meta->gR]),
            'view_custom_fields' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/salesorder/view_custom_fields', [
                    'customFields'          => $this->fetchCustomFieldsAndValues($service->meta->cfR, $service->meta->cvR, 'salesorder_custom')['customFields'],
                    'customValues'          => $this->fetchCustomFieldsAndValues($service->meta->cfR, $service->meta->cvR, 'salesorder_custom')['customValues'],
                    'form'                  => $form,
                    'salesOrderCustomValues' => $salesorder_custom_values,
                    'cvH'                   => new CVH($service->meta->settingRepository, $service->meta->cvR),
                ]),
            'partial_quote_delivery_location' => null !==
                ($quote = $service->relation->qR->repoQuoteUnLoadedQuery($so->reqQuoteId()))
                ? $this->viewPartialDeliveryLocation($_language, $service->relation->dR, $quote->getDeliveryLocationId())
                : '',
        ];
        if ($this->rbac->isObserver($so, $service->relation->ucR, $service->relation->uiR)
            || $this->rbac->isAdmin() || $this->rbac->isAccountant()) {
            return $this->webViewRenderer->render('view', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

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
        Request $request,
        FormHydrator $formHydrator,
        SoToInvoiceDependencies $d,
        #[RouteArgument('id')] string $id = '',
    ): \Psr\Http\Message\ResponseInterface {
        $body = $request->getQueryParams();
        $so_id = $id !== '' ? (int) $id : (int) ($body['so_id'] ?? '');
        $so = $d->soR->repoSalesOrderUnloadedquery($so_id);
        if (!$so) {
            return $this->webService->getNotFoundResponse();
        }
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
        if (!$formHydrator->populateAndValidate($form, $inv_body) || $so->reqInvId() !== 0) {
            return $this->buildSoToInvFailResponse($request, $so_id);
        }
        /** @var string $inv_body['client_id'] */
        $client_id = (int) $inv_body['client_id'];
        $user_client = $d->ucR->repoUserquery($client_id);
        $user_client_count = $d->ucR->repoUserquerycount($client_id);
        $response = null;
        if (null !== $user_client && $user_client_count == 1) {
            $user_id = $user_client->reqUserId();
            $user = $d->uR->findById($user_id);
            $user_inv = $d->uiR->repoUserInvUserIdquery($user_id);
            if (null !== $user_inv && $user_inv->getActive()) {
                $response = $this->performSoToInvSave($user, $inv, $inv_body, $so, $d, $formHydrator, $request);
            }
        }
        return $response ?? $this->webService->getNotFoundResponse();
    }

    private function buildSoToInvFailResponse(Request $request, int $soId): \Psr\Http\Message\ResponseInterface
    {
        if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->factory->createResponse(Json::encode([
                'success'       => 0,
                'flash_message' => $this->translator->translate('salesorder.copied.to.invoice.not'),
            ]));
        }
        $this->flashMessage('danger', $this->translator->translate('salesorder.copied.to.invoice.not'));
        return $this->webService->getRedirectResponse('salesorder/view', ['id' => $soId]);
    }

    /** @param array<array-key, mixed> $inv_body */
    private function performSoToInvSave(
        User $user,
        Inv $inv,
        array $inv_body,
        SalesOrder $so,
        SoToInvoiceDependencies $d,
        FormHydrator $formHydrator,
        Request $request,
    ): Response {
        $this->invService->saveInv($user, $inv, $inv_body, $d->sR, $d->gR);
        $inv_id = $inv->reqId();
        $so_id = $so->reqId();
        $this->soToInvConverter->soToInvoiceSoItems($so_id, $inv_id, $formHydrator, $d);
        $this->soToInvConverter->soToInvoiceSoTaxRates($so_id, $inv_id, $d, $formHydrator);
        $this->soToInvConverter->soToInvoiceSoCustom($so_id, $inv_id, $d, $formHydrator);
        $this->soToInvConverter->soToInvoiceSoAmount($so, $inv, $d);
        $this->soToInvConverter->soToInvoiceSoAllowanceCharges($so_id, $inv_id, $d, $formHydrator);
        $so->setInvId($inv_id);
        $so->setStatusId(8);
        $this->flashMessage('info', $this->translator->translate('salesorder.invoice.generated'));
        $d->soR->save($so);
        $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
        return $isAjax
            ? $this->factory->createResponse(Json::encode([
                'success' => 1,
                'flash_message' => $this->translator->translate('salesorder.copied.to.invoice'),
                'inv_id' => $inv_id,
            ]))
            : $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
    }

    public function urlKey(CurrentRoute $currentRoute, CurrentUser $currentUser, SoUrlKeyDeps $deps): Response
    {
        $soR = $deps->soR;
        $sotrR = $deps->sotrR;
        $uiR = $deps->uiR;
        // Get the url key from the browser
        $url_key = $currentRoute->getArgument('key');

        // If there is no salesorder with such a url_key, issue a not found response
        if ($url_key === null || $soR->repoUrlKeyGuestCount($url_key) < 1) {
            return $this->webService->getNotFoundResponse();
        }
        $salesorder = $soR->repoUrlKeyGuestLoaded($url_key);
        if (!$salesorder) {
            return $this->webService->getNotFoundResponse();
        }
        $salesorder_id = $salesorder->reqId();
        $salesorder_tax_rates = $sotrR->repoCount($salesorder_id) > 0
            ? $sotrR->repoSalesOrderquery($salesorder_id)
            : null;
        /**
         * @psalm-suppress PossiblyNullArgument $this->userService->getUser()?->reqId()
         */
        if (in_array($salesorder->getStatusId(), [2,3,4,5,6,7,8,9,10])
            && $uiR->repoUserInvUserIdcount((int)
                    $this->userService->getUser()?->reqId()) === 1) {
            $result = $this->renderSalesOrderForGuest(
                $salesorder, $deps, $salesorder_tax_rates, $url_key, $currentUser);
            if ($result !== null) {
                return $result;
            }
        }
        return $this->webService->getNotFoundResponse();
    }

    private function renderSalesOrderForGuest(
        SalesOrder $salesorder,
        SoUrlKeyDeps $deps,
        mixed $salesorder_tax_rates,
        string $url_key,
        CurrentUser $currentUser,
    ): ?Response {
        $uiR = $deps->uiR;
        $ucR = $deps->ucR;
        $cfR = $deps->cfR;
        $soaR = $deps->soaR;
        $soiR = $deps->soiR;
        $soiaR = $deps->soiaR;
        $acsoiR = $deps->acsoiR;
        $salesorder_id = $salesorder->reqId();
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        $user_inv = $uiR->repoUserInvUserIdquery((int) $this->userService->getUser()?->reqId());
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        $user_client = $ucR->repoUserClientqueryCount(
            $this->userService->getUser()?->reqId(),
            $salesorder->reqClientId()) === 1;
        if (!($user_inv && $user_client && $user_inv->getActive() && $user_inv->getType() == 1)) {
            return null;
        }
        $deps->soR->save($salesorder);
        $custom_fields = [
            'invoice' => $cfR->repoTablequery('inv_custom'),
            'client' => $cfR->repoTablequery('client_custom'),
            'sales_order' => $cfR->repoTablequery('sales_order'),
        ];
        $salesorder_amount = $soaR->repoSalesOrderAmountCount($salesorder_id) > 0
            ? $soaR->repoSalesOrderquery($salesorder_id)
            : null;
        if (!$salesorder_amount) {
            return null;
        }
        $parameters = [
            'renderTemplate' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/template/salesorder/public/'
                    . ($this->sR->getSetting('public_salesorder_template') ?: 'SalesOrder_Web'), [
                    'isGuest' => $currentUser->isGuest(),
                    'terms_and_conditions_file' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/terms_and_conditions_file'),
                    'alert' => $this->alert(),
                    'salesorder' => $salesorder,
                    'soiaR' => $soiaR,
                    'acsoiR' => $acsoiR,
                    'salesorder_amount' => $salesorder_amount,
                    'items' => $soiR->repoSalesOrderquery($salesorder_id),
                    'salesorder_tax_rates' => $salesorder_tax_rates,
                    'salesorder_url_key' => $url_key,
                    'custom_fields' => $custom_fields,
                    'client' => $salesorder->getClient(),
                    'userInv' => $uiR->repoUserInvUserIdcount($salesorder->reqUserId()) > 0
                        ? $uiR->repoUserInvUserIdquery($salesorder->reqUserId())
                        : null,
                ]),
        ];
        return $this->webViewRenderer->render('url_key', $parameters);
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
