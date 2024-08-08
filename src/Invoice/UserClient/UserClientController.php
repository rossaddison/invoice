<?php
declare(strict_types=1); 

namespace App\Invoice\UserClient;

use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\UserInv;
use App\Invoice\Client\ClientRepository;
use App\Invoice\UserClient\UserClientService;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserClient\UserClientForm;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class UserClientController
{
    private Session $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private UserClientService $userclientService;
    private DataResponseFactoryInterface $factory;
    private TranslatorInterface $translator;
        
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        UserClientService $userclientService,
        DataResponseFactoryInterface $factory,
        TranslatorInterface $translator,
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/userclient')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->userclientService = $userclientService;
        $this->factory = $factory;
        $this->translator = $translator;
    }
    
    /**
     * @param UserClientRepository $userclientRepository
     */
    public function index(UserClientRepository $userclientRepository) : \Yiisoft\DataResponse\DataResponse
    {      
        $canEdit = $this->rbac();$parameters = [
          'canEdit' => $canEdit,
          'userclients' => $this->userclients($userclientRepository),
          'alert'=> $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator) : Response
    {
        $user_client = new UserClient();
        $form = new UserClientForm($user_client);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'action' => ['userclient/add'],
            'errors' => [],
            'form' => $form
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->userclientService->saveUserClient($user_client, $body);
                return $this->webService->getRedirectResponse('userclient/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @param UIR $uiR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function delete(CurrentRoute $currentRoute, UserClientRepository $userclientRepository, UIR $uiR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $user_client = $this->userclient($currentRoute, $userclientRepository);
        if (null!==$user_client) {
            $user_id = $user_client->getUser_Id();
            $this->userclientService->deleteUserClient($user_client);               
            $user_inv = $uiR->repoUserInvUserIdquery($user_id);
            if (null!==$user_inv) {
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->factory->createResponse(
                        $this->viewRenderer->renderPartialAsString('//invoice/setting/userclient_successful',
                        [
                            'heading'=>$this->translator->translate('i.client'),
                            'message'=> $this->translator->translate('i.record_successfully_deleted'),
                            'url'=>'userinv/client','id'=>$user_inv->getId()
                        ]));
            }
            return $this->webService->getRedirectResponse('userclient/index');
        }
        return $this->webService->getRedirectResponse('userclient/index');
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param UserClientRepository $userclientRepository
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        UserClientRepository $userclientRepository): Response {
    $user_client = $this->userclient($currentRoute, $userclientRepository);
    if ($user_client) {
            $form = new UserClientForm($user_client);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['userclient/edit', ['id' => $user_client->getId()]],
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody();
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->userclientService->saveUserClient($user_client, $body);
                    return $this->webService->getRedirectResponse('userclient/index');
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('userclient/index');     
    }
    
    // The preceding url is userinv/client/{userinv_id} showing the currently assigned clients to this user
    
    // Retrieves userclient/new.php which offers an 'all client option' and an individual client option
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $cR
     * @param UserClientRepository $ucR
     * @param UserClientService $ucS
     * @param UIR $uiR
     * @return Response
     */
    public function new(Request $request, FormHydrator $formHydrator, CurrentRoute $currentRoute, 
                        ClientRepository $cR, UserClientRepository $ucR, UserClientService $ucS, UIR $uiR): Response {
        
        $user_id = $currentRoute->getArgument('user_id');
        if (null!==$user_id) {
            // Get possible client ids as an array that can be presented to this user
            $availableClientIdList = $ucR->get_not_assigned_to_user($user_id, $cR);
            $user_client = new UserClient();
            $form = new UserClientForm($user_client);
            $parameters = [
                'errors' => [],
                'userinv'=>$this->user($currentRoute, $uiR),
                // Only provide clients NOT already included ie. available
                'availableClientIdList' => $availableClientIdList,
                'cR' => $cR,
                // Initialize the checkbox to zero so that both 'all_clients' and dropdownbox is presented on userclient/new.php
                'form' => $form
            ];

            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if (is_array($body)) {
                    /** @var string $value */
                    foreach ($body as $key => $value) {
                        // If the user is allowed to see all clients eg. An Accountant
                        if (((string)$key === 'user_all_clients') && ($value === '1')) {
                            // Unassign currently assigned clients
                            $ucR->unassign_to_user_client($user_id);
                            // Search for all clients, including new clients and assign them aswell
                            $ucR->reset_users_all_clients($uiR, $cR, $ucS, $formHydrator);
                            return $this->webService->getRedirectResponse('userinv/index');
                        }
                        if ((((string)$key === 'client_id'))){
                            $form_array = [
                                'user_id'=>$user_id,    
                                'client_id'=>$value
                            ];
                            if ($formHydrator->populate($form, $form_array) && $form->isValid()
                                // Check that the user client does not exist    
                                                         && !$ucR->repoUserClientqueryCount($user_id, $value) > 0){
                                $this->userclientService->saveUserClient($user_client, $form_array);
                                $this->flash_message('info' , $this->translator->translate('i.record_successfully_updated'));
                                return $this->webService->getRedirectResponse('userinv/index');
                            }
                            
                            if ($ucR->repoUserClientqueryCount($user_id, $value) > 0) {
                                $this->flash_message('info' , $this->translator->translate('i.client_already_exists'));
                                return $this->webService->getRedirectResponse('userinv/index');
                            }
                            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                            $parameters['form'] = $form;
                        }
                    }
                }    
            }        
            return $this->viewRenderer->render('new', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     */
    public function view(CurrentRoute $currentRoute, UserClientRepository $userclientRepository) : 
        \Yiisoft\DataResponse\DataResponse|Response {
        $user_client = $this->userclient($currentRoute, $userclientRepository);
        if ($user_client) {
            $form = new UserClientForm($user_client);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['userclient/view', ['id' => $user_client->getId()]],
                'errors' => [],
                'form' => $form, 
                'userclient'=>$userclientRepository->repoUserClientquery($user_client->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('userclient/index');
    }   
        
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('userclient/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param UIR $uiR
     * @return UserInv|null
     */
    private function user(CurrentRoute $currentRoute, UIR $uiR): UserInv|null 
    {
        $user_id = $currentRoute->getArgument('user_id');       
        if (null!==$user_id) {
            $user = $uiR->repoUserInvUserIdquery($user_id);
            return $user;
        }
        return null;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @return UserClient|null
     */
    private function userclient(CurrentRoute $currentRoute,UserClientRepository $userclientRepository): UserClient|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $userclient = $userclientRepository->repoUserClientquery($id);
            return $userclient;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function userclients(UserClientRepository $userclientRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $userclients = $userclientRepository->findAllPreloaded();        
        return $userclients;
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
}

