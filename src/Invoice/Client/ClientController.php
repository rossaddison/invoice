<?php
declare(strict_types=1);

namespace App\Invoice\Client;
// Entity's
use App\Invoice\Entity\Client;
use App\Invoice\Entity\ClientNote;
use App\Invoice\Entity\ClientCustom;
use App\Invoice\Entity\Inv;
use App\Invoice\Inv\InvForm;
use App\Invoice\Entity\PostalAddress;
use App\Invoice\Entity\Quote;
// Services
use App\Service\WebControllerService;
use App\Invoice\ClientCustom\ClientCustomService;
// Forms
use App\Invoice\Client\ClientForm;
use App\Invoice\ClientCustom\ClientCustomForm;
use App\Invoice\ClientNote\ClientNoteService as cnS;
use App\Invoice\ClientNote\ClientNoteForm;
use App\Invoice\Quote\QuoteForm;
use App\Invoice\UserClient\UserClientService;
use App\Invoice\UserClient\Exception\NoClientsAssignedToUserException;
use App\User\UserService;
// Repositories
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\ClientCustom\ClientCustomRepository as ccR;
use App\Invoice\ClientNote\ClientNoteRepository as cnR;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as delR;
use App\Invoice\Group\GroupRepository as gR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvRecurring\InvRecurringRepository as irR;
use App\Invoice\Payment\PaymentRepository as pymtR;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as qaR;
use App\Invoice\Quote\QuoteRepository as qR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UserClient\UserClientRepository as ucR;
// Helpers
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\GenerateCodeFileHelper;
// Psr\\Http
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Widgets
use App\Widget\Bootstrap5ModalQuote;
use App\Widget\Bootstrap5ModalInv; 
// Yii
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator as DataOffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\Validator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Yii\View\ViewRenderer;
// Miscellaneous

