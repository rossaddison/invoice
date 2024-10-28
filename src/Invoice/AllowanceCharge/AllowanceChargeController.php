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
use Yiisoft\Yii\View\Renderer\ViewRenderer;

use \Exception;

final class AllowanceChargeController
{
    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private AllowanceChargeService $allowanceChargeService;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,            
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        AllowanceChargeService $allowanceChargeService,
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
        $this->allowanceChargeService = $allowanceChargeService;
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
        $allowanceCharge = new AllowanceCharge();        
        $form = new AllowanceChargeForm($allowanceCharge);
        $peppolArrays = new PeppolArrays();
        $allowances = $peppolArrays->getAllowancesSubsetArray();
        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.add'),
            'actionName' => 'allowancecharge/add_allowance',
            'actionArguments' => [],
            'allowances' => $allowances,
            'errors' => [],
            'form' => $form,
            'taxRates' => $tR->findAllPreloaded(),
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
                $this->allowanceChargeService->saveAllowanceCharge($allowanceCharge, $body);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('allowancecharge/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
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
        $allowanceCharge = new AllowanceCharge();
        $form = new AllowanceChargeForm($allowanceCharge);
        $peppolArrays = new PeppolArrays();
        $charges = $peppolArrays->getChargesArray();
        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.add'),
            'actionName' => 'allowancecharge/add_charge',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'charges' => $charges,       
            'taxRates' => $tR->findAllPreloaded()
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
            $form = new AllowanceChargeForm($allowanceCharge);
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $this->allowanceChargeService->saveAllowanceCharge($allowanceCharge, $body);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('allowancecharge/index');
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_form_charge', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert() : string {
      return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
      [
          'flash' => $this->flash,
          'errors' => [],
      ]);
    }
    
    /**
     * 
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */    
    public function index(AllowanceChargeRepository $allowanceChargeRepository, SettingRepository $settingRepository): Response
    {      
       $allowanceCharges = $allowanceChargeRepository->findAllPreloaded();
       $paginator = (new OffsetPaginator($allowanceCharges));
       $parameters = [
          'canEdit' => $this->userService->hasPermission('editInv') ? true : false,    
          'allowanceCharges' => $this->allowanceCharges($allowanceChargeRepository),
          'alert' => $this->alert(),
          'paginator' => $paginator 
       ];
       return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, AllowanceChargeRepository $allowanceChargeRepository 
    ): Response {
        try {
            $allowanceCharge = $this->allowanceCharge($currentRoute, $allowanceChargeRepository);
            if ($allowanceCharge) {
                $this->allowanceChargeService->deleteAllowanceCharge($allowanceCharge);               
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('allowancecharge/index'); 
            }
            return $this->webService->getRedirectResponse('allowancecharge/index'); 
	} catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('allowancecharge/index'); 
        }
    }
       
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function edit_allowance(Request $request, CurrentRoute $currentRoute, 
        FormHydrator $formHydrator,
        AllowanceChargeRepository $allowanceChargeRepository, 
        TaxRateRepository $tR

    ): Response {
        $allowanceCharge = $this->allowanceCharge($currentRoute, $allowanceChargeRepository);
        $body = $request->getParsedBody() ?? [];
        if (null!==$allowanceCharge) {
            $form = new AllowanceChargeForm($allowanceCharge);
            $peppolArrays = new PeppolArrays();
            $allowances = $peppolArrays->getAllowancesSubsetArray();
            $parameters = [
                'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.edit.allowance'),
                'actionName' => 'allowancecharge/edit_allowance', 
                'actionArguments' => ['id' => $allowanceCharge->getId()],
                'errors' => [],
                'form' => $form,
                'taxRates'=> $tR->findAllPreloaded(),
                'allowances' => $allowances,
            ];
            if ($request->getMethod() === Method::POST) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this->allowanceChargeService->saveAllowanceCharge($allowanceCharge, $body);
                    $this->flashMessage('info', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('allowancecharge/index');
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->viewRenderer->render('_form_allowance', $parameters);
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @param TaxRateRepository $tR
     * @return Response
     */
    public function edit_charge(Request $request, CurrentRoute $currentRoute, 
                        FormHydrator $formHydrator,
                        AllowanceChargeRepository $allowanceChargeRepository, 
                        TaxRateRepository $tR

    ): Response {
        $allowanceCharge = $this->allowanceCharge($currentRoute, $allowanceChargeRepository);
        $body = $request->getParsedBody() ?? [];
        if (null!==$allowanceCharge) {
            $form = new AllowanceChargeForm($allowanceCharge);
            $peppolArrays = new PeppolArrays();
            $charges = $peppolArrays->getChargesArray();
            $parameters = [
                'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.edit.charge'),
                'actionName' => 'allowancecharge/edit_allowance', 
                'actionArguments' => ['id' => $allowanceCharge->getId()],
                'errors' => [],
                'form' => $form,
                'taxRates' => $tR->findAllPreloaded(),
                'charges' => $charges,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new AllowanceChargeForm($allowanceCharge);
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                   $this->allowanceChargeService->saveAllowanceCharge($allowanceCharge, $body);
                    $this->flashMessage('info', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('allowancecharge/index');
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->viewRenderer->render('_form_charge', $parameters);
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }
    
     /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flashMessage(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return AllowanceCharge|null
     */
    private function allowanceCharge(CurrentRoute $currentRoute, AllowanceChargeRepository $allowanceChargeRepository) : AllowanceCharge|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $allowanceCharge = $allowanceChargeRepository->repoAllowanceChargequery($id);
            return $allowanceCharge;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function allowanceCharges(AllowanceChargeRepository $allowanceChargeRepository) : \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $allowanceCharges = $allowanceChargeRepository->findAllPreloaded();        
        return $allowanceCharges;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param AllowanceChargeRepository $allowanceChargeRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, AllowanceChargeRepository $allowanceChargeRepository) 
                         : \Yiisoft\DataResponse\DataResponse|Response {
        $allowanceCharge = $this->allowanceCharge($currentRoute, $allowanceChargeRepository);
        if ($allowanceCharge) {
            $form = new AllowanceChargeForm($allowanceCharge);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'allowancecharge/view', 
                'actionArguments' => ['id' => $allowanceCharge->getId()],
                'form' => $form,
                'allowanceCharge' => $allowanceCharge,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('allowancecharge/index');
    }
}

