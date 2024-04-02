<?php
declare(strict_types=1); 

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeService;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository;
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

final class AllowanceChargeController
{
    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private AllowanceChargeService $allowancechargeService;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        AllowanceChargeService $allowancechargeService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->webService = $webService;
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/allowancecharge')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/allowancecharge')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->allowancechargeService = $allowancechargeService;
        $this->translator = $translator;
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function add_allowance(Request $request, 
        FormHydrator $formHydrator,
        TaxRateRepository $tR
    ) : Response
    {
        $allowance_charge = new AllowanceCharge();        
        $form = new AllowanceChargeForm($allowance_charge);
        $peppol_arrays = new PeppolArrays();
        $allowances = $peppol_arrays->get_allowances_subset_array();
        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.add'),
            'action' => ['allowancecharge/add_allowance'],
            'allowances' => $allowances,
            'errors' => [],
            'form' => $form,
            'tax_rates' => $tR->findAllPreloaded(),
        ];
        
        /**
         * @var array $body
         */
        $body = $request->getParsedBody();
        // true => allowance; false => charge
        /**
         * @var bool $body['identifier']
         */
        $body['identifier'] = false;
        /**
         * @var string $body['reason']
         */
        $reason = $body['reason'] ?? '';
        /**
         * @var string $value
         */
        foreach ($allowances as $key => $value) {
            if ($value === $reason ) {
                /**
                 * @var string $body['reason_code']
                 */
                $body['reason_code'] = $key;
            }
        }
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->allowancechargeService->saveAllowanceCharge($allowance_charge, $body);
                $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('allowancecharge/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
        }
        return $this->viewRenderer->render('_form_allowance', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function add_charge(Request $request, 
                        FormHydrator $formHydrator,
                        TaxRateRepository $tR
    ) : Response
    {
        $allowance_charge = new AllowanceCharge();
        $form = new AllowanceChargeForm($allowance_charge);
        $peppol_arrays = new PeppolArrays();
        $charges = $peppol_arrays->get_charges_array();
        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.add'),
            'action' => ['allowancecharge/add_charge'],
            'errors' => [],
            'form' => $form,
            'charges' => $charges,       
            'tax_rates'=>$tR->findAllPreloaded()
        ];
        /**
         * @var array $body
         */
        $body = $request->getParsedBody();
        // true => allowance; false => charge
        /**
         * @var bool $body['identifier']
         */
        $body['identifier'] = true;
        /**
         * @var string $body['reason']
         */
        $reason = $body['reason'] ?? '';
        /**
         * @var array $value
         * @var string $value[0]
         */
        foreach ($charges as $key => $value) {
            if ($value[0] === $reason) {
                /**
                 * @var string $body['reason_code']
                 */
                $body['reason_code'] = $key;
            }
        }
        if ($request->getMethod() === Method::POST) {
            $form = new AllowanceChargeForm($allowance_charge);
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->allowancechargeService->saveAllowanceCharge($allowance_charge, $body);
                $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('allowancecharge/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
        }
        return $this->viewRenderer->render('_form_charge', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert() : string {
      return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
      [
          'flash'=>$this->flash,
          'errors' => [],
      ]);
    }
    
    /**
     * 
     * @param AllowanceChargeRepository $allowancechargeRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */    
    public function index(AllowanceChargeRepository $allowancechargeRepository, SettingRepository $settingRepository): Response
    {      
       $allowancecharges = $allowancechargeRepository->findAllPreloaded();
       $paginator = (new OffsetPaginator($allowancecharges));
       $parameters = [
          'canEdit' => $this->userService->hasPermission('editInv') ? true : false,    
          'allowancecharges' => $this->allowancecharges($allowancechargeRepository),
          'alert' => $this->alert(),
          'grid_summary'=> $settingRepository->grid_summary($paginator, $this->translator, (int)$settingRepository->get_setting('default_list_limit'), $this->translator->translate('invoice.invoice.allowance.or.charge'), ''), 
          'paginator'=>$paginator 
       ];
       return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowancechargeRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,AllowanceChargeRepository $allowancechargeRepository 
    ): Response {
        try {
            $allowancecharge = $this->allowancecharge($currentRoute, $allowancechargeRepository);
            if ($allowancecharge) {
                $this->allowancechargeService->deleteAllowanceCharge($allowancecharge);               
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('allowancecharge/index'); 
            }
            return $this->webService->getRedirectResponse('allowancecharge/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('allowancecharge/index'); 
        }
    }
       
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowancechargeRepository
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function edit_allowance(Request $request, CurrentRoute $currentRoute, 
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowancechargeRepository, 
        TaxRateRepository $tR

    ): Response {
        $allowancecharge = $this->allowancecharge($currentRoute, $allowancechargeRepository);
        $body = $request->getParsedBody() ?? [];
        if (null!==$allowancecharge) {
            $form = new AllowanceChargeForm($allowancecharge);
            $peppol_arrays = new PeppolArrays();
            $allowances = $peppol_arrays->get_allowances_subset_array();
            $parameters = [
                'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.edit.allowance'),
                'action' => ['allowancecharge/edit_allowance', ['id' => $allowancecharge->getId()]],
                'errors' => [],
                'form' => $form,
                'tax_rates'=> $tR->findAllPreloaded(),
                'allowances'=> $allowances,
            ];
            if ($request->getMethod() === Method::POST) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->allowancechargeService->saveAllowanceCharge($allowancecharge, $body);
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('allowancecharge/index');
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            }
            return $this->viewRenderer->render('_form_allowance', $parameters);
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowancechargeRepository
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function edit_charge(Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        AllowanceChargeRepository $allowancechargeRepository, 
                        TaxRateRepository $tR

    ): Response {
        $allowancecharge = $this->allowancecharge($currentRoute, $allowancechargeRepository);
        $body = $request->getParsedBody() ?? [];
        if (null!==$allowancecharge) {
            $form = new AllowanceChargeForm($allowancecharge);
            $peppol_arrays = new PeppolArrays();
            $charges = $peppol_arrays->get_charges_array();
            $parameters = [
                'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.edit.charge'),
                'action' => ['allowancecharge/edit_allowance', ['id' => $allowancecharge->getId()]],
                'errors' => [],
                'form' => $form,
                'tax_rates'=>$tR->findAllPreloaded(),
                'charges'=>$charges,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new AllowanceChargeForm($allowancecharge);
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                   /**
                    * @psalm-suppress PossiblyInvalidArgument $body
                    */
                    $this->allowancechargeService->saveAllowanceCharge($allowancecharge, $body);
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('allowancecharge/index');
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            }
            return $this->viewRenderer->render('_form_charge', $parameters);
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash{
        $this->flash->add($level, $message, true); 
        return $this->flash;
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowancechargeRepository
     * @return AllowanceCharge|null
     */
    private function allowancecharge(CurrentRoute $currentRoute,AllowanceChargeRepository $allowancechargeRepository) : AllowanceCharge|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $allowancecharge = $allowancechargeRepository->repoAllowanceChargequery($id);
            return $allowancecharge;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function allowancecharges(AllowanceChargeRepository $allowancechargeRepository) : \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $allowancecharges = $allowancechargeRepository->findAllPreloaded();        
        return $allowancecharges;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowancechargeRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,AllowanceChargeRepository $allowancechargeRepository) 
                         : \Yiisoft\DataResponse\DataResponse|Response {
        $allowancecharge = $this->allowancecharge($currentRoute, $allowancechargeRepository);
        if ($allowancecharge) {
            $form = new AllowanceChargeForm($allowancecharge);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['allowancecharge/view', ['id' => $allowancecharge->getId()]],
                'form' => $form,
                'allowancecharge'=>$allowancecharge,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }
}

