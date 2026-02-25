<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Auth\Permissions;
use App\Invoice\{
BaseController, Client\ClientRepository as CR,
CustomField\CustomFieldRepository as CFR,
CustomValue\CustomValueRepository as CVR,
DeliveryLocation\DeliveryLocationRepository as DR,
Entity\CustomField, Entity\DeliveryLocation, Entity\Group, Entity\Inv,
Entity\InvAllowanceCharge, Entity\InvAmount, Entity\InvCustom, Entity\InvItem,Entity\InvItemAllowanceCharge, Entity\InvTaxRate, Entity\SalesOrder,
Entity\SalesOrderAmount, Entity\SalesOrderCustom, Entity\SalesOrderItem,
Entity\SalesOrderTaxRate, Entity\SalesOrderItemAllowanceCharge, 
Group\GroupRepository as GR, Entity\SalesOrderAllowanceCharge,
Helpers\CustomValuesHelper as CVH, Helpers\PdfHelper, Inv\InvForm,
Inv\InvRepository as InvRepo, Inv\InvService,
InvAllowanceCharge\InvAllowanceChargeForm,
InvAllowanceCharge\InvAllowanceChargeService,
InvAmount\InvAmountForm,
InvAmount\InvAmountService, InvCustom\InvCustomForm, InvCustom\InvCustomService,
InvItem\InvItemForm, InvItem\InvItemService,
InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
InvItemAllowanceCharge\InvItemAllowanceChargeService as IIACS,
InvAmount\InvAmountRepository as IAR,
InvItemAmount\InvItemAmountRepository as IIAR,
InvItemAmount\InvItemAmountService as IIAS,
InvTaxRate\InvTaxRateForm, InvTaxRate\InvTaxRateService,
Product\ProductRepository as PR, ProductImage\ProductImageRepository as PIR,
Quote\QuoteRepository as QR, SalesOrder\SalesOrderRepository as SoR,
SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository as ACSOR,
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
Setting\SettingRepository,
Task\TaskRepository as TASKR,
TaxRate\TaxRateRepository as TRR,
Unit\UnitRepository as UNR,
UserClient\Exception\NoClientsAssignedToUserException,
UserClient\UserClientRepository as UCR,
UserInv\UserInvRepository as UIR,
};
use App\Service\WebControllerService;
use App\User\UserRepository as UR;
use App\User\UserService;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SalesOrderController extends BaseController
{
    protected string $controllerName = 'invoice/salesorder';

    public function __construct(
        private readonly DataResponseFactoryInterface $factory,
        private readonly InvService $invService,
        private readonly InvAllowanceChargeService $inv_allowance_charge_service,
        private readonly InvCustomService $inv_custom_service,
        private readonly InvAmountService $invAmountService,
        private readonly InvItemService $invItemService,
        private readonly IIACS $inv_item_ac_service,
        private readonly InvTaxRateService $invTaxRateService,
        private readonly SalesOrderService $salesorderService,
        Session $session,
        SettingRepository $sR,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
                $webViewRenderer, $session, $sR, $flash);
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
            // Use this user's id to see whether a user has been setup under
            // UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string) $user->getId()) > 0
                     ? $uiR->repoUserInvUserIdquery((string) $user->getId())
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
                $user_clients = $ucR->get_assigned_to_user((string) $user->getId());
                if (!empty($user_clients)) {
                    $salesOrders = $this->salesorders_status_with_sort_guest(
                        $soR, $status, $user_clients, $sort);
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
                        'defaultPageSizeOffsetPaginator' =>
                            $this->sR->getSetting('default_list_limit')
                                ? (int) $this->sR->getSetting(
                                        'default_list_limit') : 1,
                        'so_statuses' => $so_statuses,
                        'paginator' => $paginator,
                    ];
                    return $this->webViewRenderer->render('guest', $parameters);
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
     * @param string|null $queryFilterClient
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(CurrentRoute $currentRoute, CR $clientRepo,
            Request $request, SoAR $soaR, SoR $soR, InvRepo $iR,
            SettingRepository $sR,
            #[Query('filterClient')]
            ?string $queryFilterClient = null,
            #[Query('groupBy')]
            ?string $queryGroupBy = 'none'): \Psr\Http\Message\ResponseInterface
    {
        // If the language dropdown changes
        $this->session->set('_language', $currentRoute->getArgument('_language'));
        $query_params = $request->getQueryParams();
        $page = (int) $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        //status 0 => 'all';
        $status = (int) $currentRoute->getArgument('status', '0');
        /** @psalm-suppress MixedAssignment $sort_string */
        $sort_string = $query_params['sort'] ?? '-id';
        $sort = Sort::only(['id','status_id','number','date_created','client_id'])
                    // (Related logic: see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest quotes first => -id
                    /** @psalm-suppress MixedArgument $sort_string */
                    ->withOrderString((string) $sort_string);
        $salesorders = $this->salesorders_status_with_sort($soR, $status, $sort);
        if (isset($queryFilterClient) && !empty($queryFilterClient)) {
            $salesorders = $soR->filterClient($queryFilterClient)->withSort($sort);
        }
        $so_statuses = $soR->getStatuses($this->translator);
        $visible = $sR->getSetting('columns_all_visible') === '1';
        $parameters = [
            'alert' => $this->alert(),
            'soaR' => $soaR,
            'soR' => $soR,
            'iR' => $iR,
            'status' => $status,
            'defaultPageSizeOffsetPaginator' =>
                $this->sR->getSetting('default_list_limit')
                    ? (int) $this->sR->getSetting('default_list_limit') : 1,
            'so_statuses' => $so_statuses,
            'salesorders' => $salesorders,
            'client_count' => $clientRepo->count(),
            'groupBy' => $queryGroupBy,
            'visible' => $visible,
            'optionsDataClientsDropdownFilter' =>
                $this->optionsDataClientsFilter($soR),
            'sortString' => $sort_string,
            'page' => $currentPageNeverZero,
        ];
        return $this->webViewRenderer->render('index', $parameters);
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
    public function agree_to_terms(CurrentRoute $currentRoute, SoR $soR, UCR $ucR,
                                                            UIR $uiR): Response
    {
        $url_key = $currentRoute->getArgument('url_key');
        if (null !== $url_key) {
            if ($soR->repoUrl_key_guest_count($url_key) > 0) {
                $so = $soR->repoUrl_key_guest_loaded($url_key);
                if ($so && $this->rbacObserver($so, $ucR, $uiR)) {
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
                    return $this->factory->createResponse(
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/setting/salesorder_successful',
                        [
                            'heading' => $so_label,
                            'message' => $this->translator->translate(
                                'record.successfully.updated'),
                            'url' => 'salesorder/view','id' => $so_id,
                        ],
                    ));
                }
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
        if (null !== $url_key) {
            if ($soR->repoUrl_key_guest_count($url_key) > 0) {
                $so = $soR->repoUrl_key_guest_loaded($url_key);
                if ($so) {
                    // Only the observer user can reject the salesorder
                    // so check that the salesorder being rejected is linked to
                    // the current user
                    if ($this->rbacObserver($so, $ucR, $uiR)) {
                        $so_id = $so->getId();
                        // see SalesOrderRepository getStatuses function
                        $so->setStatus_id(9);
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
     * @param CurrentRoute $currentRoute
     * @param SoR $soR
     * @param SoIR $soiR
     * @param SoIS $soiS
     *
     * @return Response
     */
    public function url_key_guest_save_peppol(
        Request $request,
        CurrentRoute $currentRoute,
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
        if ($soR->repoUrl_key_guest_count($url_key) < 1) {
            return $this->factory->createResponse(
                Json::encode([
                    'success' => 0,
                    'message' =>
                            $this->translator->translate('salesorder.not.found')
                ])
            );
        }
        
        $salesorder = $soR->repoUrl_key_guest_loaded($url_key);
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
        if (!in_array($salesorder->getStatus_id(), [3, 4])) {
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
            $item = $soiR->repoSalesOrderItemquery($item_id);
            if ($item && $item->getSales_order_id() === $salesorder->getId()) {
                /** @var string $peppol_po_itemid */
                $peppol_po_itemid = $peppol_po_itemids[$item_id] ?? '';
                /** @var string $peppol_po_lineid */
                $peppol_po_lineid = $peppol_po_lineids[$item_id] ?? '';
                
                // Update the item with Peppol data
                $array = [
                    'peppol_po_itemid' => trim($peppol_po_itemid),
                    'peppol_po_lineid' => trim($peppol_po_lineid)
                ];
                
                if ($soiS->savePeppol_po_itemid($item, $array)) {
                    $updated_count++;
                }
                if ($soiS->savePeppol_po_lineid($item, $array)) {
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
     * @return \Yiisoft\Data\Reader\DataReaderInterface&\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, SalesOrder>
     */
    private function salesorders_status_with_sort(SoR $soRepo, int $status,
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
     * @return \Yiisoft\Data\Reader\DataReaderInterface&\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, SalesOrder>
     */
    private function salesorders_status_with_sort_guest(SoR $soR, int $status,
    array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
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
     * @param UIR $uiR
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
        UIR $uiR,
        CFR $cfR,
        CVR $cvR,
    ): Response {
        $so = $this->salesorder($currentRoute, $salesorderRepository);
        // Ensure that, even though the observer user has Permission editInv,
        // they edit and input the correct sales order with their purchase order
        // line numbers i.e. no browser manipulation
        if ($so && ($this->rbacObserver($so, $ucR, $uiR) || $this->rbacAdmin()
                                                || $this->rbacAccountant())) {
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
            $inv_number = null !== $inv_id
                    && null !== $inv ? (string) $inv->getNumber() : '';
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'salesorder/edit',
                'actionArguments' => ['id' => $so->getId()],
                'actionArgumentsDelAdd' => [
                    'client_id' => $so->getClient_id(),
                    'origin' => 'salesorder',
                    'origin_id' => $so->getClient_id(),
                    'action' => 'edit',
                ],
                // Only make clients that have a user account available in the
                // drop down list
                'optionsData' => $this->optionsData(
                    (int) $so->getClient_id(),
                    $clientRepo,
                    $delRepo,
                    $gR,
                    $soR,
                    $ucR,
                ),
                'errors' => [],
                'form' => $form,
                'invNumber' => $inv_number,
                // if the delivery location is zero present the link to delivery
                // locations add
                'delCount' => $delRepo->repoClientCount($so->getClient_id()),
                'dels' => $dels,
                'terms_and_conditions_file' =>
                    $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/terms_and_conditions_file'),
                'terms_and_conditions' =>
                    $settingRepository->getTermsAndConditions(),
                // if there are no delivery locations add a flash message
                'no_delivery_locations' => $delRepo->repoClientCount(
                    $so->getClient_id()) > 0 ? '' :
                        $this->flashMessage(
                                'warning', $this->translator->translate(
                                    'quote.delivery.location.none')),
                'alert' => $this->alert(),
                'so' => $so,
                'cfR' => $cfR,
                'cvR' => $cvR,
                'so_custom_values' =>
                    null !== $so_id ? $this->salesorder_custom_values(
                        $so_id, $socR) : null,
                'so_statuses' => $soR->getStatuses($this->translator),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->salesorderService->saveSo($so, $body);
                        $this->flashMessage(
                            'success', $this->translator->translate(
                                'record.successfully.updated'));
                        return $this->webService->getRedirectResponse(
                            'salesorder/index');
                    }
                }
                $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_form_edit', $parameters);
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
        SoAS $soaS,
    ): Response {
        try {
            $so = $this->salesorder($currentRoute, $salesorderRepository);
            if ($so) {
                $this->salesorderService->deleteSo($so, $socR, $socS, $soiR,
                    $soiS, $sotrR, $sotrS, $soaR, $soaS);
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
     * @param string $so_id
     * @param SoCR $salesorder_customR
     * @return array
     */
    public function salesorder_custom_values(string $so_id,
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
    
    public function pdf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR,
        DR $dlR, SoAR $soaR, SoCR $socR, SoIR $soiR, SoIAR $soiaR,
        ACSOIR $acsoiR, SoR $soR, SoTRR $sotrR, SettingRepository $sR,
        UIR $uiR): \Psr\Http\Message\ResponseInterface
    {
        // include is a value of 0 or 1 passed from quote.js function
        // quote_to_pdf_with(out)_custom_fields indicating whether the user
        // wants custom fields included on the quote or not.
        $include = $currentRoute->getArgument('include');
        $so_id = (string) $this->session->get('so_id');
        $salesorder_amount = (($soaR->repoSalesOrderAmountCount($so_id) > 0) ?
            $soaR->repoSalesOrderquery($so_id) : null);
        if ($salesorder_amount) {
            $custom = (($include === (string) 1) ? true : false);
            $salesorder_custom_values = $this->salesorder_custom_values(
                (string) $this->session->get('so_id'), $socR);
            // session is passed to the pdfHelper and will be used for the
            // locale ie. $session->get('_language') or the print_language
            // ie $session->get('print_language')
            $pdfhelper = new PdfHelper($sR, $this->session, $this->translator);
            // The salesorder will be streamed ie. shown in the browser, and
            // not archived
            $stream = true;
            $so = $soR->repoSalesOrderUnloadedquery($so_id);
            if ($so) {
                $pdfhelper->generate_salesorder_pdf($so_id, $so->getUser_id(),
                    $stream, $custom, $salesorder_amount,
                        $salesorder_custom_values, $cR, $cvR, $cfR, $dlR,
                            $soiR, $soiaR, $acsoiR, $soR, $sotrR, $uiR,
                                $this->webViewRenderer, $this->translator);
                $parameters = ($include == '1'
                ? [
                    'success' => 1,
                ]
                : [
                    'success' => 0,
                ]);
                return $this->factory->createResponse(Json::encode($parameters));
            } // $inv
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        } // quote_amount
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param int $id
     * @param string $_language
     * @param ACSOIR $acsoiR
     * @param ACSOR $acsoR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param DR $dR
     * @param GR $gR
     * @param PR $pR
     * @param TASKR $taskR
     * @param PIR $piR
     * @param QR qR,
     * @param SoAR $soaR
     * @param SoIAR $soiaR
     * @param SoIR $soiR
     * @param SoR $soR
     * @param SoTRR $sotrR
     * @param TRR $trR
     * @param UNR $uR
     * @param SoCR $socR
     * @param InvRepo $invRepo
     * @param SettingRepository $settingRepository
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(
        #[RouteArgument('id')]
        int $id,
        #[RouteArgument('_language')]
        string $_language,
        ACSOIR $acsoiR,
        ACSOR $acsoR,
        CFR $cfR,
        CVR $cvR,
        DR $dR,
        GR $gR,
        PR $pR,
        TASKR $taskR,
        PIR $piR,
        QR $qR,
        SoAR $soaR,
        SoIAR $soiaR,
        SoIR $soiR,
        SoR $soR,
        SoTRR $sotrR,
        TRR $trR,
        UCR $ucR,
        UIR $uiR,    
        UNR $uR,
        SoCR $socR,
        InvRepo $invRepo,
        SettingRepository $settingRepository,
    ): \Psr\Http\Message\ResponseInterface {
        $so = $this->salesorderunloaded($id, $soR, false);
        if ($so) {
            $so_id = $so->getId();
            // pdf => need session variable
            $this->session->set('so_id', $so_id);
            $so_tax_rates = (($sotrR->repoCount((string) $so_id) > 0) ?
                $sotrR->repoSalesOrderquery((string) $so_id) : null);
            $inv_id = $so->getInv_id();
            if (null !== $inv_id) {
                $inv = $invRepo->repoInvUnloadedquery($inv_id);
                $invNumber = ($inv ? $inv->getNumber() : '');
            } else {
                $invNumber = '';
            }
            $quote_id = $so->getQuote_id();
            if ($quote_id !== '' && $quote_id !== '0') {
                $quote = $qR->repoQuoteUnLoadedQuery($quote_id);
                $quoteNumber = ($quote ? $quote->getNumber() : '');
            } else {
                $quoteNumber = '';
            }
            $so_amount = (($soaR->repoSalesOrderAmountCount(
                (string) $so_id) > 0) ? $soaR->repoSalesOrderquery(
                    (string) $so_id) : null);
            if ($so_amount) {
                $salesorder_custom_values = $this->salesorder_custom_values(
                        (string) $so_id, $socR);
                $form = new SalesOrderForm($so);
                $parameters = [
                    'alert' => $this->alert(),
                    'title' => $this->translator->translate('view'),
                    'invEdit' => $this->userService->hasPermission(
                        Permissions::EDIT_INV) ? true : false,
                    'errors' => [],
                    'form' => $form,
                    'so' => $so,
                    'soItems' => $soiR->repoSalesOrderquery((string) $so_id),
                    'soR' => $soR,
                    'invNumber' => $invNumber,
                    'quoteNumber' => $quoteNumber,
                    // Get all the fields that have been setup for this SPECIFIC
                    // salesorder in salesorder_custom.
                    'fields' => $socR->repoFields(
                        (string) $this->session->get('quote_id')),
                    // Get the standard extra custom fields built for EVERY quote.
                    'customFields' =>
                        $this->fetchCustomFieldsAndValues($cfR, $cvR,
                            'salesorder_custom')['customFields'],
                    'customValues' =>
                        $this->fetchCustomFieldsAndValues($cfR, $cvR,
                            'salesorder_custom')['customValues'],
                    'cvH' => new CVH($settingRepository, $cvR),
                    'terms_and_conditions' =>
                        $settingRepository->getTermsAndConditions(),
                    'soStatuses' => $soR->getStatuses($this->translator),
                    'salesOrderCustomValues' => $salesorder_custom_values,
                    'partial_item_table' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/partial_item_table', [
                        'acsoiR' => $acsoiR,
                        'packHandleShipTotal' => $acsoR->getPackHandleShipTotal(
                                    (string) $so->getId()),
                        'included' => $this->translator->translate(
                                'item.tax.included'),
                        'excluded' => $this->translator->translate(
                                'item.tax.excluded'),
                        'invEdit' => $this->userService->hasPermission(
                                Permissions::EDIT_INV) ? true : false,
                        'editClientPeppol' => $this->userService->hasPermission(
                                Permissions::EDIT_CLIENT_PEPPOL) ? true : false,
                        'piR' => $piR,
                        'invView' => $this->userService->hasPermission(
                                Permissions::VIEW_INV) ? true : false,
                        'products' => $pR->findAllPreloaded(),
                        'soItems' => $soiR->repoSalesOrderquery(
                                (string) $so_id),
                        'soiaR' => $soiaR,
                        'soTaxRates' => $so_tax_rates,
                        'soAmount' => $so_amount,
                        'so' => $so,
                        'language' => $_language,
                        'taxRates' => $trR->findAllPreloaded(),
                        'tasks' => $taskR->findAllPreloaded(),
                        'units' => $uR->findAllPreloaded(),
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
                        'gR' => $gR,
                    ]),
                    'view_custom_fields' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/salesorder/view_custom_fields', [
                        'customFields' => $this->fetchCustomFieldsAndValues(
                            $cfR, $cvR, 'salesorder_custom')['customFields'],
                        'customValues' => $this->fetchCustomFieldsAndValues(
                            $cfR, $cvR, 'salesorder_custom')['customValues'],
                        'form' => $form,
                        'salesOrderCustomValues' => $salesorder_custom_values,
                        'cvH' => new CVH($settingRepository, $cvR),
                    ]),
                    'partial_quote_delivery_location' => null!==
                        ($quote = $qR->repoQuoteUnLoadedQuery(
                            $so->getQuote_id())) ?
                        $this->view_partial_delivery_location(
                            $_language, $dR, $quote->getDelivery_location_id())
                                : '',
                ];
                if ($this->rbacObserver($so, $ucR, $uiR)) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAdmin()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAccountant()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
            } // $so_amount
        } // $so->getId()
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
        $statusId = $so->getStatus_id();
        if (null!==$statusId) {
            // has observer role
            if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && !($this->userService->hasPermission(Permissions::EDIT_INV))
                // the salesorder has passed the 'draft' stage i.e sent / appears
                // in the observer user's guest index
                && !($statusId === 1)
                // the salesorder is intended for the current user
                && ($so->getUser_id() === $this->userService->getUser()?->getId())
                // the salesorder client is associated with the above user
                && ($ucR->repoUserClientqueryCount($so->getUser_id(),
                                                $so->getClient_id()) > 0)) {
                $userInv = $uiR->repoUserInvUserIdquery($so->getUser_id());
                // the current observer user is active
                if (null !== $userInv && $userInv->getActive()) {
                    return true;
                }
            }
        }    
        return false;
    }
    
    private function rbacAccountant() : bool {
        // has accountant role
        if (($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::VIEW_PAYMENT))
            && ($this->userService->hasPermission(Permissions::EDIT_PAYMENT)))) {
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
            return $salesorderRepository->repoSalesOrderLoadedquery($id);
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
            return $unloaded ? $soR->repoSalesOrderUnLoadedquery((string) $id)
                : $soR->repoSalesOrderLoadedquery((string) $id);
        }
        return null;
    }

    /**
     * This function will be done by the Admin as soon as the sales order has
     * 'invoice generate' status
     * The Sales Order will have the status 'invoice generated' against it
     * The Invoice will have the status 'sent' against it
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ACSOIR $acsoiR
     * @param CFR $cfR
     * @param GR $gR
     * @param InvRepo $iR
     * @param IAR $iaR
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function so_to_invoice_confirm(
        #[RouteArgument('id')] string $id = '',
        Request $request,
        FormHydrator $formHydrator,
        ACIIR $aciiR,
        ACSOR $acsoR,
        ACSOIR $acsoiR,
        CFR $cfR,
        GR $gR,
        InvRepo $iR,    
        IAR $iaR,
        IIAR $iiaR,
        IIAS $iiaS,
        PR $pR,
        SoAR $soaR,
        SoCR $socR,
        SoIAR $soiaR,
        SoIR $soiR,
        SoR $soR,
        SoTRR $sotrR,
        TASKR $taskR,
        TRR $trR,
        UNR $unR,
        SettingRepository $sR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): \Psr\Http\Message\ResponseInterface {
        $body = $request->getQueryParams();
        // Support both URL path parameter (/so_to_invoice/66) and query
        // parameter (?so_id=66)
        $so_id = $id !== '' ? $id : (string) ($body['so_id'] ?? '');
        $so = $soR->repoSalesOrderUnloadedquery($so_id);
        if ($so) {
            // Get client_id from sales order entity or fallback to query params
            $client_id = $so->getClient_id() ?:
                (string) ($body['client_id'] ?? '');
            // Use default invoice group, not the sales order group
            $group_id = $sR->getSetting('default_invoice_group');
            
            $inv_body = [
                'client_id' => $client_id,
                'group_id' => $group_id,
                'quote_id' => $so->getQuote_id(),
                'so_id' => $so->getId(),
                'status_id' => 2,
                'password' => $body['password'] ?? '',
                'number' => $gR->generate_number((int) $group_id),
                'discount_amount' => (float) $so->getDiscount_amount(),
                'url_key' => $so->getUrl_key(),
                'payment_method' => 0,
                'terms' => '',
                'creditinvoice_parent_id' => '',
            ];
            $inv = new Inv();
            $form = new InvForm($inv);
            if ($formHydrator->populateAndValidate($form, $inv_body)
                  // Salesorder has not been copied before:  inv_id = 0
                  && ($so->getInv_id() === (string) 0)
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
                            $this->invService->saveInv($user, $inv, $inv_body,
                                                                    $sR, $gR);
                            $inv_id = $inv->getId();
                            if (null !== $inv_id) {
                                // Transfer each so_item to inv_item and the
                                // corresponding so_item_amount to
                                // inv_item_amount for each item
                                $this->so_to_invoice_so_items($so_id, $inv_id,
                                        $aciiR, $acsoiR, $iiaR, $iiaS, $pR,
                                        $taskR, $soiaR, $soiR, $trR,
                                        $formHydrator, $sR, $unR);
                                $this->so_to_invoice_so_tax_rates(
                                    $so_id, $inv_id, $sotrR, $formHydrator);
                                $this->so_to_invoice_so_custom(
                                    $so_id, $inv_id, $socR, $cfR, $formHydrator);
                                $this->so_to_invoice_so_amount($so, $inv, $iR);
                                $this->so_to_invoice_so_allowance_charges(
                                    $so_id, $inv_id, $acsoR, $formHydrator);
                                // Update the sos inv_id.
                                $so->setInv_id($inv_id);
                                // Set salesorder's status to invoice generated
                                $so->setStatus_id(8);
                                $this->flashMessage('info',
                                    $this->translator->translate(
                                        'salesorder.invoice.generated'));
                                $soR->save($so);
                                
                                // Check if this is an AJAX request
                                $isAjax = $request->getHeaderLine(
                                    'X-Requested-With') === 'XMLHttpRequest';
                                
                                if ($isAjax) {
                                    // Return JSON for AJAX requests
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
                                    // Direct browser request - redirect to
                                    // invoice view
                                    return $this->webService->getRedirectResponse(
                                        'inv/view', ['id' => $inv_id]);
                                }
                            } // null!==$inv_id
                        } // null!==$user_inv && $user_inv->getActive()
                    } // null!==$user
                }
            } else {
                // Check if this is an AJAX request
                $isAjax = $request->getHeaderLine(
                    'X-Requested-With') === 'XMLHttpRequest';
                
                if ($isAjax) {
                    // Return JSON for AJAX requests
                    $parameters = [
                        'success' => 0,
                        'flash_message' => $this->translator->translate(
                            'salesorder.copied.to.invoice.not'),
                    ];
                    return $this->factory->createResponse(Json::encode($parameters));
                } else {
                    // Direct browser request - redirect back to sales order
                    // view
                    $this->flashMessage('danger',
                        $this->translator->translate(
                            'salesorder.copied.to.invoice.not'));
                    return $this->webService->getRedirectResponse(
                        'salesorder/view', ['id' => $so_id]);
                }
            }
        } // so
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param string $so_id
     * @param string $inv_id
     * @param ACIIR $aciiR
     * @param ACSOIR $acsoiR
     * @param IIAR $iiaR
     * @param IIAS $iiaS
     * @param PR $pR
     * @param TASKR $taskR
     * @param SOIR $soiR
     * @param TRR $trR
     * @param FormHydrator $formHydrator
     * @param SettingRepository $sR
     * @param UNR $unR
     */
    private function so_to_invoice_so_items(string $so_id, string $new_inv_id,
        ACIIR $aciiR, ACSOIR $acsoiR, IIAR $iiaR, IIAS $iiaS, PR $pR, TASKR $taskR,
            SoIAR $soiaR, SoIR $soiR, TRR $trR, FormHydrator $formHydrator,
                SettingRepository $sR, UNR $unR): void
    {
        // Get all items that belong to the salesorder
        $items = $soiR->repoSalesOrderItemIdquery($so_id);
        /** @var SalesOrderItem $so_item */
        foreach ($items as $so_item) {
            $origSoItemId = $so_item->getId();
            $newInvItem = new InvItem();
            $inv_item = [
                'inv_id' => $new_inv_id,
                'so_item_id' => $origSoItemId,
                'tax_rate_id' => $so_item->getTax_rate_id(),
                'product_id' => $so_item->getProduct_id(),
                'task_id' => $so_item->getTask_id(),
                'product_unit' => $so_item->getProduct_unit(),
                'product_unit_id' => $so_item->getProduct_unit_id(),
                'peppol_po_itemid' => $so_item->getPeppol_po_itemid(),
                'peppol_po_lineid' => $so_item->getPeppol_po_lineid(),
                'name' => $so_item->getName(),
                'description' => $so_item->getDescription(),
                'quantity' => $so_item->getQuantity(),
                'price' => $so_item->getPrice(),
                'discount_amount' => $so_item->getDiscount_amount(),
                'order' => $so_item->getOrder(),
                'is_recurring' => 0,
                // Recurring date
                'date' => '',
            ];
            $form = new InvItemForm($newInvItem, (int) $new_inv_id);
            if ($formHydrator->populateAndValidate($form, $inv_item)) {
                $savedInvItem = $this->invItemService->addInvItemProductTask(
                    $newInvItem, $inv_item, $new_inv_id, $pR, $taskR, $iiaR,
                    $iiaS, $unR, $trR, $this->translator, $sR);
                $this->copy_so_item_allowance_charges_to_inv(
                        $origSoItemId, $acsoiR, $new_inv_id, 
                        $savedInvItem, $aciiR);
                $tax_rate_percentage = $this->invItemService->taxrate_percentage(
                        (int) $inv_item['tax_rate_id'], $trR);
                if (isset($inv_item['quantity'], $inv_item['price'],
                    $inv_item['discount_amount'])
                    && null !== $tax_rate_percentage
                ) {
                   /**
                    * Note: Although, at first glance, the allowances and charges
                    * do not appear to be here, they are in fact worked out with a
                    * $this->aciiR in the function below which creates their
                    * accumulative totals and saves it using the $iiaR which
                    * is inherited from the so_item_service constructor
                    */
                    $this->invItemService->saveInvItemAmount(
                        (int) $savedInvItem->getId(),
                        $inv_item['quantity'],
                        $inv_item['price'],
                        $inv_item['discount_amount'],
                        $tax_rate_percentage,
                        $iiaS,
                        $iiaR,
                        $this->sR,
                    );
                }
            }
        } // items
    }
    
    
    private function copy_so_item_allowance_charges_to_inv(
        string $origSoItemId, ACSOIR $acsoiR, string $new_inv_id,
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
            $acInvItem->setInv_id((int) $new_inv_id);
            $acInvItem->setInv_item_id((int) $newInvItem->getId());
            $acInvItem->setAllowance_charge_id(
            (int) $salesOrderItemAllowanceCharge->getAllowanceCharge()?->getId()
            );
            
            // Set other properties
            $acInvItem->setAmount((float)
                $salesOrderItemAllowanceCharge->getAmount());
            $acInvItem->setVatOrTax((float)
                $salesOrderItemAllowanceCharge->getVatOrTax() ?: 0.00);
            $aciiR->save($acInvItem);
        }
    }
    
    /**
     * @param string $so_id
     * @param string|null $inv_id
     * @param SOTRR $sotrR
     * @param FormHydrator $formHydrator
     */
    private function so_to_invoice_so_tax_rates(string $so_id, ?string $inv_id,
        SoTRR $sotrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the salesorder
        $so_tax_rates = $sotrR->repoSalesOrderquery($so_id);
        /** @var SalesOrderTaxRate $so_tax_rate */
        foreach ($so_tax_rates as $so_tax_rate) {
            $inv_tax_rate = [
                'inv_id' => (string) $inv_id,
                'tax_rate_id' => $so_tax_rate->getTax_rate_id(),
                'include_item_tax' => $so_tax_rate->getInclude_item_tax(),
                'inv_tax_rate_amount' =>
                    $so_tax_rate->getSales_order_tax_rate_amount(),
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
        ?string $inv_id,
        SoCR $socR,
        CFR $cfR,
        FormHydrator $formHydrator,
    ): void {
        $so_customs = $socR->repoFields($so_id);
        // For each salesorder custom field, build a new custom field for
        // 'inv_custom' using the custom_field_id to find details
        /** @var SalesOrderCustom $so_custom */
        foreach ($so_customs as $so_custom) {
            // For each so custom field, build a new custom field for
            // 'inv_custom'
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery(
                $so_custom->getCustom_field_id());
            if ($cfR->repoTableAndLabelCountquery('inv_custom',
                (string) $existing_custom_field->getLabel()) !== 0) {
                // Build an identitcal custom field for the invoice
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
                    $this->inv_custom_service->saveInvCustom(
                        $entity, $inv_custom);
                }
            } // existing_custom_field
        } // foreach
    }

    /**
     * @param SalesOrder $so
     * @param Inv $inv
     * @param InvRepo $iR
     * @return void
     */
    private function so_to_invoice_so_amount(
                                    SalesOrder $so, Inv $inv, InvRepo $iR): void
    {
        /**
         * @var SalesOrderAmount $soA
         */
        $soA = $so->getSales_order_amount();
        /**
         * @var InvAmount $iA
         */
        $iA = $inv->getInvAmount();
        // hydrate
        $iA->setInv_id((int) $inv->getId());
        $iA->setItem_subtotal(
            $soA->getItem_subtotal() ?? 0.00);
        $iA->setItem_tax_total(
            $soA->getItem_tax_total() ?? 0.00);
        $iA->setPackhandleship_total(
            $soA->getPackhandleship_total() ?: 0.00);
        $iA->setPackhandleship_tax(
            $soA->getPackhandleship_tax() ?: 0.00);
        $iA->setTax_total($soA->getTax_total() ?? 0.00);
        $iA->setTotal($soA->getTotal() ?? 0.00);
        $iR->save($inv);
    }

    private function so_to_invoice_so_allowance_charges(
        string $so_id,
        string $new_inv_id,
        ACSOR $acsoR,
        FormHydrator $formHydrator
    ): void {
        $so_allowance_charges = $acsoR->repoACSOquery($so_id);
        /**
         * @var SalesOrderAllowanceCharge $so_allowance_charge
         */
        foreach ($so_allowance_charges as $so_allowance_charge) {
            $new_inv_ac = [
                'inv_id' => $new_inv_id,
                'allowance_charge_id' =>
                    $so_allowance_charge->getAllowance_charge_id(),
                'amount' => $so_allowance_charge->getAmount(),
            ];
            $invAllowanceCharge = new InvAllowanceCharge();
            $form = new InvAllowanceChargeForm($invAllowanceCharge,
                (int) $new_inv_id);
            if ($formHydrator->populateAndValidate($form, $new_inv_ac)) {
                $this->inv_allowance_charge_service->saveInvAllowanceCharge(
                    $invAllowanceCharge, $new_inv_ac
                );
            }
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
    public function url_key(CurrentRoute $currentRoute, CurrentUser $currentUser,
        CFR $cfR, SoAR $soaR, SoIR $soiR, SoIAR $soiaR, ACSOIR $acsoiR,
            SoR $soR, SoTRR $sotrR, UIR $uiR, UCR $ucR): Response
    {
        // Get the url key from the browser
        $url_key = $currentRoute->getArgument('key');

        // If there is no quote with such a url_key, issue a not found response
        if ($url_key === null) {
            return $this->webService->getNotFoundResponse();
        }

        // If there is a salesorder with the url key ... continue or else issue
        // not found response
        if ($soR->repoUrl_key_guest_count($url_key) < 1) {
            return $this->webService->getNotFoundResponse();
        }
        $salesorder = $soR->repoUrl_key_guest_loaded($url_key);
        $salesorder_tax_rates = null;
        if ($salesorder) {
            $salesorder_id = $salesorder->getId();
            if (null !== $salesorder_id) {
                if ($sotrR->repoCount($salesorder_id) > 0) {
                    $salesorder_tax_rates = $sotrR->repoSalesOrderquery(
                        $salesorder_id);
                }
            }
            if (in_array($salesorder->getStatus_id(), [2,3,4,5,6,7,8,9,10])) {
// If the user exists
/**
 * @psalm-suppress PossiblyNullArgument $this->userService->getUser()?->getId()
 */
                if ($uiR->repoUserInvUserIdcount(
                        $this->userService->getUser()?->getId()) === 1) {
                    // After signup the user was included in the userinv using
                    // Settings...User Account...+
                    $user_inv = $uiR->repoUserInvUserIdquery(
                        $this->userService->getUser()?->getId());
                    // The client has been assigned to the user id using Setting
                    // ...User Account...Assigned Clients
                    $user_client = $ucR->repoUserClientqueryCount(
                        $this->userService->getUser()?->getId(),
                            $salesorder->getClient_id()) === 1 ? true : false;
                    if ($user_inv && $user_client && $user_inv->getActive()) {
                        // If the userinv is a Guest => type = 1 ie. NOT an
                        // administrator =>type = 0 and they are active
                        // So if the user has a type of 1 they are a guest.
                        if ($user_inv->getType() == 1) {
                            $soR->save($salesorder);
                            $custom_fields = [
                                'invoice' => $cfR->repoTablequery('inv_custom'),
                                'client' => $cfR->repoTablequery('client_custom'),
                                'sales_order' =>
                                            $cfR->repoTablequery('sales_order'),
                            ];
                            if (null !== $salesorder_id) {
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
                                                    $salesorder->getUser_id())
                                            > 0 ? $uiR->repoUserInvUserIdquery(
                                              $salesorder->getUser_id()) : null,
                                        ]),
                                    ];
                                    return $this->webViewRenderer->render('url_key',
                                        $parameters);
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
        UCR $ucR,
    ): array {
        $dLocs = $delRepo->repoClientquery((string) $client_id);
        $optionsDataDeliveryLocations = [];
        /**
         * @var DeliveryLocation $dLoc
         */
        foreach ($dLocs as $dLoc) {
            $dLocId = $dLoc->getId();
            if (null !== $dLocId) {
                $optionsDataDeliveryLocations[$dLocId] =
                    ($dLoc->getAddress_1() ?? '')
                        . ', ' . ($dLoc->getAddress_2() ?? '') . ', '
                        . ($dLoc->getCity() ?? '') . ', '
                        . ($dLoc->getZip() ?? '');
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
        foreach ($salesOrderRepo->getStatuses($this->translator) as
            $key => $status) {
            $optionsDataSalesOrderStatus[$key] = (string) $status['label'];
        }
        return $optionsData = [
            'client' => $clientRepo->optionsData($ucR),
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'salesOrderStatus' => $optionsDataSalesOrderStatus,
        ];
    }

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
            if (null !== $client) {
                if (strlen($client->getClient_full_name()) > 0) {
                    $fullName = $client->getClient_full_name();
                    $optionsDataClients[$client->getClient_full_name()] =
                        !empty($fullName) ? $fullName : '';
                }
            }
        }
        return $optionsDataClients;
    }
}
