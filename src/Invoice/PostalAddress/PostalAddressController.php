<?php

declare(strict_types=1); 

namespace App\Invoice\PostalAddress;

use App\Invoice\Entity\PostalAddress;
use App\Invoice\Client\ClientRepository;
use App\Invoice\PostalAddress\PostalAddressService;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\Setting\SettingRepository;

use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
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
     * 
     * @param CurrentRoute $currentRoute
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepo
     * @return Response
     */
    public function add(CurrentRoute $currentRoute, ViewRenderer $head, Request $request, 
                       FormHydrator $formHydrator, ClientRepository $clientRepo
    ) : Response
    {
        $client_id = $currentRoute->getArgument('client_id');
        $parameters = [
            'canEdit' => ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) ? true : false,
            'client_id' => $client_id,
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['postaladdress/add',['client_id'=>$client_id]],
            'errors' => [],
            'body' => $request->getParsedBody(),
            'head'=>$head,
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new PostalAddressForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->postaladdressService->savePostalAddress(new PostalAddress(), $form);
                return $this->webService->getRedirectResponse('postaladdress/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
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
    
    /**
     * @param PostalAddress $postaladdress
     * @return array
     */
    private function body(PostalAddress $postaladdress) : array {
        $body = [
          'id'=>$postaladdress->getId(),
          'client_id'=>$postaladdress->getClient_id(),
          'street_name'=>$postaladdress->getStreet_name(),
          'additional_street_name'=>$postaladdress->getAdditional_street_name(),
          'building_number'=>$postaladdress->getBuilding_number(),
          'city_name'=>$postaladdress->getCity_name(),
          'postalzone'=>$postaladdress->getPostalzone(),
          'countrysubentity'=>$postaladdress->getCountrysubentity(),
          'country'=>$postaladdress->getCountry()
        ];
        return $body;
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
        $page = (int)$currentRoute->getArgument('page', '1');
        $postaladdresses = $this->postaladdresses($postaladdressRepository); 
        $paginator = (new OffsetPaginator($postaladdresses))
        ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
        ->withCurrentPage($page)        
        ->withNextPageToken((string) $page); 
      $parameters = [
        'canEdit' => ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) ? true : false,  
        's'=>$settingRepository,
        'postaladdresses' => $postaladdresses,
        'alert'=> $this->alert(),
        'paginator'=>$paginator,
        'max'=>(int)$settingRepository->get_setting('default_list_limit'),  
        'cR' => $cR
      ];         
      return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param PostalAddressRepository $postaladdressRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,PostalAddressRepository $postaladdressRepository 
    ): Response {
        try {
            $postaladdress = $this->postaladdress($currentRoute, $postaladdressRepository);
            if ($postaladdress) {
                $this->postaladdressService->deletePostalAddress($postaladdress);               
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('postaladdress/index'); 
            }
            return $this->webService->getRedirectResponse('postaladdress/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('postaladdress/index'); 
        }
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param PostalAddressRepository $postaladdressRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */    
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                       FormHydrator $formHydrator,
                        PostalAddressRepository $postaladdressRepository, 
                        SettingRepository $settingRepository                        

    ): Response {
        $postaladdress = $this->postaladdress($currentRoute, $postaladdressRepository);
        if ($postaladdress){
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['postaladdress/edit', ['id' => $postaladdress->getId()]],
                'errors' => [],
                'body' => $this->body($postaladdress),
                'head'=>$head  
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new PostalAddressForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->postaladdressService->savePostalAddress($postaladdress,$form);
                    return $this->webService->getRedirectResponse('postaladdress/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
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
     * @param PostalAddressRepository $postaladdressRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,
                         PostalAddressRepository $postaladdressRepository,
                         SettingRepository $settingRepository,
                         ): \Yiisoft\DataResponse\DataResponse|Response {
        $postaladdress = $this->postaladdress($currentRoute, $postaladdressRepository); 
        if ($postaladdress) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['postaladdress/view', ['id' => $postaladdress->getId()]],
                'errors' => [],
                'body' => $this->body($postaladdress),
                'postaladdress'=>$postaladdress,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('postaladdress/index');
    }
}

