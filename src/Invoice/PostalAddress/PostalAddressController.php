<?php

declare(strict_types=1); 

namespace App\Invoice\PostalAddress;

use App\Invoice\Entity\PostalAddress;
use App\Invoice\Client\ClientRepository;
use App\Invoice\PostalAddress\PostalAddressForm;
use App\Invoice\PostalAddress\PostalAddressService;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\Setting\SettingRepository;

use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class PostalAddressController
{
    private Flash $flash;
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private PostalAddressService $postaladdressService;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        PostalAddressService $postaladdressService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/postaladdress')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/postaladdress')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->webService = $webService;
        $this->postaladdressService = $postaladdressService;
        $this->translator = $translator;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepo
     * @return Response
     */
    public function add(CurrentRoute $currentRoute, 
                        Request $request, 
                        FormHydrator $formHydrator
    ) : Response
    {
        $client_id = $currentRoute->getArgument('client_id');
        $postalAddress = new PostalAddress();
        $form = new PostalAddressForm($this->translator, $postalAddress, (int)$client_id);
        $parameters = [
            'canEdit' => ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) ? true : false,
            'client_id' => $client_id,
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['postaladdress/add', ['client_id' => $client_id]],
            'errors' => [],
            'form' => $form
        ];
        
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->postaladdressService->savePostalAddress($postalAddress, $body);
                return $this->webService->getRedirectResponse('postaladdress/index');
            }
            $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
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
     * @param CurrentRoute $currentRoute
     * @param PostalAddressRepository $postaladdressRepository
     * @param SettingRepository $settingRepository
     * @param ClientRepository $cR
     * @return Response
     */
    public function index(CurrentRoute $currentRoute, PostalAddressRepository $postaladdressRepository, SettingRepository $settingRepository, ClientRepository $cR): Response
    {      
        $page = $currentRoute->getArgument('page', '1');
        $postaladdresses = $this->postaladdresses($postaladdressRepository); 
        $paginator = (new OffsetPaginator($postaladdresses))
        ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
        ->withCurrentPage((int)$page)        
        ->withToken(PageToken::next((string)$page)); 
      $parameters = [
        'canEdit' => ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) ? true : false,  
        'postaladdresses' => $postaladdresses,
        'alert' => $this->alert(),
        'paginator' => $paginator,
        'max' => (int)$settingRepository->get_setting('default_list_limit'),  
        'cR' => $cR
      ];         
      return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param PostalAddressRepository $postaladdressRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,PostalAddressRepository $postaladdressRepository)
                           : Response 
    {
    try {
            $postaladdress = $this->postaladdress($currentRoute, $postaladdressRepository);
            if ($postaladdress) {
                $this->postaladdressService->deletePostalAddress($postaladdress);               
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('postaladdress/index'); 
            }
            return $this->webService->getRedirectResponse('postaladdress/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('postaladdress/index'); 
        }
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param PostalAddressRepository $postalAddressRepository
     * @return Response
     */    
    public function edit(Request $request, CurrentRoute $currentRoute, 
                         FormHydrator $formHydrator,
                         PostalAddressRepository $postalAddressRepository            

    ): Response {
        $postalAddress = $this->postaladdress($currentRoute, $postalAddressRepository);
        if ($postalAddress){
            $form = new PostalAddressForm($this->translator, $postalAddress, (int)$postalAddress->getClient_id());
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['postaladdress/edit', ['id' => $postalAddress->getId()]],
                'errors' => [],
                'form' => $form
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody();
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->postaladdressService->savePostalAddress($postalAddress, $body);
                    return $this->webService->getRedirectResponse('postaladdress/index');
                }
                $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('postaladdress/index');
    }
       
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param PostalAddressRepository $postaladdressRepository
     * @return PostalAddress|null
     */
    private function postaladdress(CurrentRoute $currentRoute,PostalAddressRepository $postaladdressRepository) : PostalAddress|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            /* @var PostalAddress $postaladdress */
            $postaladdress = $postaladdressRepository->repoPostalAddressLoadedquery($id);
            return $postaladdress;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function postaladdresses(PostalAddressRepository $postaladdressRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $postaladdresses = $postaladdressRepository->findAllPreloaded();        
        return $postaladdresses;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param PostalAddressRepository $postalAddressRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,
                         PostalAddressRepository $postalAddressRepository) 
            : \Yiisoft\DataResponse\DataResponse|Response 
    {
        $postalAddress = $this->postaladdress($currentRoute, $postalAddressRepository); 
        if ($postalAddress) {
            $form = new PostalAddressForm($this->translator, $postalAddress, (int)$postalAddress->getClient_id());
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['postaladdress/view', ['id' => $postalAddress->getId()]],
                'form' => $form,
                'postaladdress'=>$postalAddress,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('postaladdress/index');
    }
}

