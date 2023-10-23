<?php
declare(strict_types=1); 

namespace App\Invoice\InvAllowanceCharge;

use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class InvAllowanceChargeController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private InvAllowanceChargeService $invallowancechargeService;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        InvAllowanceChargeService $invallowancechargeService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer;
        $this->userService = $userService;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/invallowancecharge')
                                                ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/invallowancecharge')
                                               ->withLayout('@views/layout/invoice.php');
        }
        $this->webService = $webService;
        $this->invallowancechargeService = $invallowancechargeService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param AllowanceChargeRepository $allowance_chargeRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $settingRepository,                        
                        AllowanceChargeRepository $allowance_chargeRepository
    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['invallowancecharge/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
            'allowance_charges'=>$allowance_chargeRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new InvAllowanceChargeForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->invallowancechargeService->saveInvAllowanceCharge(new InvAllowanceCharge(),$form);
                return $this->webService->getRedirectResponse('invallowancecharge/index');
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
     * @param InvAllowanceCharge $invallowancecharge     
     * @return array
     */
    private function body(InvAllowanceCharge $invallowancecharge) : array {
        $body = [
          'id'=>$invallowancecharge->getId(),
          'inv_id'=>$invallowancecharge->getInv_id(),
          'allowance_charge_id'=>$invallowancecharge->getAllowance_charge_id(),
          'amount'=>$invallowancecharge->getAmount(),
          'vat'=>$invallowancecharge->getVat(),  
                
        ];
        return $body;
    }
    
    /**
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(InvAllowanceChargeRepository $invallowancechargeRepository, SettingRepository $settingRepository): Response
    {      
      $invallowancecharges = $this->invallowancecharges($invallowancechargeRepository);
      $paginator = (new OffsetPaginator($invallowancecharges));
       $parameters = [
         'paginator' => $paginator,
         'grid_summary'=> $settingRepository->grid_summary($paginator, $this->translator, (int)$settingRepository->get_setting('default_list_limit'), $this->translator->translate('invoice.invoice.allowance.or.charge'), ''),    
         'alert'=> $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
        
    /**
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,InvAllowanceChargeRepository $invallowancechargeRepository 
    ): Response {
        try {
            $invallowancecharge = $this->invallowancecharge($currentRoute, $invallowancechargeRepository);
            if ($invallowancecharge) {
                $this->invallowancechargeService->deleteInvAllowanceCharge($invallowancecharge);               
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('invallowancecharge/index'); 
            }
            return $this->webService->getRedirectResponse('invallowancecharge/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('invallowancecharge/index'); 
        }
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @param SettingRepository $settingRepository
     * @param AllowanceChargeRepository $allowance_chargeRepository
     * @return Response
     */    
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        InvAllowanceChargeRepository $invallowancechargeRepository, 
                        SettingRepository $settingRepository,                        
                        AllowanceChargeRepository $allowance_chargeRepository
    ): Response {
        $invallowancecharge = $this->invallowancecharge($currentRoute, $invallowancechargeRepository);
        if ($invallowancecharge){
            $parameters = [
                'title' => $this->translator->translate('invoice.invoice.allowance.or.charge'),
                'action' => ['invallowancecharge/edit', ['id' => $invallowancecharge->getId()]],
                'errors' => [],
                'body' => $this->body($invallowancecharge),
                'head'=>$head,
                'allowancecharge'=>$invallowancecharge->getAllowanceCharge(),                
                's'=>$settingRepository,
                'allowance_charges'=>$allowance_chargeRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new InvAllowanceChargeForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->invallowancechargeService->saveInvAllowanceCharge($invallowancecharge,$form);
                    return $this->webService->getRedirectResponse('invallowancecharge/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('invallowancecharge/index');
    }
        
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @return InvAllowanceCharge|null
     */
    private function invallowancecharge(CurrentRoute $currentRoute,InvAllowanceChargeRepository $invallowancechargeRepository) : InvAllowanceCharge|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $invallowancecharge = $invallowancechargeRepository->repoInvAllowanceChargeLoadedquery($id);
            return $invallowancecharge;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function invallowancecharges(InvAllowanceChargeRepository $invallowancechargeRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $invallowancecharges = $invallowancechargeRepository->findAllPreloaded();        
        return $invallowancecharges;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,InvAllowanceChargeRepository $invallowancechargeRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $invallowancecharge = $this->invallowancecharge($currentRoute, $invallowancechargeRepository); 
        if ($invallowancecharge) {
          $parameters = [
              'title' => $settingRepository->trans('view'),
              'action' => ['invallowancecharge/view', ['id' => $invallowancecharge->getId()]],
              'errors' => [],
              'body' => $this->body($invallowancecharge),
              'invallowancecharge'=>$invallowancecharge,
          ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('invallowancecharge/index');
    }
}

