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
use Yiisoft\FormModel\FormHydrator;
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
     * @param Request $request
     * @param CurrentRoute $currentRoute 
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response
     */
    public function add(Request $request,
                        CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,                        
                        AllowanceChargeRepository $allowanceChargeRepository
    ) : Response
    {
        $invAllowanceCharge = new InvAllowanceCharge();
        $inv_id = $currentRoute->getArgument('inv_id');
        $form = new InvAllowanceChargeForm($invAllowanceCharge, (int)$inv_id);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['invallowancecharge/add', ['inv_id' => $inv_id]],
            'errors' => [],
            'form' => $form,
            'optionsDataAllowanceCharges' => $allowanceChargeRepository->optionsDataAllowanceCharges(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $body['inv_id'] = $inv_id;
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->invallowancechargeService->saveInvAllowanceCharge($invAllowanceCharge, $body);
                    return $this->webService->getRedirectResponse('invallowancecharge/index');
                }
                $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                $parameters['form'] = $form;
            } // is_array    
        }
        return $this->viewRenderer->render('modal_add_allowance_charge_form', $parameters);
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
     * @param CurrentRoute $currentRoute
     * @param InvAllowanceChargeRepository $invallowancechargeRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,InvAllowanceChargeRepository $invallowancechargeRepository 
    ): Response {
        try {
            $invallowancecharge = $this->invallowancecharge($currentRoute, $invallowancechargeRepository);
            if ($invallowancecharge) {
                $this->invallowancechargeService->deleteInvAllowanceCharge($invallowancecharge);               
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('invallowancecharge/index'); 
            }
            return $this->webService->getRedirectResponse('invallowancecharge/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('invallowancecharge/index'); 
        }
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param InvAllowanceChargeRepository $invAllowanceChargeRepository
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response
     */    
    public function edit(Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        InvAllowanceChargeRepository $invAllowanceChargeRepository, 
                        AllowanceChargeRepository $allowanceChargeRepository
    ): Response {
        $invAllowanceCharge = $this->invallowancecharge($currentRoute, $invAllowanceChargeRepository);
        if ($invAllowanceCharge){
            $inv_id = $invAllowanceCharge->getInv_id();
            $form = new InvAllowanceChargeForm($invAllowanceCharge, (int)$inv_id);
            $parameters = [
                'title' => $this->translator->translate('invoice.invoice.allowance.or.charge'),
                'action' => ['invallowancecharge/edit', ['id' => $invAllowanceCharge->getId()]],
                'errors' => [],
                'form' => $form,
                'optionsDataAllowanceCharges' => $allowanceChargeRepository->optionsDataAllowanceCharges(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->invallowancechargeService->saveInvAllowanceCharge($invAllowanceCharge, $body);
                    return $this->webService->getRedirectResponse('invallowancecharge/index');
                }
                $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                $parameters['form'] = $form;
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
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,
                         InvAllowanceChargeRepository $invallowancechargeRepository,
                         AllowanceChargeRepository $allowanceChargeRepository): \Yiisoft\DataResponse\DataResponse|Response {
        $invAllowanceCharge = $this->invallowancecharge($currentRoute, $invallowancechargeRepository); 
        if ($invAllowanceCharge) {
          $inv_id = $invAllowanceCharge->getInv_id();  
          $form = new InvAllowanceChargeForm($invAllowanceCharge, (int)$inv_id);  
          $parameters = [
              'title' => $this->translator->translate('i.view'),
              'action' => ['invallowancecharge/view', ['id' => $invAllowanceCharge->getId()]],
              'form' => $form,
              'optionsDataAllowanceCharges' => $allowanceChargeRepository->optionsDataAllowanceCharges(),
          ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('invallowancecharge/index');
    }
}

