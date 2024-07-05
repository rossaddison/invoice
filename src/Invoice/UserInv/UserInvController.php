<?php
declare(strict_types=1); 

namespace App\Invoice\UserInv;

use App\Invoice\Entity\UserInv;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvForm;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Invoice\UserInv\UserInvService;
use App\Service\WebControllerService;
use App\User\UserService;
use App\User\UserRepository as uR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Rbac\AssignmentsStorageInterface as Assignment;
use Yiisoft\Rbac\ItemsStorageInterface as ItemStorage;
use Yiisoft\Rbac\Manager as Manager;
use Yiisoft\Rbac\RuleFactoryInterface as Rule;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

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
    
    /**
     * @see Purpose: Transfer i.e add, newly signed up users, sitting in user Table, into userInv Table
     * @param Request $request
     * @param string $_language
     * @param FormHydrator $formHydrator
     * @param SettingRepository $sR
     * @param uR $uR
     * @param uiR $uiR
     * @return Response
     */
    public function add(Request $request,
                        #[RouteArgument('_language')] string $_language, 
                        FormHydrator $formHydrator,
                        SettingRepository $sR,
                        uR $uR,
                        uiR $uiR
    ) : Response
    {        
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        $countries = new CountryHelper();
        $userinv = new UserInv();
        $form = new UserInvForm($userinv);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'actionName' => 'userinv/add',
            'actionArguments' => [],
            'aliases' => $aliases,
            'errors' => [],
            'form' => $form,
            // Only include newly signed up user ids in user Table in dropdown list i.e exclude those users already added and linked with client(s)
            'selected_country' => $sR->get_setting('default_country'),            
            'selected_language' => $sR->get_setting('default_language'),
            'countries' => $countries->get_country_list($_language),
            'uR' => $uR,
            'uiR' => $uiR
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
                            $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.observer.assigned.already'));
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
        return $this->viewRenderer->render('_form_add', $parameters);
    }

    // UserInv  is the extension Table of User
    // Users that have been signed up through the demo must be added
    // to the invoicing system 
    // using Setting...Invoice User Account
    
    /**
     * @param uiR $uiR
     * @param UserClientRepository $ucR
     * @param SettingRepository $sR
     * @param string $_language
     * @param string $page
     * @param string $active
     * @param string $queryPage
     * @param string $querySort
     * @param string $queryFilterUser
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(uiR $uiR, UserClientRepository $ucR, SettingRepository $sR, 
        #[RouteArgument('_language')] string $_language, 
        #[RouteArgument('page')] string $page = '1',
        #[RouteArgument('active')] string $active = '2',
        #[Query('page')] string $queryPage = null,
        #[Query('sort')] string $querySort = null,
        #[Query('filterUser')] string $queryFilterUser = null,
    ): \Yiisoft\DataResponse\DataResponse
    {      
        $canEdit = $this->rbac();
        $pageString = $queryPage ?? $page;
        $activeInt = (int)$active;
        $sortString = $querySort ?? '-user_id';
        $sort = Sort::only(['user_id', 'name', 'email'])          
            ->withOrderString($sortString);
        $userinvs = $this->userinvs_active_with_sort($uiR, $activeInt, $sort); 
        if (isset($queryFilterUser) && !empty($queryFilterUser)) {
            $userinvs = $uiR->filterUserInvs($queryFilterUser);
        }
        /**
         * @psalm-suppress PossiblyInvalidArgument
         */
        $paginator = (new OffsetPaginator($userinvs))        
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage((int)$pageString)               
        ->withToken(PageToken::next($pageString));   
        $parameters = [
            'uiR' => $uiR, 
            // get a count of clients allocated to the user
            'ucR' => $ucR,  
            'active' => $activeInt,   
            'paginator' => $paginator,
            'canEdit' => $canEdit,            
            'userinvs' => $userinvs,
            'locale' => $_language,
            'alert' => $this->alert(),
            // Parameters for GridView->requestArguments
            'page' => $pageString,
            'sortOrder' => $querySort ?? '',
            'manager' => $this->manager,
            'optionsDataFilterUserInvLoginDropDown' => $this->optionsDataFilterUserInvLogin($uiR)
        ];
        return $this->viewRenderer->render('index', $parameters);        
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param uiR $uiR
     * @return Response
     */
    public function guest(Request $request, 
                        FormHydrator $formHydrator,
                        uiR $uiR
    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        if (null!==$this->userService->getUser()){
            $id = $this->userService->getUser()?->getId();
            if (null!==$id) {
                $userinv = $uiR->repoUserInvUserIdquery($id);
                if ($userinv) {
                    $form = new UserInvForm($userinv);
                    $parameters = [
                        'title' => $this->translator->translate('i.edit'),
                        'actionName' => 'userinv/guest',
                        'actionArguments' => [],
                        'errors' => [],
                        'form' => $form,
                        'aliases' => $aliases
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
     * @see src\Widget\PageSizeLimiter buttonsGuest function
     * @see ..\resources\views\invoice\inv\guest.php
     * @see InvController\guest
     * @param string $userInvId
     * @param string $origin
     * @param string $limit
     * @param uiR $uiR
     * @return Response
     */
    public function guestlimit(
            #[RouteArgument('userinv_id')] string $userInvId,
            #[RouteArgument('origin')] string $origin,
            #[RouteArgument('limit')] string $limit,
            uiR $uiR
        ) : Response {
        if (strlen($userInvId) > 0 && strlen($origin) > 0) {
            $limitInt = (int)$limit;
            $userInv = $uiR->repoUserInvquery($userInvId);
            if (null!==$userInv) {
                $userInv->setListLimit($limitInt);
                $uiR->save($userInv);
            }
        }    
        /**
         * @see config/common/routes.php Route::get('/client_invoices[/page/{page:\d+}[/status/{status:\d+}]]')
         */
        return $this->webService->getRedirectResponse(strlen($origin) > 0 ? $origin.'/guest' : 'client/guest');
    }
    
    /**
     * @return string
     */
    private function alert(): string {
      return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
      [ 
        'flash' => $this->flash
      ]);
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
    
    /**
     * @param string $user_id
     * @return Response
     */
    public function assignObserverRole(#[RouteArgument('user_id')] string $user_id) : Response {
      if (strlen($user_id) > 0) {
        $this->manager->revokeAll($user_id);
        $this->manager->assign('observer', $user_id);
        $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.observer.assigned'));
      }
      return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param string $user_id
     * @return Response
     */
    public function assignAccountantRole(#[RouteArgument('user_id')] string $user_id) : Response {
      if (strlen($user_id) > 0) {
        $this->manager->revokeAll($user_id);
        $this->manager->assign('accountant', $user_id);
        $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.accountant.assigned'));
        $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.accountant.default'));
      }
      return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param string $user_id
     * @return Response
     */
    public function revokeAllRoles(#[RouteArgument('user_id')] string $user_id) : Response {
      if (strlen($user_id) > 0) {
        $this->manager->revokeAll($user_id);
        $this->flash_message('info', $this->translator->translate('invoice.user.inv.role.revoke.all'));
      }
      return $this->webService->getRedirectResponse('userinv/index');
    }
    
    private function getRolesByUserId(string $userId) : array {
        return $this->manager->getRolesByUserId($userId);
    }
    
    /**
     * @param Request $request
     * @param int $id
     * @param FormHydrator $formHydrator
     * @param UserInvRepository $userinvRepository
     * @param uR $uR
     * @return Response
     */   
    public function edit(Request $request, 
                        #[RouteArgument('id')] int $id, 
                        FormHydrator $formHydrator,
                        UserInvRepository $userinvRepository,
                        uR $uR,
    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        $userinv = $this->userinv($id, $userinvRepository);
        if ($userinv) {
            $form = new UserInvForm($userinv);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'userinv/edit',
                'actionArguments' => ['id' => $userinv->getId()],
                'errors' => [],
                'form' => $form,
                'aliases' => $aliases,
                'uR' => $uR
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
            return $this->viewRenderer->render('_form_edit', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');         
    }
    
    /**
     * @param int $id
     * @param UserClientRepository $ucR
     * @param UserInvRepository $uiR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function client(#[RouteArgument('id')] int $id, UserClientRepository $ucR, UserInvRepository $uiR) 
        : \Yiisoft\DataResponse\DataResponse|Response {
        // Use the primary key 'id' passed in userinv/index's urlGenerator to retrieve the user_id
        $userinv = $this->userinv($id, $uiR);
        if (null!==$userinv) {
            $user_id = $userinv->getUser_Id();
            if ($user_id) {
                $parameters = [
                    'alert' => $this->alert(),
                    // Get all clients that this user will deal with
                    'ucR' => $ucR,
                    'userInv' => $uiR->repoUserInvUserIdquery($user_id)
                ];
                return $this->viewRenderer->render('field', $parameters);
            }
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * 
     * @param TranslatorInterface $translator
     * @param int $id
     * @param UserInvRepository $userinvRepository
     * @return Response
     */
    public function delete(TranslatorInterface $translator, #[RouteArgument('id')] int $id, UserInvRepository $userinvRepository 
    ): Response {
        $userinv = $this->userinv($id, $userinvRepository); 
        if ($userinv) {
            $this->userinvService->deleteUserInv($userinv);               
            $this->flash_message('info', $translator->translate('invoice.deleted'));
            return $this->webService->getRedirectResponse('userinv/index'); 	
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param int $id
     * @param UserInvRepository $userinvRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(#[RouteArgument('id')] int $id, UserInvRepository $userinvRepository): \Yiisoft\DataResponse\DataResponse|Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        $userinv = $this->userinv($id, $userinvRepository);
        if ($userinv) {
            $form = new UserInvForm($userinv);
            $parameters = [
                'aliases' => $aliases,
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'userinv/view',
                'actionArguments' => ['id' => $userinv->getId()],
                'errors' => [],
                'form' => $form,
                'userinv' => $userinvRepository->repoUserInvquery((string)$userinv->getId()),
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
     * @param int $id
     * @param UserInvRepository $userinvRepository
     * @return UserInv|null
     */
    private function userinv(int $id, UserInvRepository $userinvRepository): UserInv|null
    {
        if ($id) {
            $userinv = $userinvRepository->repoUserInvquery((string)$id);
            return $userinv;
        }
        return null;
    } 
    
    /**
     * @param UserInvRepository $uiR
     * @return array
     */
    public function optionsDataFilterUserInvLogin(UserInvRepository $uiR) : array
    {
        $optionsDataUserInvs = [];
        $userInvs = $uiR->findAllPreloaded();
        /**
         * @var UserInv $userInv
         */
        foreach ($userInvs as $userInv) {
            if (null!==$userInv->getUser()) {
                $login = $userInv->getUser()?->getLogin();
                if (null!==$login) {
                    $optionsDataUserInvs[$login] = $login;
                }
            }    
        }
        return $optionsDataUserInvs;
    }
}