final class ClientController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private ClientService $clientService;
    private ClientCustomService $clientCustomService;
    private UserService $userService;
    private UserClientService $userclientService;     
    private CurrentUser $currentUser;
    private DataResponseFactoryInterface $factory;
    private Flash $flash;
    private SessionInterface $session;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        ClientService $clientService,
        ClientCustomService $clientCustomService,
        UserService $userService,
        UserClientService $userclientService,
        CurrentUser $currentUser,
        DataResponseFactoryInterface $factory,
        SessionInterface $session,
        TranslatorInterface $translator
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/client')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->clientService = $clientService;
        $this->clientCustomService = $clientCustomService;
        $this->userclientService = $userclientService;
        $this->currentUser = $currentUser;
        $this->factory = $factory;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer;
        $this->userService = $userService;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/client')
                                               ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/client')
                                               ->withLayout('@views/layout/invoice.php');
        }
        $this->translator = $translator;
    }
    
    /**
     * @return string
     */
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash
        ]);
    }
    
    /**
     * 
     * @param string $generated_dir_path
     * @param string $content
     * @param string $file
     * @param string $name
     * @return GenerateCodeFileHelper
     */
    private function build_and_save(string $generated_dir_path, string $content, string $file,string $name): GenerateCodeFileHelper {
        $build_file = new GenerateCodeFileHelper("$generated_dir_path/$name$file", $content); 
        $build_file->save();
        return $build_file;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param cR $cR
     * @return Client|null
     */
    private function client(CurrentRoute $currentRoute,cR $cR): Client|null {
        $client_id = $currentRoute->getArgument('id');
        if (null!==$client_id) {
            $client = $cR->repoClientquery($client_id);
            return $client;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function clients(cR $cR, int $active): \Yiisoft\Data\Cycle\Reader\EntityReader {
        $clients = $cR->findAllWithActive($active); 
        return $clients;
    }
    
    /**
     * 
     * @param string $client_id
     * @param ccR $ccR
     * @return array
     */
    public function client_custom_values(string $client_id, ccR $ccR) : array
    {
        // Get all the custom fields that have been registered with this client on creation, retrieve existing values via repo, and populate 
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($ccR->repoClientCount($client_id) > 0) {
            $client_custom_fields = $ccR->repoFields($client_id);
            /**
             * @var int $key
             * @var string $val
             */
            foreach ($client_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param cfR $cfR
     * @param cvR $cvR
     * @param sR $sR
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,    
        Request $request, 
        FormHydrator $formHydrator, 
        cfR $cfR, 
        cvR $cvR, 
        sR $sR    
    ) : Response 
    {
        $origin = $currentRoute->getArgument('origin');
        $countries = new CountryHelper();
        $new_client = New Client();
        $form = new ClientForm($new_client);
        $clientCustom = new ClientCustom();
        $clientCustomForm = new ClientCustomForm($clientCustom);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'alert' => $this->alert(),
            'action' => ['client/add', ['origin' => $origin]],
            'origin' => $origin,
            'errors' => [],
            'errors_custom' => [],
            'client' => $new_client,
            'aliases' => new Aliases(['@invoice' => dirname(__DIR__), '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR.'Language']),
            'selected_country' => $sR->get_setting('default_country'),            
            'selected_language' => $sR->get_setting('default_language'),
            'datepicker_dropdown_locale_cldr' => $this->session->get('_language') ?? 'en',
            'optionsDataGender' => $this->optionsDataGender(),
            'optionsDataClientFrequencyDropdownFilter' => $this->optionsDataClientFrequencyDropDownFilter(),
            'postal_address_count' => 0,
            'postaladdresses' => null,
            'countries' => $countries->get_country_list($sR->get_setting('cldr')),
            'custom_fields' => $cfR->repoTablequery('client_custom'),
            'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('client_custom')),
            'cvH' => new CVH($sR),
            'client_custom_values' => [],
            'clientCustomForm' => $clientCustomForm,
        ];
        if ($request->getMethod() === Method::POST) { 
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $client_id = $this->clientService->saveClient($new_client, $body, $sR);
                if (null!==$client_id) {
                    if (isset($body['custom'])) {
                        // Retrieve the custom array
                        /** @var array $custom */
                        $custom = $body['custom'];
                        /** 
                         * @var int $custom_field_id
                         * @var string|array $value
                         */
                        foreach($custom as $custom_field_id => $value){
                            $clientCustom = new ClientCustom();
                            $formClientCustom = new ClientCustomForm($clientCustom);
                            $client_custom = [];
                            $client_custom['client_id'] = $client_id;
                            $client_custom['custom_field_id'] = $custom_field_id;                    
                            // Note: There are no Required rules for value under ClientCustomForm
                            $client_custom['value'] = is_array($value) ? serialize($value) : $value;                    
                            if ($formHydrator->populate($formClientCustom, $client_custom) && $formClientCustom->isValid()) {
                              $this->clientCustomService->saveClientCustom($clientCustom, $client_custom);
                            }
                            // These two can be used to create customised labels for custom field error validation on the form
                            // Currently not used.
                            $parameters['formClientCustom'] = $formClientCustom; 
                            $parameters['errors_custom'] = $formClientCustom->getValidationResult()->getErrorMessagesIndexedByAttribute();
                        }
                    }    
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                    if ($origin == 'main' || $origin == 'add') {
                        return $this->webService->getRedirectResponse('client/index');
                    }
                    if ($origin == 'dashboard') {
                        return $this->webService->getRedirectResponse('invoice/dashboard');
                    }   
                }
            }
            else {
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            }
        }    
        $parameters['form'] = $form;
        return $this->viewRenderer->render('__form', $parameters);
    }
        
    /**
     * 
     * @param FormHydrator $formHydrator
     * @param array $body
     * @param mixed $matches
     * @param string $client_id
     * @param ccR $ccR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function custom_fields(FormHydrator $formHydrator, array $body, mixed $matches, string $client_id, ccR $ccR) : \Yiisoft\DataResponse\DataResponse
    {   
        if (!empty($body['custom'])) {
            $db_array = [];
            $values = [];
            /**
             * @var array $custom 
             * @var string $custom['name']
             */
            foreach ($body['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                     /**
                     * @var string $custom['value']
                     */
                    $values[$matches[1]][] = $custom['value'];
                } else {
                     /**
                     * @var string $custom['value']
                     */
                    $values[$custom['name']] = $custom['value'];
                }
            }
            
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                     /**
                     * @var string $value
                     */
                    $db_array[$matches[1]] = $value;
                }
            }
            
            foreach ($db_array as $key => $value){                
                $client_custom = [];
                $client_custom['client_id']=$client_id;
                $client_custom['custom_field_id']=$key;
                $client_custom['value']=$value; 
                $model = ($ccR->repoClientCustomCount($client_id,$key) == 1 ? $ccR->repoFormValuequery($client_id,$key) : new ClientCustom());
                if ($model instanceof ClientCustom) {
                   $form = new ClientCustomForm($model); 
                   if ($formHydrator->populate($form, $client_custom) && $form->isValid()) { 
                        $this->clientCustomService->saveClientCustom($model, $client_custom);
                   }     
                }
            }
            $parameters = [
                'success'=>1,
            ];
            return $this->factory->createResponse(Json::encode($parameters)); 
        } else {
            $parameters = [
                'success'=>0,
            ];           
            return $this->factory->createResponse(Json::encode($parameters)); 
        }
    }  
    
    public function delete(CurrentRoute $currentRoute,cR $cR, sR $sR
    ): Response {
        try {
            $this->clientService->deleteClient($this->client($currentRoute, $cR)); 
             $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
            //UserClient Entity automatically deletes the UserClient record relevant to this client 
            return $this->webService->getRedirectResponse('client/index');
	} catch (\Exception $e) {
              unset($e);
              $this->flash_message('danger', $this->translator->translate('invoice.client.delete.history.exits.no'));
              return $this->webService->getRedirectResponse('client/index');
        }
    } 
    
    public function edit(Request $request, 
                         cR $cR, 
                         ccR $ccR, 
                         cfR $cfR, 
                         cvR $cvR, 
                         FormHydrator $formHydrator, 
                         paR $paR, 
                         sR $sR, 
                         CurrentRoute $currentRoute, Validator $validator
    ): Response {
    $origin = $currentRoute->getArgument('origin');    
    $client = null!==$this->client($currentRoute, $cR) ? $this->client($currentRoute, $cR) : null;
    if (null!==$client) {
        $form = new ClientForm($client);
        $clientCustom = new ClientCustom();
        $clientCustomForm = new ClientCustomForm($clientCustom);
        $selected_country =  $client->getClient_country(); 
        $selected_language = $client->getClient_language();
        $countries = new CountryHelper();
        $client_id = $client->getClient_id();
        if (null!==$client_id) {
            $postaladdresses = $paR->repoClientAll((string)$client_id); 
            $parameters = [
               'title' => $this->translator->translate('i.edit'),
               'action' => ['client/edit', ['id' => $client_id, 'origin' => $origin]],
               'alert' => $this->alert(),
               'errors' => [],
               'origin' => $origin,
               'errors_custom' => [], 
               'buttons' => $this->viewRenderer->renderPartialAsString('/invoice/layout/header_buttons',['s'=>$sR, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]), 
               'datehelper' => new DateHelper($sR),
               'client' => $client,
               'form' => $form,
               'optionsDataGender' => $this->optionsDataGender(),
               'optionsDataClientFrequencyDropdownFilter' => $this->optionsDataClientFrequencyDropDownFilter(),
               'aliases' => new Aliases(['@invoice' => dirname(__DIR__), '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR.'Language']),
               'selected_country' => null!==$selected_country ? $selected_country : $sR->get_setting('default_country'),            
               'selected_language' => null!==$selected_language ? $selected_language : $sR->get_setting('default_language'),
               'datepicker_dropdown_locale_cldr' => $this->session->get('_language') ?? 'en',
               'postal_address_count' => $paR->repoClientCount((string)$client_id),
               'postaladdresses' => $this->optionsDataPostalAddress($postaladdresses),
               'countries' => $countries->get_country_list($sR->get_setting('cldr')),
               'custom_fields' => $cfR->repoTablequery('client_custom'),
               'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('client_custom')),
               'cvH' => new CVH($sR),
               'client_custom_values' => $this->client_custom_values((string)$client_id, $ccR),
               'clientCustomForm' => $clientCustomForm 
            ];
            if ($request->getMethod() === Method::POST) {
               $body = $request->getParsedBody() ?? []; 
               if (is_array($body)) {
                   $returned_form = $this->save_form_fields($body, $form, $client, $formHydrator, $sR, $validator);
                   $parameters['body'] = $body;
                   if (!$returned_form->isValid()) {
                       $parameters['form'] = $returned_form;
                       $parameters['errors'] = $returned_form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                       return $this->viewRenderer->render('__form', $parameters);
                   } 
                   // Only save custom fields if they exist
                   if ($cfR->repoTableCountquery('client_custom') > 0) { 
                        if (isset($body['custom'])) {
                            $custom = (array)$body['custom'];
                            /** @var string|array $value */
                            foreach ($custom as $custom_field_id => $value) {
                                $client_custom = $ccR->repoFormValuequery((string)$client_id, (string)$custom_field_id);
                                if (null!==$client_custom) {
                                    $client_custom_input = [
                                        'client_id' => $client_id,
                                        'custom_field_id' => (int)$custom_field_id,
                                        'value' => is_array($value) ? serialize($value) : $value 
                                    ];
                                    $clientCustomForm = new ClientCustomForm($client_custom);
                                    if ($formHydrator->populate($clientCustomForm, $client_custom_input) 
                                       && $clientCustomForm->isValid()
                                    )
                                    {
                                        $this->clientCustomService->saveClientCustom($client_custom, $client_custom_input);     
                                    }
                                    $parameters['errors_custom'] = $clientCustomForm->getValidationResult()->getErrorMessagesIndexedByAttribute();
                                    $parameters['clientCustomForm'] = $clientCustomForm;
                                }
                            } //foreach
                            $errors_custom = $parameters['errors_custom'];
                            if (count($errors_custom) > 0) {
                                return $this->viewRenderer->render('__form', $parameters);
                            }
                        } //isset  
                   } // cfR
               } // is_array    
               $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
               if ($origin  == 'edit') {
                    return $this->webService->getRedirectResponse('client/index');
               }
           }
           return $this->viewRenderer->render('__form', $parameters);
        } // null!==client_id
    } //client    
    return $this->webService->getRedirectResponse('client/index');   
}
    
    /**
     * 
     * @param array $body
     * @param ClientForm $form
     * @param Client $client
     * @param FormHydrator $formHydrator
     * @param sR $sR
     * @param Validator $validator
     * @return ClientForm
     */
    public function save_form_fields(array $body, ClientForm $form, Client $client, FormHydrator $formHydrator, sR $sR, Validator $validator) : ClientForm {
        if ($formHydrator->populate($form, $body) &&  $form->isValid()) {
           $this->clientService->saveClient($client, $body, $sR);
        }
        return $form;
    }
    
    /**
     * @param string $client_id
     * @param int $custom_field_id
     * @param ccR $ccR
     * @return bool
     */
    public function add_custom_field(string $client_id, int $custom_field_id, ccR $ccR): bool
    {
        return ($ccR->repoClientCustomCount($client_id, (string)$custom_field_id) > 0 ? false : true);        
    }
    
     /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
    
    public function index(CurrentRoute $currentRoute, Request $request, cR $cR, iaR $iaR, iR $iR, sR $sR, cpR $cpR, ucR $ucR): 
        \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');        
        $active = (int)$currentRoute->getArgument('active', '2');
        /** @var string $query_params['sort'] */
        $sort_string = $query_params['sort'] ?? '-id';
        $order =  OrderHelper::stringToArray($sort_string);
        $sort = Sort::only(['id', 'client_name', 'client_surname'])
                    // (@see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest products first => -id
                    ->withOrder($order);
        $clients = $this->clients_with_sort($cR, $sort, $active);
        if (isset($query_params['filter_client_name']) && !empty($query_params['filter_client_name'])) {
            $clients = $cR->filter_client_name((string)$query_params['filter_client_name']);
        }
        if (isset($query_params['filter_client_surname']) && !empty($query_params['filter_client_surname'])) {
            $clients = $cR->filter_client_surname((string)$query_params['filter_client_surname']);
        }
        //if (isset($query_params['filter_client_balance']) && !empty($query_params['filter_client_balance'])) {
        //    $clients = $iR->filter_client_balance((string)$query_params['filter_client_balance']);
        //}
        if ((isset($query_params['filter_client_name']) && !empty($query_params['filter_client_name'])) && 
           (isset($query_params['filter_client_surname']) && !empty($query_params['filter_client_surname']))) {
            $clients = $cR->filter_client_name_surname((string)$query_params['filter_client_name'], (string)$query_params['filter_client_surname']);
        }  
        
        $paginator = (new DataOffsetPaginator($clients))
            ->withPageSize((int)$sR->get_setting('default_list_limit'))
            ->withCurrentPage((int)$page)
            ->withToken(PageToken::next((string)$page));     
        $parameters = [
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'iR' => $iR,
            'iaR' => $iaR,
            'canEdit' => $canEdit,
            'active' => $active,
            'page' => $page,
            'cpR' => $cpR,
            'ucR' => $ucR,
            'grid_summary' => $sR->grid_summary($paginator, $this->translator, (int)$sR->get_setting('default_list_limit'), $this->translator->translate('invoice.clients'), ''),
            'defaultPageSizeOffsetPaginator' => $sR->get_setting('default_list_limit')
                                                    ? (int)$sR->get_setting('default_list_limit') : 1,
            'modal_create_client' => $this->viewRenderer->renderPartialAsString('modal_create_client',[
                'datehelper' => new DateHelper($sR)
            ]),
            'optionsDataClientNameDropdownFilter' => $this->optionsDataClientNameDropdownFilter($cR),
            'optionsDataClientSurnameDropdownFilter' => $this->optionsDataClientSurnameDropdownFilter($cR),
        ];    
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param cR $cR
     * @param Sort $sort
     * @param int $active
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Client>
     */
    private function clients_with_sort(cR $cR, Sort $sort, int $active): \Yiisoft\Data\Reader\SortableDataInterface {       
        $query = $this->clients($cR, $active);
        $clients = $query->withSort($sort);
        return $clients;
    }
    
    public function guest(CurrentRoute $currentRoute, cR $cR, iaR $iaR, iR $iR, sR $sR, cpR $cpR, ucR $ucR): 
        Response
    {
        $pageNum = (int)$currentRoute->getArgument('page', '1');        
        $active = (int)$currentRoute->getArgument('active', '2');
        $user = $this->userService->getUser();
        if (null!==$user) {
            $user_id = $user->getId();
            if (null!==$user_id) {  
                $client_array = $ucR->get_assigned_to_user($user_id);
                if (!empty($client_array)) {
                    $clients = $cR->repoUserClient($client_array);
                    $paginator = (new DataOffsetPaginator($clients))
                        ->withPageSize((int)$sR->get_setting('default_list_limit'))
                        ->withCurrentPage($pageNum);
                    $parameters = [
                        'paginator' => $paginator,
                        'alert' => $this->alert(),
                        'iR' => $iR,
                        'iaR' => $iaR,
                        'editInv' => $this->userService->hasPermission('editInv'), 
                        'active' => $active,
                        'pageNum' => $pageNum,
                        'cpR' => $cpR,
                        'grid_summary' => $sR->grid_summary($paginator, $this->translator, (int)$sR->get_setting('default_list_limit'), $this->translator->translate('invoice.clients'), ''),                
                        'defaultPageSizeOffsetPaginator' => $sR->get_setting('default_list_limit')
                                                            ? (int)$sR->get_setting('default_list_limit') : 1,
                        'modal_create_client' => $this->viewRenderer->renderPartialAsString('modal_create_client',[
                            'datehelper'=> new DateHelper($sR)
                        ])
                    ];    
                    return $this->viewRenderer->render('guest', $parameters);
                } // 
                $this->flash_message('warning', $this->translator->translate('invoice.user.clients.assigned.not'));
                throw new NoClientsAssignedToUserException($this->translator);
            } // null!== $user_id
        } // null!== $this->userService
        return $this->webService->getNotFoundResponse();
    }
    
    public function load_client_notes(Request $request, cnR $cnR): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        /** @var int $body['client_id'] */
        $client_id = $body['client_id'];
        $data = $cnR->repoClientNoteCount($client_id) > 0 ? $cnR->repoClientquery((string)$client_id) : null;
        $parameters = [
            'success'=>1,
            'data'=> $data,
        ];           
        return $this->factory->createResponse(Json::encode($parameters)); 
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('client/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param FormHydrator $formHydrator
     * @param ccR $ccR
     * @param string $client_id
     * @param array $body
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function save_client_custom_fields(FormHydrator $formHydrator, ccR $ccR, string $client_id, array $body)
                    : \Yiisoft\DataResponse\DataResponse
    {  
       $custom = (array)$body['custom'];
       $custom_field_body = [            
            'custom'=>$custom,            
       ];
       if (!($custom_field_body['custom']) == []) {
            $db_array = [];
            $values = [];
             /**
             * @var array $custom 
             * @var string $custom['name']
             */
            foreach ($custom_field_body['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    /** @var string $custom['value'] */
                    $values[$matches[1]][] = $custom['value'];
                } else {
                    /** @var string $custom['value']  */
                    $values[$custom['name']] = $custom['value'];
                }
            }            
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    $db_array[$matches[1]] = $value;
                }
            }
            /**
             * @var string $value
             */
            foreach ($db_array as $key => $value){
                $client_custom = [];
                $client_custom['client_id']=$client_id;
                $client_custom['custom_field_id']=$key;
                $client_custom['value']=$value; 
                $model = ($ccR->repoClientCustomCount($client_id,$key) == 1 ? $ccR->repoFormValuequery($client_id, $key) : new ClientCustom());
                if (null!==$model) {
                    $form = new ClientCustomForm($model);
                    if ($formHydrator->populate($form, $client_custom) && $form->isValid()) {
                        $this->clientCustomService->saveClientCustom($model, $client_custom);
                    }
                }    
            }
            $parameters = [
                'success'=>1,
                'clientid'=>$client_id,
            ];
            return $this->factory->createResponse(Json::encode($parameters)); 
        } else {
            $parameters = [
                'success'=>0,
            ];           
            return $this->factory->createResponse(Json::encode($parameters)); 
        }
    }
    
    /**
     * 
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param ccR $ccR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function save_custom(FormHydrator $formHydrator, Request $request, ccR $ccR)
                    : \Yiisoft\DataResponse\DataResponse
    {
       $body = $request->getQueryParams();
       $custom = $body['custom'] ? (array)$body['custom'] : '';
       $custom_field_body = [            
            'custom'=>$custom,            
        ];      
       $client_id = (string)$this->session->get('client_id');
       if (isset($custom_field_body['custom'])) {
            $db_array = [];
            $values = [];
            /**
             * @var array $custom_field_body['custom']
             * @var array $custom 
             * @var string $custom['name']
             */
            foreach ($custom_field_body['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    /**
                     * @var string $custom['value']
                     */
                    $values[$matches[1]][] = $custom['value'];
                } else {
                    /**
                     * @var string $custom['value']
                     */
                    $values[$custom['name']] = $custom['value'];
                }
            }            
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    $db_array[$matches[1]] = $value;
                }
            }            
            foreach ($db_array as $key => $value){
                $form = new ClientCustomForm(new ClientCustom());
                $client_custom = [];
                $client_custom['client_id']=$client_id;
                $client_custom['custom_field_id']=$key;
                /**
                 * @var string $value
                 */
                $client_custom['value']=$value; 
                $model = ($ccR->repoClientCustomCount($client_id, $key) == 1 ? $ccR->repoFormValuequery($client_id, $key) : new ClientCustom());
                if (null!==$model) {
                    if ($formHydrator->populate($form, $client_custom) && $form->isValid()) {
                        $this->clientCustomService->saveClientCustom($model, $client_custom);                
                    }
                }    
            }
            $parameters = [
                'success'=>1,                
            ];
            return $this->factory->createResponse(Json::encode($parameters)); 
        } else {
            $parameters = [
                'success'=>0,
            ];           
            return $this->factory->createResponse(Json::encode($parameters)); 
        }
    }
    
    /**
     * 
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param cnS $cnS
     * @param sR $sR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function save_client_note_new(Request $request, FormHydrator $formHydrator, cnS $cnS, sR $sR) : \Yiisoft\DataResponse\DataResponse 
    {
        $datehelper = new DateHelper($sR);
        //receive data ie. note
        $body = $request->getQueryParams();
        /**
         * @var string $body['client_id']
         */
        $client_id = $body['client_id'];
        $date = new \DateTimeImmutable('now');
        /**
         * @var string $body['client_note']
         */
        $note = $body['client_note'];
        $data = [        
            'client_id'=>$client_id,
            'date'=> $date->format($datehelper->style()),
            'note'=>$note,
        ];
        $form = new ClientNoteForm(new ClientNote());        
        if ($formHydrator->populate($form, $data) && $form->isValid()) {    
            $cnS->addClientNote(new ClientNote(), $data);
            $parameters = [
                'success' => 1,
            ];
        } else {
            $parameters = [
                'success' => 0,
                'validation_errors' => $form->getValidationResult()->getErrorMessagesIndexedByAttribute()
            ];
        }        
        return $this->factory->createResponse(Json::encode($parameters));          
    }
    
    /**
     * @return array
     */
    private function optionsDataGender() : array
    {
        $optionsDataGender = [];
        $genders_array = [
            $this->translator->translate('invoice.gender.male'),
            $this->translator->translate('invoice.gender.female'),
            $this->translator->translate('invoice.gender.other'),
        ];
        foreach ($genders_array as $key => $val) {
            $optionsDataGender[(string)$key] = $val;
        }
        return $optionsDataGender;
    }
    
    /**
     * @param EntityReader $postalAddresses
     * @return array
     */
    private function optionsDataPostalAddress(EntityReader $postalAddresses) : array
    {
        $optionsDataPostalAddress = [];
        /**
         * @var PostalAddress $postalAddress
         */
        foreach ($postalAddresses as $postalAddress)
        {
            $paId = (int)$postalAddress->getId();
            $address = [];
            if ($paId > 0) {
                if ($postalAddress->getStreet_name()) {
                    $address[] = $postalAddress->getStreet_name();
                }
                if ($postalAddress->getAdditional_street_name()) {
                    $address[] = $postalAddress->getAdditional_street_name();
                }
                if ($postalAddress->getBuilding_number()) {
                    $address[] = $postalAddress->getBuilding_number();
                }
                if ($postalAddress->getCity_name()) {
                    $address[] = $postalAddress->getCity_name();
                }
                if ($postalAddress->getPostalzone()) {
                    $address[] = $postalAddress->getPostalzone();
                }
                if ($postalAddress->getCountrysubentity()) {
                    $address[] = $postalAddress->getCountrysubentity();
                }
                if ($postalAddress->getCountry()) {
                    $address[] = $postalAddress->getCountry();
                }
                $optionsDataPostalAddress[$paId] = implode(",", $address);
            }
        }
        return $optionsDataPostalAddress;
    }

    public function optionsDataClientNameDropdownFilter(cR $cR) : array
    {
        $optionsDataClientName = [];
        $clients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $firstname = $client->getClient_name();
            $optionsDataClientName[$firstname] = $firstname; 
        }
        return $optionsDataClientName;
    }
    
    public function optionsDataClientSurnameDropdownFilter(cR $cR) : array
    {
        $optionsDataClientSurname = [];
        $clients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $surname = $client->getClient_surname();
            if (null!=$surname) {
                $optionsDataClientSurname[$surname] = $surname;
            }    
        }
        return $optionsDataClientSurname;
    }
    
    public function optionsDataClientFrequencyDropdownFilter() : array
    {
        $optionsDataClientFrequency = [];
        $optionsDataClientFrequency['None'] = 'None';
        $optionsDataClientFrequency['Only 1'] = 'Only 1';
        $optionsDataClientFrequency['Daily'] = 'Daily';
        $optionsDataClientFrequency['Weekly'] = 'Weekly';
        $optionsDataClientFrequency['Monthly'] = 'Monthly';
        $optionsDataClientFrequency['2 Monthly'] = '2 Monthly';
        $optionsDataClientFrequency['3 Monthly'] = '3 Monthly';
        $optionsDataClientFrequency['6 Monthly'] = '6 Monthly';
        return $optionsDataClientFrequency;
    }    
    
    /**
     * 
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param cR $cR
     * @param cfR $cfR
     * @param cnR $cnR
     * @param cpR $cpR
     * @param cvR $cvR
     * @param ccR $ccR
     * @param delR $delR
     * @param gR $gR
     * @param iR $iR
     * @param iaR $iaR
     * @param irR $irR
     * @param qR $qR
     * @param pymtR $pymtR
     * @param qaR $qaR
     * @param sR $sR
     * @param ucR $ucR
     * @return Response
     */    
    public function view(SessionInterface $session, CurrentRoute $currentRoute, cR $cR, cfR $cfR, cnR $cnR, cpR $cpR, cvR $cvR, ccR $ccR, delR $delR, gR $gR, iR $iR, iaR $iaR, irR $irR, qR $qR, pymtR $pymtR, qaR $qaR, sR $sR, ucR $ucR   
    ): Response {
        $quote = new Quote();
        $quoteForm = new QuoteForm($quote);
        $bootstrap5ModalQuote = new Bootstrap5ModalQuote($this->translator, $this->viewRenderer, $cR, $gR, $sR, $ucR, $quoteForm);
        
        $inv = new Inv();
        $invForm = new InvForm($inv);
        $bootstrap5ModalInv = new Bootstrap5ModalInv($this->translator, $this->viewRenderer, $cR, $gR, $sR, $ucR, $invForm);
                
        $optionsGroupData = [];
        
        $groups = $gR->findAllPreloaded();
        /**
         * @var \App\Invoice\Entity\Group
         */
        foreach ($groups as $group) {
            $optionsGroupData[$group->getId()] = $group->getName();
        } 
        $client = $this->client($currentRoute, $cR);      
        if ($client instanceof Client) {
              $client_id = $client->getClient_id();  
              if (null!==$client_id) {
                $parameters = [
                    'title' => $this->translator->translate('i.client'),
                    'alert' => $this->alert(),
                    'iR' => $iR,
                    'iaR' => $iaR,
                    'clienthelper' => new ClientHelper($sR),
                    'custom_fields' => $cfR->repoTablequery('client_custom'),
                    'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('client_custom')),
                    'cpR' => $cpR,
                    'cvH' => new CVH($sR),
                    'client_custom_values'=>$this->client_custom_values((string)$client_id, $ccR),
                    'client' => $client,            
                    'client_notes' => $cnR->repoClientNoteCount($client_id) > 0 ? $cnR->repoClientquery((string)$client_id) : [],
                    'partial_client_address' => $this->viewRenderer->renderPartialAsString('/invoice/client/partial_client_address', [
                        'client' => $client,            
                        'countryhelper' => new CountryHelper(),
                    ]),
                    // Note here the client_id is presented as the 'origin'. Origin could be 'quote', 'main', 'dashboard'
                    'client_modal_layout_quote' => $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString((string)$client_id, []),
                    'client_modal_layout_inv'=> $bootstrap5ModalInv->renderPartialLayoutWithFormAsString((string)$client_id, []),  
                    'quote_table' => $this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                        'qaR'=> $qaR,
                        'quote_count' => $qR->repoCountByClient($client_id),
                        'quotes' => $qR->repoClient($client_id),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'quote_statuses' => $qR->getStatuses($this->translator),
                    ]),
                    'quote_draft_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                        'qaR'=> $qaR,
                        'quote_count' => $qR->by_client_quote_status_count($client_id,1),
                        'quotes' => $qR->by_client_quote_status($client_id,1),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'quote_statuses' => $qR->getStatuses($this->translator),
                    ]),
                    'quote_sent_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                        'qaR'=> $qaR,
                        'quote_count' => $qR->by_client_quote_status_count($client_id,2),
                        'quotes' => $qR->by_client_quote_status($client_id,2),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'quote_statuses' => $qR->getStatuses($this->translator),
                    ]),
                    'quote_viewed_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                        'qaR'=> $qaR,
                        'quote_count' => $qR->by_client_quote_status_count($client_id,3),
                        'quotes' => $qR->by_client_quote_status($client_id,3),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'quote_statuses' => $qR->getStatuses($this->translator),
                    ]),
                    'quote_approved_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                        'qaR'=> $qaR,
                        'quote_count' => $qR->by_client_quote_status_count($client_id,4),
                        'quotes' => $qR->by_client_quote_status($client_id,4),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'quote_statuses' => $qR->getStatuses($this->translator),
                    ]),
                    'quote_rejected_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                        'qaR'=> $qaR,
                        'quote_count' => $qR->by_client_quote_status_count($client_id,5),
                        'quotes' => $qR->by_client_quote_status($client_id,5),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'quote_statuses' => $qR->getStatuses($this->translator),
                    ]),
                    'quote_cancelled_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                        'qaR'=> $qaR,
                        'quote_count' => $qR->by_client_quote_status_count($client_id,6),
                        'quotes' => $qR->by_client_quote_status($client_id,6),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'quote_statuses' => $qR->getStatuses($this->translator),
                    ]),
                    'invoice_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                        'iaR'=> $iaR,
                        'irR'=> $irR,
                        'invoice_count'=>$iR->repoCountByClient($client_id),
                        'invoices' => $iR->repoClient($client_id),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'inv_statuses' => $iR->getStatuses($this->translator),
                        'session' => $session,
                    ]),
                    'invoice_draft_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                        'iaR'=> $iaR,
                        'irR'=> $irR,
                        'invoice_count' => $iR->by_client_inv_status_count($client_id,1),    
                        'invoices' => $iR->by_client_inv_status($client_id,1),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'inv_statuses' => $iR->getStatuses($this->translator)
                    ]),
                    'invoice_sent_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                        'iaR'=> $iaR,
                        'irR'=> $irR,
                        'invoice_count' => $iR->by_client_inv_status_count($client_id,2),    
                        'invoices' => $iR->by_client_inv_status($client_id,2),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'inv_statuses' => $iR->getStatuses($this->translator)
                    ]),
                    'invoice_viewed_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                        'iaR'=> $iaR,
                        'irR'=> $irR,
                        'invoice_count' => $iR->by_client_inv_status_count($client_id,3),    
                        'invoices' => $iR->by_client_inv_status($client_id,3),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'inv_statuses' => $iR->getStatuses($this->translator)
                    ]),
                    'invoice_paid_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                        'iaR'=> $iaR,
                        'irR'=> $irR,
                        'invoice_count' => $iR->by_client_inv_status_count($client_id,4),    
                        'invoices' => $iR->by_client_inv_status($client_id,4),
                        'clienthelper' => new ClientHelper($sR),
                        'datehelper' => new DateHelper($sR),
                        'inv_statuses' => $iR->getStatuses($this->translator)
                    ]),
                    'partial_notes'=>$this->viewRenderer->renderPartialAsString('/invoice/clientnote/partial_notes', [
                        'client_notes' => $cnR->repoClientquery((string)$client_id),
                        'datehelper' => new DateHelper($sR),
                    ]),
                    'payment_table'=>$this->viewRenderer->renderPartialAsString('/invoice/payment/partial_payment_table', [
                        'client'=> $client,
                        // All payments from the client are loaded and filtered in the view with 
                        // if ($payment->getInv()->getClient_id() === $client->getClient_id())
                        'payments'=> $pymtR->repoPaymentInvLoadedAll((int)$sR->get_setting('payment_list_limit') ?: 10),
                        'clienthelper' => new ClientHelper($sR),
                    ]), 
                    'delivery_locations'=>$this->viewRenderer->renderPartialAsString('/invoice/client/client_delivery_location_list', [
                        'client'=> $client,
                        'locations'=> $delR->repoClientquery((string)$client->getClient_id()),
                        'clienthelper' => new ClientHelper($sR),
                    ]), 
                ];
                return $this->viewRenderer->render('view', $parameters);
           } else {
                return $this->webService->getRedirectResponse('client/index');
           } 
        } // if $client     
        return $this->webService->getRedirectResponse('client/index');
    }
}