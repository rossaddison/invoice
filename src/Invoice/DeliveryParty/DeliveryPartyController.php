<?php

declare(strict_types=1); 

namespace App\Invoice\DeliveryParty;

use App\Invoice\Entity\DeliveryParty;
use App\Invoice\DeliveryParty\DeliveryPartyService;
use App\Invoice\DeliveryParty\DeliveryPartyRepository;

use App\Invoice\Setting\SettingRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class DeliveryPartyController
{
    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private DeliveryPartyService $deliverypartyService;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        DeliveryPartyService $deliverypartyService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/deliveryparty')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->deliverypartyService = $deliverypartyService;
        $this->translator = $translator;
    }
    
    /**
     * 
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
            'action' => ['deliveryparty/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new DeliveryPartyForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->deliverypartyService->saveDeliveryParty(new DeliveryParty(),$form);
                return $this->webService->getRedirectResponse('deliveryparty/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
        
    /**
     * @param DeliveryParty $deliveryparty
     * @return array
     */
    private function body(DeliveryParty $deliveryparty) : array {
        $body = [
          'id'=>$deliveryparty->getId(),
          'party_name'=>$deliveryparty->getPartyName()
        ];
        return $body;
    }
    
    /**
     * 
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param DeliveryPartyService $service
     * @return Response
     */
    public function index(DeliveryPartyRepository $deliverypartyRepository, SettingRepository $settingRepository, Request $request, DeliveryPartyService $service): Response
    {      
        $parameters = [
          'deliveryparties' => $this->deliveryparties($deliverypartyRepository),
          'alert'=> $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,DeliveryPartyRepository $deliverypartyRepository 
    ): Response {
        try {
            $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
            if ($deliveryparty) {
                $this->deliverypartyService->deleteDeliveryParty($deliveryparty);               
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('deliveryparty/index'); 
            }
            return $this->webService->getRedirectResponse('deliveryparty/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('deliveryparty/index'); 
        }
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */    
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        DeliveryPartyRepository $deliverypartyRepository, 
                        SettingRepository $settingRepository,                        

    ): Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty){
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['deliveryparty/edit', ['id' => $deliveryparty->getId()]],
                'errors' => [],
                'body' => $this->body($deliveryparty),
                'head'=>$head,
                's'=>$settingRepository,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new DeliveryPartyForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->deliverypartyService->saveDeliveryParty($deliveryparty, $form);
                    return $this->webService->getRedirectResponse('deliveryparty/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
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
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return DeliveryParty|null
     */
    private function deliveryparty(CurrentRoute $currentRoute,DeliveryPartyRepository $deliverypartyRepository) : DeliveryParty|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $deliveryparty = $deliverypartyRepository->repoDeliveryPartyquery($id);
            return $deliveryparty;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function deliveryparties(DeliveryPartyRepository $deliverypartyRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $deliveryparties = $deliverypartyRepository->findAllPreloaded();        
        return $deliveryparties;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,DeliveryPartyRepository $deliverypartyRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository); 
        if ($deliveryparty) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['deliveryparty/view', ['id' => $deliveryparty->getId()]],
                'errors' => [],
                'body' => $this->body($deliveryparty),
                'deliveryparty'=>$deliveryparty,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
    }
}

