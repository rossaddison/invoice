<?php

declare(strict_types=1); 

namespace App\Invoice\SalesOrder;

// Entity
use App\Invoice\Entity\CustomField;
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
use App\Invoice\SalesOrder\SalesOrderService;
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
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\PdfHelper;

// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class SalesOrderController
{
    private DataResponseFactoryInterface $factory;
    private Flash $flash;
    private InvService $invService;
    private InvCustomService $inv_custom_service;
    private InvAmountService $invAmountService;
    private InvItemService $invItemService;
    private InvTaxRateService $invTaxRateService;
    private Session $session;
    private SettingRepository $sR;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private SalesOrderService $salesorderService;
    private TranslatorInterface $translator;
    
    public function __construct(
        DataResponseFactoryInterface $factory,
        InvService $invService,
        InvCustomService $inv_custom_service,    
        InvAmountService $invAmountService,
        InvItemService $invItemService,
        InvTaxRateService $invTaxRateService,
        Session $session,
        SettingRepository $settingRepository,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        SalesOrderService $salesorderService,
        TranslatorInterface $translator
    )    
    {
        $this->factory = $factory;
        $this->flash = new Flash($session);
        $this->invService = $invService;
        $this->inv_custom_service = $inv_custom_service;
        $this->invAmountService = $invAmountService;
        $this->invItemService = $invItemService;
        $this->invTaxRateService = $invTaxRateService;
        $this->session = $session;
        $this->sR = $settingRepository;
        $this->webService = $webService;
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/salesorder')
                                                 ->withLayout('@views/layout/guest.php');
        }      
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/salesorder')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->salesorderService = $salesorderService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param Request $request
     * @param SOAR $soaR
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function guest(Request $request, SOAR $soaR, CurrentRoute $currentRoute,
                          SOR $soR, UCR $ucR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        $pageNum = (int)$currentRoute->getArgument('page', '1');
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
                $salesorders = $this->salesorders_status_with_sort_guest($soR, $status, $user_clients, $sort);
                $paginator = (new OffsetPaginator($salesorders))
                ->withPageSize((int)$this->sR->get_setting('default_list_limit'))
                ->withCurrentPage($pageNum);
                /**
                 * @var array $so_statuses
                 */
                $so_statuses = $soR->getStatuses($this->sR);
                /**
                 *  @var array $so_statuses[$status]
                 *  @var string $so_label 
                 */
                $so_label = $so_statuses[$status]['label'];
                $parameters = [            
                    'alert'=> $this->alert(),
                    'soaR'=> $soaR,
                    'salesorders' => $salesorders,
                    'grid_summary'=>
                        $this->sR->grid_summary($paginator, 
                        $this->translator, 
                        (int)$this->sR->get_setting('default_list_limit'), 
                        $this->translator->translate('invoice.salesorders'),
                        $so_label),
                    'so_statuses'=> $so_statuses,            
                    'max'=> (int) $this->sR->get_setting('default_list_limit'),
                    'page'=> $pageNum,
                    'paginator'=> $paginator,
                    's'=> $this->sR,
                    'sortOrder' => $sort_string, 
                    'status'=> $status,
                ];    
                return $this->viewRenderer->render('/invoice/salesorder/guest', $parameters);  
            } // userinv
            return $this->webService->getNotFoundResponse();
        } //user
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param SalesOrderRepository $salesorderRepo
     * @param Request $request
     * @param SoAR $soaR
     * @param SettingRepository $sR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(CurrentRoute $currentRoute, CR $clientRepo, Request $request, SoAR $soaR, SOR $soR, SettingRepository $sR): \Yiisoft\DataResponse\DataResponse
    {      
        // If the language dropdown changes
        $this->session->set('_language', $currentRoute->getArgument('_language'));
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page','1');
        //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status','0');
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
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage($page)
        ->withNextPageToken((string) $page);   
        /**
         * @var array $so_statuses
         */
        $so_statuses = $soR->getStatuses($sR);
        /**
         *  @var array $so_statuses[$status]
         *  @var string $so_label 
         */
        $so_label = $so_statuses[$status]['label'];
        $parameters = [
            'page' => $page,
            'status' => $status,
            'paginator' => $paginator,
            'sortOrder' => $query_params['sort'] ?? '',
            'alert'=>$this->alert(),
            'client_count'=> $clientRepo->count(),
            'grid_summary'=> $sR->grid_summary($paginator, $this->translator, (int)$sR->get_setting('default_list_limit'), $this->translator->translate('invoice.salesorders'), $so_label),        
            'so_statuses'=> $so_statuses,
            'max'=>(int)$sR->get_setting('default_list_limit'),
            'soaR'=>$soaR
        ]; 
        return $this->viewRenderer->render('/invoice/salesorder/index', $parameters);
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                       FormHydrator $formHydrator,
                       SettingRepository $settingRepository,                        

    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['salesorder/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=> $settingRepository,
            'head'=> $head,
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new SalesOrderForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                /** @psalm-suppress PossiblyNullArgument $this->user_service->getUser() */
                $this->salesorderService->addSo($this->userService->getUser(), new SalesOrder(),$form);
                return $this->webService->getRedirectResponse('salesorder/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @see SalesOrderRepository getStatuses function
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @return Response
     */
    public function agree_to_terms(CurrentRoute $currentRoute, SOR $soR) : Response {
        $url_key = $currentRoute->getArgument('url_key');
        if (null!==$url_key) {
            if ($soR->repoUrl_key_guest_count($url_key) > 0) { 
                $so = $soR->repoUrl_key_guest_loaded($url_key);
                if ($so) {
                    $so_id = $so->getId(); 
                    $so->setStatus_id(3);
                    $soR->save($so);
                    /**
                     * @var array $so_statuses
                     */
                    $so_statuses = $soR->getStatuses($this->sR);
                    /*  @var string $status_id */
                    $status_id = $so->getStatus_id();
                    /**
                     *  @var array $so_statuses[$status_id]
                     *  @var string $so_label 
                     */
                    $so_label = $so_statuses[$status_id]['label'];
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/salesorder_successful',
                    ['heading'=>$so_label,'message'=>$this->sR->trans('record_successfully_updated'),'url'=>'salesorder/view','id'=>$so_id]));  
                }
                return $this->webService->getNotFoundResponse();
            }
            return $this->webService->getNotFoundResponse();
        }
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * @see SalesOrderRepository getStatuses function 
     * @param CurrentRoute $currentRoute
     * @param SOR $soR
     * @return Response
     */
    public function reject(CurrentRoute $currentRoute, SOR $soR) : Response {
        $url_key = $currentRoute->getArgument('url_key');
        if (null!==$url_key) {
            if ($soR->repoUrl_key_guest_count($url_key) > 0) { 
                $so = $soR->repoUrl_key_guest_loaded($url_key);
                if ($so) {
                    $so_id = $so->getId();
                    // see SalesOrderRepository getStatuses function 
                    $so->setStatus_id(9);
                    $soR->save($so);
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/salesorder_successful',
                    ['heading'=>$soR->getSpecificStatusArrayLabel((string)9), 'message'=>$this->sR->trans('record_successfully_updated'),'url'=>'salesorder/view','id'=>$so_id]));  
                }
                return $this->webService->getNotFoundResponse();
            }
            return $this->webService->getNotFoundResponse();
        }
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * @param SalesOrderRepository $soRepo
     * @param int $status
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, SalesOrder>
     */
    private function salesorders_status_with_sort(SalesOrderRepository $soRepo, int $status, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $pos = $soRepo->findAllWithStatus($status)
                      ->withSort($sort);
        return $pos;
    }
    
    /**
     * @param SOR $soR
     * @param int $status
     * @param array $user_clients
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, SalesOrder>
     */
    private function salesorders_status_with_sort_guest(SOR $soR, int $status,  array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $salesorders = $soR->repoGuestStatuses($status, $user_clients)
                     ->withSort($sort);
        return $salesorders;
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param SalesOrderRepository $salesorderRepository
     * @param CR $clientRepo
     * @param CFR $cfR
     * @param CVR $cvR
     * @param DR $delRepo
     * @param GR $gR
     * @param InvRepo $invRepo
     * @param SoR $soR
     * @param SoCR $socR
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                         FormHydrator $formHydrator,
                         SalesOrderRepository $salesorderRepository, 
                         CR $clientRepo,
                         CFR $cfR,
                         CVR $cvR,
                         DR $delRepo,
                         GR $gR,
                         InvRepo $invRepo,
                         SoR $soR,
                         SoCR $socR,
                         SettingRepository $settingRepository

    ): Response {
        $so = $this->salesorder($currentRoute, $salesorderRepository);
        if ($so){
            $dels = $delRepo->repoClientquery($so->getClient_id());            
            $so_id = $so->getId();
            $inv_id = $so->getInv_id();
            /** 
             * @var Inv $inv 
             */
            if (null!==$inv_id) {
                $inv = $invRepo->repoInvUnloadedquery($inv_id);
            }
            $inv_number = null!==$inv_id &&null!==$inv ? (string)$inv->getNumber() : ''; 
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['salesorder/edit', ['id' => $so->getId()]],
                'clients'=>$clientRepo->findAllPreloaded(),
                'groups'=>$gR->findAllPreloaded(),
                'errors' => [],
                'body' => $this->body($so),
                'head' => $head,
                'inv_number' => $inv_number,
                'del_count' => $delRepo->repoClientCount($so->getClient_id()),
                'dels' => $dels,
                'terms_and_conditions_file'=>$this->viewRenderer->renderPartialAsString('/invoice/salesorder/terms_and_conditions_file'),
                'terms_and_conditions'=>$settingRepository->getTermsAndConditions(),
                'custom_fields'=>$cfR->repoTablequery('salesorder_custom'),
                'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('salesorder_custom')),
                'no_delivery_locations' => $delRepo->repoClientCount($so->getClient_id()) > 0 ? '' : $this->flash_message('warning', $this->translator->translate('invoice.quote.delivery.location.none')),
                'alert'=>$this->alert(),
                'so'=> $so,
                'so_custom_values' => null!==$so_id ? $this->salesorder_custom_values($so_id, $socR) : null,
                'so_statuses'=> $soR->getStatuses($settingRepository),            
                's'=>$settingRepository,
            ];
            $body = $request->getParsedBody();
            if ($request->getMethod() === Method::POST) {
                $form = new SalesOrderForm();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->salesorderService->saveSo($so, $form, $settingRepository, $gR);
                    return $this->webService->getRedirectResponse('salesorder/index');
                }
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
                $parameters['body'] = $body;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('salesorder/index');
    }
            
    /**
     * 
     * @param SettingRepository $settingRepository
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
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,SalesOrderRepository $salesorderRepository, 
                           SoCR $socR, 
                           SoCS $socS, 
                           SoIR $soiR, 
                           SoIS $soiS, 
                           SoTRR $sotrR, 
                           SoTRS $sotrS, 
                           SoAR $soaR, SoAS $soaS) : Response {
        try {
            $so = $this->salesorder($currentRoute, $salesorderRepository);
            if ($so) {
                $this->salesorderService->deleteSo($so, $socR, $socS, $soiR, $soiS, $sotrR, $sotrS, $soaR, $soaS);               
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('salesorder/index'); 
            }
            return $this->webService->getRedirectResponse('salesorder/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('salesorder/index'); 
        }
    }
    
    /**
     * 
     * @param string $so_id
     * @param SoCR $salesorder_customR
     * @return array
     */
    public function salesorder_custom_values(string $so_id, SoCR $salesorder_customR) : array
    {
        // Get all the custom fields that have been registered with this quote on creation, retrieve existing values via repo, and populate 
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
    
    public function pdf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, SOAR $soaR, SOCR $socR, SOIR $soiR, SOIAR $soiaR, SOR $soR, SOTRR $sotrR, SettingRepository $sR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse|Response {
        // include is a value of 0 or 1 passed from quote.js function quote_to_pdf_with(out)_custom_fields indicating whether the user
        // wants custom fields included on the quote or not.
        $include = $currentRoute->getArgument('include');        
        $so_id = (string)$this->session->get('so_id');
        $salesorder_amount = (($soaR->repoSalesOrderAmountCount($so_id) > 0) ? $soaR->repoSalesOrderquery($so_id) : null);
        if ($salesorder_amount) {
            $custom = (($include===(string)1) ? true : false);
            $salesorder_custom_values = $this->salesorder_custom_values((string)$this->session->get('so_id'),$socR);
            // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
            $pdfhelper = new PdfHelper($sR, $this->session);
            // The salesorder will be streamed ie. shown, and not archived
            $stream = true;
            $so = $soR->repoSalesOrderUnloadedquery($so_id);        
            if ($so) {
                $pdfhelper->generate_salesorder_pdf($so_id, $so->getUser_id(), $stream, $custom, $salesorder_amount, $salesorder_custom_values, $cR, $cvR, $cfR, $soiR, $soiaR, $soR, $sotrR, $uiR, $this->viewRenderer, $this->translator);        
                $parameters = ($include == '1' ? ['success'=>1] : ['success'=>0]);
                return $this->factory->createResponse(Json::encode($parameters));  
            } // $inv
            return $this->factory->createResponse(Json::encode(['success'=>0]));  
        } // quote_amount 
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * 
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
    public function view(CurrentRoute $currentRoute, SettingRepository $settingRepository, PR $pR, CFR $cfR, CVR $cvR, GR $gR, SoAR $soaR, SoIAR $soiaR, SoIR $soiR, SoR $soR, SoTRR $sotrR, TRR $trR, UNR $uR, SoCR $socR, InvRepo $invRepo
        ): Response {
        $so = $this->salesorder($currentRoute, $soR);
        if ($so) {
            $this->session->set('so_id', $so->getId());
            $salesorder_custom_values = $this->salesorder_custom_values((string)$this->session->get('so_id'), $socR);
            $so_tax_rates = (($sotrR->repoCount((string)$this->session->get('so_id')) > 0) ? $sotrR->repoSalesOrderquery((string)$this->session->get('so_id')) : null);            
            $inv_id = $so->getInv_id();
            if (null!==$inv_id) {
                $inv = $invRepo->repoInvUnloadedquery($inv_id);
                $inv_number = ($inv ? $inv->getNumber() : '');
            } else { $inv_number = '';}
            if ($so_tax_rates) {
                $so_amount = (($soaR->repoSalesOrderAmountCount((string)$this->session->get('so_id')) > 0) ? $soaR->repoSalesOrderquery((string)$this->session->get('so_id')) : null);
                if ($so_amount) {
                    $parameters = [
                        'alert'=>$this->alert(),
                        'title' => $settingRepository->trans('view'),
                        'invEdit' => $this->userService->hasPermission('editInv') ? true : false,
                        'action' => ['salesorder/view', ['id' => $so->getId()]],
                        'errors' => [],
                        'body' => $this->body($so),
                        'so'=>$so,
                        'soR'=>$soR,
                        'inv_number'=>$inv_number,
                        // Get all the fields that have been setup for this SPECIFIC salesorder in salesorder_custom. 
                        'fields' => $socR->repoFields((string)$this->session->get('quote_id')),
                        // Get the standard extra custom fields built for EVERY quote. 
                        'custom_fields'=>$cfR->repoTablequery('salesorder_custom'),
                        'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('salesorder_custom')),
                        'cvH'=> new CVH($settingRepository),
                        'terms_and_conditions' => $settingRepository->getTermsAndConditions(),
                        'so_statuses'=> $soR->getStatuses($settingRepository),  
                        'salesorder_custom_values' => $salesorder_custom_values,
                        'partial_item_table'=>$this->viewRenderer->renderPartialAsString('/invoice/salesorder/partial_item_table',[
                            'invEdit' => $this->userService->hasPermission('editInv') ? true : false,    
                            'invView' => $this->userService->hasPermission('viewInv') ? true : false,
                            'numberhelper'=> new NumberHelper($settingRepository),          
                            'products'=> $pR->findAllPreloaded(),
                            'so_items'=> $soiR->repoSalesOrderquery((string)$this->session->get('so_id')),
                            'so_item_amount'=> $soiaR,
                            'so_tax_rates'=> $so_tax_rates,
                            'so_amount'=> $so_amount,
                            'so'=> $soR->repoSalesOrderLoadedquery((string)$this->session->get('so_id')),  
                            's'=> $settingRepository,
                            'tax_rates'=> $trR->findAllPreloaded(),
                            'units'=> $uR->findAllPreloaded(),
                        ]),
                        'modal_salesorder_to_pdf'=>$this->viewRenderer->renderPartialAsString('/invoice/salesorder/modal_salesorder_to_pdf',[
                            's'=>$settingRepository,
                            'so'=> $so,                        
                        ]),
                        'modal_so_to_invoice'=>$this->viewRenderer->renderPartialAsString('/invoice/salesorder/modal_so_to_invoice',[
                                 's'=>$settingRepository,
                                 'so'=> $so,                        
                                 'groups'=>$gR->findAllPreloaded(),
                        ]),
                        'view_custom_fields'=>$this->viewRenderer->renderPartialAsString('/invoice/salesorder/view_custom_fields', [
                            'custom_fields'=>$cfR->repoTablequery('salesorder_custom'),
                            'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('salesorder_custom')),
                            'salesorder_custom_values'=> $salesorder_custom_values,  
                            'cvH'=> new CVH($settingRepository),
                            's'=>$settingRepository,   
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
    private function salesorder(CurrentRoute $currentRoute,SalesOrderRepository $salesorderRepository) : SalesOrder|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $salesorder = $salesorderRepository->repoSalesOrderLoadedquery($id);
            return $salesorder;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function salesorders(SalesOrderRepository $salesorderRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $salesorders = $salesorderRepository->findAllPreloaded();        
        return $salesorders;
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
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function so_to_invoice_confirm(Request $request, FormHydrator $formHydrator, CFR $cfR, 
                                             GR $gR, IIAR $iiaR, IIAS $iiaS, PR $pR, SOAR $soaR, SOCR $socR,
                                             SOIR $soiR, SOR $soR, SOTRR $sotrR, TRR $trR, UNR $unR, SettingRepository $sR, UR $uR, UCR $ucR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse|Response
    {
        $body = $request->getQueryParams();
        $so_id = (string)$body['so_id'];
        $so = $soR->repoSalesOrderUnloadedquery($so_id);
        if ($so) {
            $inv_body = [
                'client_id'=>$body['client_id'],
                'group_id'=>$body['group_id'],
                'quote_id'=> $so->getQuote_id(),
                'so_id'=> $so->getId(),
                'status_id'=>2,
                'password'=>$body['password'] ?? '',
                'number'=>$gR->generate_number((int)$body['group_id']),
                'discount_amount'=>floatval($so->getDiscount_amount()),
                'discount_percent'=>floatval($so->getDiscount_percent()),
                'url_key'=>$so->getUrl_key(),
                'payment_method'=>0,
                'terms'=>'',
                'creditinvoice_parent_id'=>''
            ];
            $form = new InvForm();
            $inv = new Inv();
            if (($formHydrator->populate($form, $inv_body) && $form->isValid()) &&
                  // Salesorder has not been copied before:  inv_id = 0
                  (($so->getInv_id()===(string)0))
              ) {
              /**
               * @var string $inv_body['client_id']
               */
              $client_id = $inv_body['client_id'];
              $user_client = $ucR->repoUserquery($client_id);
              $user_client_count = $ucR->repoUserquerycount($client_id);
              if (null!==$user_client && $user_client_count==1) {
                // Only one user account per client
                $user_id = $user_client->getUser_id();
                $user = $uR->findById($user_id);
                if (null!==$user) {
                  $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                  if (null!==$user_inv && $user_inv->getActive()) { 
                    $this->invService->bothInv($user, $inv, $form, $sR, $gR);
                    $inv_id = $inv->getId();
                    if (null!==$inv_id) {
                      // Transfer each so_item to inv_item and the corresponding so_item_amount to inv_item_amount for each item
                      $this->so_to_invoice_so_items($so_id,$inv_id,$iiaR,$iiaS,$pR,$soiR,$trR,$formHydrator, $sR, $unR);
                      $this->so_to_invoice_so_tax_rates($so_id,$inv_id,$sotrR,$formHydrator);
                      $this->so_to_invoice_so_custom($so_id,$inv_id,$socR,$cfR,$formHydrator);
                      $this->so_to_invoice_so_amount($so_id,$inv_id,$soaR,$formHydrator);
                      // Update the sos inv_id.
                      $so->setInv_id($inv_id);
                      // Set salesorder's status to invoice generated
                      $so->setStatus_id(8);
                      $this->flash_message('info', $this->translator->translate('invoice.salesorder.invoice.generated'));
                      $soR->save($so);
                      $parameters = ['success'=>1];
                      //return response to salesorder.js to reload page at location
                      $this->flash_message('info',$this->translator->translate('invoice.salesorder.copied.to.invoice'));
                      return $this->factory->createResponse(Json::encode($parameters));          
                    } // null!==$inv_id
                  } // null!==$user_inv && $user_inv->getActive()
                } // null!==$user
              }  
            } else {
                $parameters = [
                   'success'=>0,
                ];
                //return response to salesorder.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));          
            }
        } // so
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * 
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
     * @return void
     */
    private function so_to_invoice_so_items(string $so_id, string $inv_id, IIAR $iiaR, IIAS $iiaS, PR $pR, SOIR $soiR, TRR $trR, FormHydrator $formHydrator, SettingRepository $sR, UNR $unR): void {
        // Get all items that belong to the salesorder
        $items = $soiR->repoSalesOrderItemIdquery($so_id);
        /** @var SalesOrderItem $so_item */
        foreach ($items as $so_item) {
            $inv_item = [
                'inv_id'=>$inv_id,
                'so_item_id'=>$so_item->getId(),
                'tax_rate_id'=>$so_item->getTax_rate_id(),
                'product_id'=>$so_item->getProduct_id(),
                'task_id'=>'',
                'name'=>$so_item->getName(),
                'description'=>$so_item->getDescription(),
                'quantity'=>$so_item->getQuantity(),
                'price'=>$so_item->getPrice(),
                'discount_amount'=>$so_item->getDiscount_amount(),
                'charge_amount'=>$so_item->getCharge_amount(),
                'order'=>$so_item->getOrder(),
                'is_recurring'=>0,
                'product_unit'=>$so_item->getProduct_unit(),
                'product_unit_id'=>$so_item->getProduct_unit_id(),
                // Recurring date
                'date'=>''
            ];
            // Create an equivalent invoice item for the so item
            $invitem = new InvItem();
            $form = new InvItemForm();
            if ($formHydrator->populate($form, $inv_item) && $form->isValid()) {
                $this->invItemService->addInvItem_product($invitem, $form, $inv_id, $pR, $trR, $iiaS, $iiaR, $sR, $unR);
            }
        } // items
    }
    
    /**
     * @param string $so_id
     * @param string|null $inv_id
     * @param SOTRR $sotrR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function so_to_invoice_so_tax_rates(string $so_id, string|null $inv_id, SOTRR $sotrR, FormHydrator $formHydrator): void {
        // Get all tax rates that have been setup for the salesorder
        $so_tax_rates = $sotrR->repoSalesOrderquery($so_id);        
        /** @var SalesOrderTaxRate $so_tax_rate */
        foreach ($so_tax_rates as $so_tax_rate){ 
            $inv_tax_rate = [
                'inv_id'=>(string)$inv_id,
                'tax_rate_id'=>$so_tax_rate->getTax_rate_id(),
                'include_item_tax'=>$so_tax_rate->getInclude_item_tax(),
                'inv_tax_rate_amount'=>$so_tax_rate->getSo_tax_rate_amount(),
            ];
            $entity = new InvTaxRate();
            $form = new InvTaxRateForm();
            if ($formHydrator->populate($form, $inv_tax_rate) && $form->isValid()
            ) {    
               $this->invTaxRateService->saveInvTaxRate($entity,$form);
            }
        } // foreach        
    }
    
    /**
     * 
     * @param string $so_id
     * @param string|null $inv_id
     * @param SOCR $socR
     * @param CFR $cfR
     * @param FormHydrator $formHydrator
     * @return void
     * 
     */
    private function so_to_invoice_so_custom(string $so_id, string|null $inv_id, 
                                                   SOCR $socR,                                                     
                                                   CFR $cfR, 
                                                   FormHydrator $formHydrator) : void {
        $so_customs = $socR->repoFields($so_id);
        // For each salesorder custom field, build a new custom field for 'inv_custom' using the custom_field_id to find details
        /** @var SalesOrderCustom $so_custom */
        foreach ($so_customs as $so_custom) {
            // For each so custom field, build a new custom field for 'inv_custom' 
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery($so_custom->getCustom_field_id());
            if ($cfR->repoTableAndLabelCountquery('inv_custom',(string)$existing_custom_field->getLabel()) !== 0) {
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
                  'inv_id'=>$inv_id,
                  'custom_field_id'=>$custom_field->getId(),
                  'value'=>$so_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm();
                if ($formHydrator->populate($form, $inv_custom) && $form->isValid()) {    
                    $this->inv_custom_service->saveInvCustom($entity,$form);            
                }
            } // existing_custom_field    
        } // foreach        
    }
    
    /**
     * @param string $so_id
     * @param string|null $inv_id
     * @param SOAR $soaR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function so_to_invoice_so_amount(string $so_id, string|null $inv_id, SOAR $soaR, FormHydrator $formHydrator) : void {
        $so_amount = $soaR->repoSalesOrderquery($so_id);
        $inv_amount = [];
        if ($so_amount) {
            $inv_amount = [
                'inv_id'=>$inv_id,
                'sign'=>1,
                'item_subtotal'=>$so_amount->getItem_subtotal(),
                'item_tax_total'=>$so_amount->getItem_tax_total(),
                'tax_total'=>$so_amount->getTax_total(),
                'total'=>$so_amount->getTotal(),
                'paid'=>floatval(0.00),
                'balance'=>$so_amount->getTotal(),
            ];
        }    
        $entity = new InvAmount();
        $form = new InvAmountForm();
        if ($formHydrator->populate($form, $inv_amount) && $form->isValid()) {    
          $this->invAmountService->saveInvAmount($entity, $form);            
        }
    }
    
    /**
     * @param SalesOrder $salesorder
     * @return array
     */
    private function body(SalesOrder $salesorder) : array {
        $body = [                
          'date_created'=>$salesorder->getDate_created(),
          'date_modified'=>$salesorder->getDate_modified(),
          'id'=>$salesorder->getId(),
          'quote_id'=>$salesorder->getQuote_id(),
          'inv_id'=>$salesorder->getInv_id(),
          'user_id'=>$salesorder->getUser_id(),
          'client_id'=>$salesorder->getClient_id(),
          'group_id'=>$salesorder->getGroup_id(),
          'status_id'=>$salesorder->getStatus_id(),
          'client_po_number'=>$salesorder->getClient_po_number(),
          'client_po_line_number'=>$salesorder->getClient_po_line_number(),
          'client_po_person'=>$salesorder->getClient_po_person(),
          'date_expires'=>$salesorder->getDate_expires(),
          'number'=>$salesorder->getNumber(),
          'discount_amount'=>$salesorder->getDiscount_amount(),
          'discount_percent'=>$salesorder->getDiscount_percent(),
          'url_key'=>$salesorder->getUrl_key(),
          'password'=>$salesorder->getPassword(),
          'notes'=>$salesorder->getNotes(),
          'payment_term'=>$salesorder->getPaymentTerm(),                ];
        return $body;
    }
    
    /**
     * 
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
    public function url_key(CurrentRoute $currentRoute, CurrentUser $currentUser, CFR $cfR, SOAR $soaR, SOIR $soiR, SOIAR $soiaR, SOR $soR, SOTRR $sotrR, UIR $uiR, UCR $ucR): Response 
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
            if ($salesorder_id) {
                if ($sotrR->repoCount($salesorder_id) > 0)  {
                    $salesorder_tax_rates = $sotrR->repoSalesOrderquery($salesorder_id);
                }    
            }    
            if (in_array($salesorder->getStatus_id(),[2,3,4,5,6,7,8,9,10])) { 
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
                            //TODO 
                            // $attachments;
                            if ($salesorder_id) {
                                $salesorder_amount = (($soaR->repoSalesOrderAmountCount($salesorder_id) > 0) ? $soaR->repoSalesOrderquery($salesorder_id) : null);
                                if ($salesorder_amount) {
                                    $parameters = [            
                                        'render'=> $this->viewRenderer->renderPartialAsString('/invoice/template/salesorder/public/' . ($this->sR->get_setting('public_salesorder_template') ?: 'SalesOrder_Web'), [
                                            'isGuest' => $currentUser->isGuest(),
                                            'terms_and_conditions_file'=>$this->viewRenderer->renderPartialAsString('/invoice/salesorder/terms_and_conditions_file'),
                                            // TODO logo
                                            'logo'=> '',
                                            'alert'=>$this->alert(),
                                            'salesorder' => $salesorder,
                                            'salesorder_item_amount'=>$soiaR,
                                            'salesorder_amount' => $salesorder_amount,
                                            'items' => $soiR->repoSalesOrderquery($salesorder_id),
                                            // Get all the salesorder tax rates that have been setup for this salesorder
                                            'salesorder_tax_rates' => $salesorder_tax_rates,
                                            'salesorder_url_key' => $url_key,
                                            'flash_message' => $this->flash_message('info', ''),
                                            //'attachments' => $attachments,
                                            'custom_fields' => $custom_fields,
                                            'clienthelper' => new ClientHelper($this->sR),
                                            'datehelper' => new DateHelper($this->sR),
                                            'numberhelper' => new NumberHelper($this->sR),
                                            's'=>$this->sR,
                                            'client'=>$salesorder->getClient(),
                                            // Get the details of the user of this quote
                                            'userinv'=> $uiR->repoUserInvUserIdcount($salesorder->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($salesorder->getUser_id()) : null,
                                            
                                        ]),        
                                    ];        
                                    return $this->viewRenderer->render('/invoice/salesorder/url_key', $parameters);
                                } // if salesorder_amount    
                                return $this->webService->getNotFoundResponse(); 
                            } // if there is a salesorder id 
                            return $this->webService->getNotFoundResponse(); 
                        } // user_inv->getType
                        return $this->webService->getNotFoundResponse(); 
                    } // user_inv
                    return $this->webService->getNotFoundResponse(); 
                } // $uiR    
                return $this->webService->getNotFoundResponse(); 
            } // if in_array
            return $this->webService->getNotFoundResponse(); 
        } // if salesorder
        return $this->webService->getNotFoundResponse(); 
    }
    
  /**
   * @return string
   */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
     [ 
       'flash' => $this->flash,
       'errors' => [],
     ]);
   }

    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }
}

