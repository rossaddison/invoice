<?php

declare(strict_types=1); 

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\Unit;
use App\Invoice\Entity\UnitPeppol;
use App\Invoice\Helpers\Peppol\Peppol_UNECERec20_11e;
use App\Invoice\UnitPeppol\UnitPeppolService;
use App\Invoice\UnitPeppol\UnitPeppolRepository;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\Unit\UnitRepository;
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
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

use \Exception;

final class UnitPeppolController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private UnitPeppolService $unitpeppolService;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        UnitPeppolService $unitpeppolService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/unitpeppol')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->unitpeppolService = $unitpeppolService;
        $this->translator = $translator;
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param UnitRepository $unitRepository
     * @return Response
     */
    public function add(Request $request, 
                        FormHydrator $formHydrator,                        
                        UnitRepository $unitRepository
    ) : Response
    {
        $enece = new Peppol_UNECERec20_11e();
        /** @var array $enece_array */
        $enece_array = $enece->getUNECERec20_11e();
        $units = $unitRepository->findAllPreloaded();
        $unitPeppol = new UnitPeppol();
        $form = new UnitPeppolForm($unitPeppol);
        $parameters = [
            'title' => $this->translator->translate('invoice.unit.peppol.add'),
            'actionName' => 'unitpeppol/add',
            'actionArguments' => [],
            'form' => $form,
            'errors' => [],
            'eneces' => $enece_array,
            'optionsDataEneces' => $this->optionsDataEneces($enece_array),
            'optionsDataUnits' => $this->optionsDataUnits($units)
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            /** 
             * @var string $body['code']
             */
            $key = (int)$body['code'];
            /**
             *  @var array $enece_array[$key] 
             *  @var string $enece_array[$key]['Name'] 
             *  @psalm-suppress PossiblyInvalidArrayAssignment $body['name'] 
             */ 
            $body['name'] = $enece_array[$key]['Name'];
            
            /** 
             * @var string $enece_array[$key]['Description']
             * @var string $body['description']
             * @psalm-suppress PossiblyInvalidArrayAssignment $body['description']  
             */       
            if (array_key_exists('Description', $enece_array[$key]) && !isset($body['description'])) {
                $body['description'] = $enece_array[$key]['Description'];
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->unitpeppolService->saveUnitPeppol($unitPeppol, $body);
                return $this->webService->getRedirectResponse('unitpeppol/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
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
     * @param UnitPeppolRepository $unitpeppolRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(UnitPeppolRepository $unitpeppolRepository, SettingRepository $settingRepository): Response
    {      
      $paginator = new OffsetPaginator($this->unitpeppols($unitpeppolRepository));
      $parameters = [
          'alert' => $this->alert(),  
          'unitpeppols' => $this->unitpeppols($unitpeppolRepository),
          'grid_summary'=> $settingRepository->grid_summary($paginator, $this->translator, (int)$settingRepository->get_setting('default_list_limit'), $this->translator->translate('invoice.unit.peppol'), ''),
          'paginator'=> $paginator
      ];
      return $this->viewRenderer->render('index', $parameters);
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitPeppolRepository $unitpeppolRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, UnitPeppolRepository $unitpeppolRepository 
    ): Response {
        try {
            $unitpeppol = $this->unitpeppol($currentRoute, $unitpeppolRepository);
            if ($unitpeppol) {
                $this->unitpeppolService->deleteUnitPeppol($unitpeppol);               
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('unitpeppol/index'); 
            }
            return $this->webService->getRedirectResponse('unitpeppol/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('unitpeppol/index'); 
        }
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param UnitPeppolRepository $unitpeppolRepository
     * @param SettingRepository $settingRepository
     * @param UnitRepository $unitRepository
     * @return Response
     */    
    public function edit(Request $request, 
                         CurrentRoute $currentRoute, 
                         FormHydrator $formHydrator,
                         UnitPeppolRepository $unitpeppolRepository,                   
                         UnitRepository $unitRepository): Response 
    {
        $unitPeppol = $this->unitpeppol($currentRoute, $unitpeppolRepository);
        $units = $unitRepository->findAllPreloaded();
        $enece = new Peppol_UNECERec20_11e();
        $enece_array = $enece->getUNECERec20_11e();
        if ($unitPeppol){
            $form = new UnitPeppolForm($unitPeppol);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'unitpeppol/edit', 
                'actionArguments' => ['id' => $unitPeppol->getId()],
                'eneces' => $enece_array,
                'errors' => [],
                'form' => $form,
                'optionsDataEneces' => $this->optionsDataEneces($enece_array),
                'optionsDataUnits' => $this->optionsDataUnits($units)
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody();
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->unitpeppolService->saveUnitPeppol($unitPeppol, $body);
                    return $this->webService->getRedirectResponse('unitpeppol/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('unitpeppol/index');
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitPeppolRepository $unitpeppolRepository
     * @return UnitPeppol|null
     */
    private function unitpeppol(CurrentRoute $currentRoute,UnitPeppolRepository $unitpeppolRepository) : UnitPeppol|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $unitpeppol = $unitpeppolRepository->repoUnitPeppolLoadedquery($id);
            return $unitpeppol;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function unitpeppols(UnitPeppolRepository $unitpeppolRepository) : \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $unitpeppols = $unitpeppolRepository->findAllPreloaded();        
        return $unitpeppols;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @param UnitPeppolRepository $unitpeppolRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,
                         UnitRepository $unitRepository,
                         UnitPeppolRepository $unitpeppolRepository): \Yiisoft\DataResponse\DataResponse|Response {
        $unitPeppol = $this->unitpeppol($currentRoute, $unitpeppolRepository);
        $units = $unitRepository->findAllPreloaded();
        $enece = new Peppol_UNECERec20_11e();
        $eneceArray = $enece->getUNECERec20_11e();
        if ($unitPeppol) {
            $form = new UnitPeppolForm($unitPeppol);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'unitpeppol/view',
                'actionArguments' => ['id' => $unitPeppol->getId()],
                'form' => $form,
                'eneces' => $eneceArray,
                'optionsDataEneces' => $this->optionsDataEneces($eneceArray),
                'optionsDataUnits' => $this->optionsDataUnits($units)
            ];        
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('unitpeppol/index');
    }
    
    /**
     * @param array $eneces
     * @return array
     */
    public function optionsDataEneces(array $eneces) : array
    {
        $optionsDataEneces = [];
        /**
         * @var string $key
         * @var array $value
         */
        foreach ($eneces as $key => $value)
        {
            /**
             * @var array $eneces[$key]
             * @var string $eneces[$key]['Description']
             * @var string $eneces[$key]['Id']
             * @var string $eneces[$key]['Name']
             * @var 
             */
            $description = (array_key_exists('Description', $eneces[$key]) ? $eneces[$key]['Description'] : '');
            $cell = ' '.$eneces[$key]['Id'].' -------- '.$eneces[$key]['Name'] .' ------ '. $description;
            /**
             * @var int $value['Id']
             */
            $optionsDataEneces[$value['Id']] = $cell;  
        }   
        return $optionsDataEneces;
    } 
    
    /**
     * @param EntityReader $units
     * @return array
     */
    public function optionsDataUnits(EntityReader $units) : array
    {
        $optionsDataUnits = [];
        /**
         * @var Unit $unit
         */
        foreach ($units as $unit)
        {
            $key = $unit->getUnit_id();
            null!==$key ? $optionsDataUnits[$key] = $unit->getUnit_name().' '. $unit->getUnit_name_plrl() : ''; 
        }   
        return $optionsDataUnits;
    }        
}

