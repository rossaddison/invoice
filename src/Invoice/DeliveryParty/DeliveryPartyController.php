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

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
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
                        FormHydrator $formHydrator) : Response
    {
        $deliveryParty = new DeliveryParty();
        $form = new DeliveryPartyForm($deliveryParty);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('i.add'),
            'action' => ['deliveryparty/add'],
            'errors' => [],
            'form' => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                $this->deliverypartyService->saveDeliveryParty(new DeliveryParty(), $body);
                return $this->webService->getRedirectResponse('deliveryparty/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
       
    /**
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @param SettingRepository $settingRepository
     * @param DeliveryPartyService $service
     * @return Response
     */
    public function index(DeliveryPartyRepository $deliverypartyRepository, 
                          SettingRepository $settingRepository): Response
    {      
        $deliveryparties = $this->deliveryparties($deliverypartyRepository);
        $paginator = (new OffsetPaginator($deliveryparties));
        $parameters = [
          'canEdit' => $this->rbac(),
          'grid_summary' => $settingRepository->grid_summary($paginator, $this->translator, (int)$settingRepository->get_setting('default_list_limit'),
                  $this->translator->translate('invoice.invoice.delivery.party'), ''),  
          'paginator' => $paginator,  
          'deliveryparties' => $deliveryparties,
          'alert'=> $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,DeliveryPartyRepository $deliverypartyRepository 
    ): Response {
        try {
            $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
            if ($deliveryparty) {
                $this->deliverypartyService->deleteDeliveryParty($deliveryparty);               
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('deliveryparty/index'); 
            }
            return $this->webService->getRedirectResponse('deliveryparty/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('deliveryparty/index'); 
        }
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */    
    public function edit(Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        DeliveryPartyRepository $deliverypartyRepository             

    ): Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty){
            $form = new DeliveryPartyForm($deliveryparty);
            $parameters = [
                'canEdit' => $this->rbac(),
                'form' => $form,
                'title' => $this->translator->translate('i.edit'),
                'action' => ['deliveryparty/edit', ['id' => $deliveryparty->getId()]],
                'errors' => [],
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body 
                     */
                    $this->deliverypartyService->saveDeliveryParty($deliveryparty, $body);
                    return $this->webService->getRedirectResponse('deliveryparty/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
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
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('clientnote/index');
        }
        return $canEdit;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,DeliveryPartyRepository $deliverypartyRepository): \Yiisoft\DataResponse\DataResponse|Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository); 
        if ($deliveryparty) {
            $form = new DeliveryPartyForm($deliveryparty);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['deliveryparty/view', ['id' => $deliveryparty->getId()]],
                'form' => $form,
                'deliveryparty'=>$deliveryparty,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
    }
}

