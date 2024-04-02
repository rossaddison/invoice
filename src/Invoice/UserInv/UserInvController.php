<?php
declare(strict_types=1); 

namespace App\Invoice\UserInv;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\UserInv;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvForm;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\UserInv\UserInvService;
use App\Service\WebControllerService;
use App\User\UserRepository as uR;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Rbac\AssignmentsStorageInterface as Assignment;
use Yiisoft\Rbac\ItemsStorageInterface as ItemStorage;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Rbac\RuleFactoryInterface as Rule;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class UserInvController
{
    private Assignment $assignment;
    private ItemStorage $itemstorage;
    private Manager $manager;
    private Rule $rule;
    
    private Flash $flash;
    private DataResponseFactoryInterface $factory;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private UserInvService $userinvService;
    private TranslatorInterface $translator;
    private Session $session;
        
    public function __construct(
        // Note: yiisoft/rbac-php or file or php based rbac is used
        //       The assignRole console command is used to assign roles to the first user admin
        //       and second user observer. Subsequent users are signed up, matched to the client 
        //       via Settings ... User Account and assigned the role Observer/Accountant via Settings ... User Account
        // @see config/common/di/rbac.php for data injection via php
        
        // load assignments and save assignments to resources/rbac/assignment.php
        Assignment $assignment,
        
        // add, save, remove, clear, children, parents
        ItemStorage $itemstorage,
        Rule $rule,
      
        DataResponseFactoryInterface $factory,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        UserInvService $userinvService,
        TranslatorInterface $translator,
        Session $session,    
    )    
    {
        
        $this->assignment = $assignment;
        $this->itemstorage = $itemstorage;
        
        // @see yiisoft/rbac-php
        $this->manager = new Manager($itemstorage, $assignment, $rule);
        $this->rule = $rule;
        
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer; 
        $this->webService = $webService;
        $this->userService = $userService;
        $this->userinvService = $userinvService;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('editUserInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/userinv')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/userinv')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->translator = $translator;
        $this->session = $session;
        $this->flash = new Flash($session);
    }

    // UserInv  is the extension Table of User
    // Users that have been signed up through the demo must be added
    // to the invoicing system 
    // using Setting...User Account
    
    /**
     * 
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param UserInvRepository $uiR
     * @param UserClientRepository $ucR
     * @param SettingRepository $sR
     * @param TranslatorInterface $translator
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(Request $request, 
                          CurrentRoute $currentRoute, 
                          UserInvRepository $uiR,
                          UserClientRepository $ucR,
                          SettingRepository $sR, 
                          TranslatorInterface $translator): \Yiisoft\DataResponse\DataResponse
    {      
        $canEdit = $this->rbac();
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page'] 
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');        
        $active = (int)$currentRoute->getArgument('active', '2');         
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['user_id', 'name', 'email'])          
            ->withOrderString($query_params['sort'] ?? '-user_id');
        $repo = $this->userinvs_active_with_sort($uiR,$active,$sort); 
        /**
         * @psalm-suppress PossiblyInvalidArgument
         */
        $paginator = (new OffsetPaginator($repo))        
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage((int)$page)               
        ->withToken(PageToken::next((string)$page));   
        $parameters = [
          'uiR'=>$uiR,
          // get a count of clients allocated to the user
          'ucR'=>$ucR,  
          'active'=>$active,   
          'paginator'=>$paginator,
          'translator'=>$translator,
          'canEdit' => $canEdit,
          'grid_summary'=> $sR->grid_summary($paginator, $this->translator, (int)$sR->get_setting('default_list_limit'), $this->translator->translate('invoice.payments'), ''),
          'userinvs' => $repo,
          'locale'=>$this->session->get('_language'),
          'alert'=>$this->alert(),
          // Parameters for GridView->requestArguments
          'page'=> $page,
          'sortOrder' => $query_params['sort'] ?? '',
          'manager' => $this->manager
        ];
        return $this->viewRenderer->render('index', $parameters);        
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param UserInvRepository $userinvRepository
     * @param uR $uR
     * @return Response
     */
    public function guest(Request $request, 
                        FormHydrator $formHydrator,
                        UserInvRepository $userinvRepository, 
                        uR $uR,

    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        if (null!==$this->userService->getUser()){
            $id = $this->userService->getUser()?->getId();
            if (null!==$id) {
                $userinv = $userinvRepository->repoUserInvUserIdquery($id);
                if ($userinv) {
                    $form = new UserInvForm($userinv);
                    $parameters = [
                        'title' => $this->translator->translate('i.edit'),
                        'action' => ['userinv/guest'],
                        'errors' => [],
                        'form' => $form,
                        'aliases'=>$aliases,
                        'users'=>$uR->findAllUsers(),
                    ];
                    if ($request->getMethod() === Method::POST) {
                        /**
                         * @psalm-suppress PossiblyInvalidArgument $body
                         */
                        $body = $request->getParsedBody();
                        if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                            /**
                             * @psalm-suppress PossiblyInvalidArgument $body
                             */
                            $this->userinvService->saveUserInv($userinv, $body);
                            return $this->webService->getRedirectResponse('invoice/index');
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                        $parameters['form'] = $form;
                    }
                    return $this->viewRenderer->render('_form_guest', $parameters);
                }
                return $this->webService->getRedirectResponse('invoice/index');
            } // nul!== $id
            return $this->webService->getNotFoundResponse();
        } // null!==$this->userService->getUser()
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * @return string
     */
    private function alert(): string {
      return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
      [ 
        'flash' => $this->flash
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
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $sR
     * @param uR $uR
     * @return Response
     */
    public function add(Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $sR,
                        uR $uR, 
    ) : Response
    {        
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        $countries = new CountryHelper();
        $userinv = new UserInv();
        $form = new UserInvForm($userinv);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'action' => ['userinv/add'],
            'aliases'=>$aliases,
            'errors' => [],
            'form' => $form,
            'users'=>$uR->findAllUsers(),
            'selected_country' => $sR->get_setting('default_country'),            
            'selected_language' => $sR->get_setting('default_language'),
            'countries'=> $countries->get_country_list($sR->get_setting('cldr'))
        ];
        
        if ($request->getMethod() === Method::POST) {            
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                // assign the observer role by default to a new user inv if it is not admin
                // and has not been assigned the observer role
                // form dropdown type 0 => admin, type 1 => guest
                $body = $request->getParsedBody() ?? [];
                /**
                 * @var string $body['type']
                 */
                $type = $body['type'];
                if (null!==$form->getUser_id()) {
                    // the user is not admin(1) and the guest dropdown type(1) has been selected
                    if ($form->getUser_id() <> '1' && $type == '1') {
                        $roles = $this->manager->getRolesByUserId($form->getUser_id());
                        if (!array_key_exists('observer', $roles)) {
                            $this->manager->assign('observer', $form->getUser_id());
                            $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.all.new'));
                        } else {
                            $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.observer.already.assigned'));
                        }
                        /**
                         * @psalm-suppress PossiblyInvalidArgument $body
                         */
                        $this->userinvService->saveUserInv($userinv, $body);
                    }
                    // the user is not admin(1) and the type administrator(0) was selected in the dropdown on the form
                    if ($form->getUser_id() <> '1' && $type == '0') {
                        $this->flash_message('warning', $this->translator->translate('invoice.user.inv.type.cannot.allocate.administrator.type.to.non.administrator'));
                    }
                    // the user is admin and the type administrator was selected in the dropdown on the form
                    if ($form->getUser_id() == '1' && $type == '0') {
                        // if the admin role has not yet been assigned, assign it now
                        $roles = $this->manager->getRolesByUserId($form->getUser_id());
                        if (!array_key_exists('admin', $roles)) {
                            $this->manager->assign('admin', $form->getUser_id());
                            $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.administrator.assigned'));
                        } else {
                            $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.administrator.already.assigned'));
                        }
                        /**
                         * @psalm-suppress PossiblyInvalidArgument $body
                         */
                        $this->userinvService->saveUserInv($userinv, $body);
                    }
                    // the user is an admin and the type guest was selected in the dropdown on the form
                    if ($form->getUser_id() == '1' && $type == '1') {
                        $this->flash_message('warning', $this->translator->translate('invoice.user.inv.type.cannot.allocate.guest.type.to.administrator'));
                    }
                    return $this->webService->getRedirectResponse('userinv/index');
                } // null!== $form->getUser_id()
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function assignObserverRole(CurrentRoute $currentRoute) : Response {
      $user_id = $currentRoute->getArgument('user_id');
      if (null!==$user_id) {
        $this->manager->revokeAll($user_id);
        $this->manager->assign('observer', $user_id);
        $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.observer.assigned'));
      }
      return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function assignAccountantRole(CurrentRoute $currentRoute) : Response {
      $user_id = $currentRoute->getArgument('user_id');
      if (null!==$user_id) {
        $this->manager->revokeAll($user_id);
        $this->manager->assign('accountant', $user_id);
        $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.accountant.assigned'));
        $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.accountant.default'));
      }
      return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param UserInvRepository $userinvRepository
     * @param SettingRepository $settingRepository
     * @param uR $uR
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        UserInvRepository $userinvRepository,
                        SettingRepository $settingRepository,
                        uR $uR,
    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        $userinv = $this->userinv($currentRoute, $userinvRepository);
        if ($userinv) {
            $form = new UserInvForm($userinv);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['userinv/edit', ['id' => $userinv->getId()]],
                'errors' => [],
                'form' => $form,
                'aliases' => $aliases,
                'users' =>$uR->findAllUsers(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                    
                    /**
                     * @var string $body['type']
                     */
                    $type = $body['type'];
                    if (null!==$form->getUser_id()) {
                        // the user is not admin(1) and the guest dropdown type(1) has been selected
                        if ($form->getUser_id() <> '1' && $type == '1') {
                            $roles = $this->manager->getRolesByUserId($form->getUser_id());
                            if (!array_key_exists('observer', $roles)) {
                                $this->manager->assign('observer', $form->getUser_id());
                                $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.all.new'));
                            } else {
                                $this->flash_message('warning', $this->translator->translate('invoice.user.inv.role.observer.assigned.already'));
                            }
                            /**
                             * @psalm-suppress PossiblyInvalidArgument $body
                             */
                            $this->userinvService->saveUserInv($userinv, $body);
                        }
                        // the user is not admin(1) and the type administrator(0) was selected in the dropdown on the form
                        if ($form->getUser_id() <> '1' && $type == '0') {
                            $this->flash_message('warning', $this->translator->translate('invoice.user.inv.type.cannot.allocate.administrator.type.to.non.administrator'));
                        }
                        // the user is admin and the type administrator was selected in the dropdown on the form
                        if ($form->getUser_id() == '1' && $type == '0') {
                            // if the admin role has not yet been assigned, assign it now
                            $roles = $this->manager->getRolesByUserId($form->getUser_id());
                            if (!array_key_exists('admin', $roles)) {
                                $this->manager->assign('admin', $form->getUser_id());
                                $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.administrator.assigned'));
                            } else {
                                $this->flash_message('warning', $this->translator->translate('invoice.user.inv.role.administrator.assigned.already'));
                            }
                            $this->userinvService->saveUserInv($userinv, $body);
                        }
                        // the user is an admin and the type guest was selected in the dropdown on the form
                        if ($form->getUser_id() == '1' && $type == '1') {
                            $this->flash_message('warning', $this->translator->translate('invoice.user.inv.type.cannot.allocate.guest.type.to.administrator'));
                        }
                        return $this->webService->getRedirectResponse('userinv/index');
                    } // null!== user_id    
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');         
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $cR
     * @param UserClientRepository $ucR
     * @param UserInvRepository $uiR
     */
    public function client(CurrentRoute $currentRoute, ClientRepository $cR, UserClientRepository $ucR, UserInvRepository $uiR) 
        : \Yiisoft\DataResponse\DataResponse|Response {
        // Use the primary key 'id' passed in userinv/index's urlGenerator to retrieve the user_id
        $userinv = $this->userinv($currentRoute, $uiR);
        if (null!==$userinv) {
            $user_id = $userinv->getUser_Id();
            if ($user_id) {
                $parameters = [
                    'cR'=>$cR,
                    'alert'=>$this->alert(),
                    // Get all clients that this user will deal with
                    'user_clients'=>$ucR->repoClientquery($user_id),
                    'userinv'=>$uiR->repoUserInvUserIdquery($user_id),
                    'user_id'=>$user_id,
                ];
                return $this->viewRenderer->render('field', $parameters);
            }
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param TranslatorInterface $translator
     * @param CurrentRoute $currentRoute
     * @param UserInvRepository $userinvRepository
     * @return Response
     */
    public function delete(TranslatorInterface $translator, CurrentRoute $currentRoute,UserInvRepository $userinvRepository 
    ): Response {
        $userinv = $this->userinv($currentRoute,$userinvRepository); 
        if ($userinv) {
            $this->userinvService->deleteUserInv($userinv);               
            $this->flash_message('info', $translator->translate('invoice.deleted'));
            return $this->webService->getRedirectResponse('userinv/index'); 	
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserInvRepository $userinvRepository
     * @param uR $uR
     */
    public function view(CurrentRoute $currentRoute,UserInvRepository $userinvRepository, uR $uR): \Yiisoft\DataResponse\DataResponse|Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        $userinv = $this->userinv($currentRoute, $userinvRepository);
        if ($userinv) {
            $form = new UserInvForm($userinv);
            $parameters = [
                'aliases' => $aliases,
                'title' => $this->translator->translate('i.view'),
                'action' => ['userinv/view', ['id' => $userinv->getId()]],
                'errors' => [],
                'form' => $form,
                'userinv'=>$userinvRepository->repoUserInvquery((string)$userinv->getId()),
                'users'=>$uR->findAllUsers(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
        
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $canEdit;
    }
    
    /**
     * @param UserInvRepository $uiR
     * @param int $active
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function userinvs_active_with_sort(UserInvRepository $uiR, int $active, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Data\Cycle\Reader\EntityReader {       
        $userinvs = $uiR->findAllWithActive($active)
                        ->withSort($sort);
        return $userinvs;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserInvRepository $userinvRepository
     * @return UserInv|null
     */
    private function userinv(CurrentRoute $currentRoute, UserInvRepository $userinvRepository): UserInv|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $userinv = $userinvRepository->repoUserInvquery($id);
            return $userinv;
        }
        return null;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @return UserClient|null
     */
    private function userclient(CurrentRoute $currentRoute, UserClientRepository $userclientRepository) : UserClient|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $userclient = $userclientRepository->repoUserClientquery($id);
            if (null!==$userclient) {
                return $userclient;
            }
            return null;
        }
        return null;
    }
}

