<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Invoice\Entity\Unit;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Unit\UnitRepository;
use App\Invoice\UnitPeppol\UnitPeppolRepository;
use App\Service\WebControllerService;
use App\User\UserService;

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

final class UnitController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UnitService $unitService;    
    private UserService $userService;
    private TranslatorInterface $translator;

    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UnitService $unitService,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/unit')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->unitService = $unitService;
        $this->userService = $userService;
        $this->translator = $translator;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @param UnitPeppolRepository $upR
     * @param SettingRepository $settingRepository
     */
    public function index(CurrentRoute $currentRoute, UnitRepository $unitRepository, UnitPeppolRepository $upR, SettingRepository $settingRepository): \Yiisoft\DataResponse\DataResponse
    {
        $units = $this->units($unitRepository);
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        $paginator = (new OffsetPaginator($units))
            ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
            ->withCurrentPage($pageNum);
        $parameters = [
            'alert'=> $this->alert(),
            'paginator'=> $paginator,
            's'=> $settingRepository,
            'upR'=>$upR,
            'units' => $units, 
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param Request $request
     * @param SettingRepository $settingRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, SettingRepository $settingRepository, FormHydrator $formHydrator): Response
    {
        $parameters = [
            'title' => $settingRepository->trans('add'),
            'action' => ['unit/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new UnitForm();
            $unit = new Unit();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->unitService->saveUnit($unit, $form);
                $this->flash_message('info', $settingRepository->trans('record_successfully_created'));
                return $this->webService->getRedirectResponse('unit/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @param SettingRepository $settingRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute,
      UnitRepository $unitRepository, SettingRepository $settingRepository, FormHydrator $formHydrator): Response 
    {
        $unit = $this->unit($currentRoute, $unitRepository);
        if ($unit) {
            $parameters = [
                'title' => $this->translator->translate('invoice.unit.edit'),
                'action' => ['unit/edit', ['id' => $unit->getUnit_id()]],
                'errors' => [],
                'body' => [
                    'unit_name' => $unit->getUnit_name(),
                    'unit_name_plrl' => $unit->getUnit_name_plrl(),
                ],
                's'=>$settingRepository,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new UnitForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->unitService->saveUnit($unit, $form);
                    $this->flash_message('info', $settingRepository->trans('record_successfully_updated'));
                    return $this->webService->getRedirectResponse('unit/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('__form', $parameters);
        } 
        return $this->webService->getRedirectResponse('unit/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, UnitRepository $unitRepository): Response 
    {
        try {
          /** @var Unit $unit */
          $unit = $this->unit($currentRoute, $unitRepository);              
          $this->unitService->deleteUnit($unit);
          return $this->webService->getRedirectResponse('unit/index');
        } catch (\Exception $e) {
          unset($e);
          $this->flash_message('danger', $this->translator->translate('invoice.unit.history'));
          return $this->webService->getRedirectResponse('unit/index');
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @param SettingRepository $settingRepository
     * @param FormHydrator $formHydrator
     */
    public function view(CurrentRoute $currentRoute, UnitRepository $unitRepository,SettingRepository $settingRepository, FormHydrator $formHydrator)
    : \Yiisoft\DataResponse\DataResponse|Response {
        $unit = $this->unit($currentRoute, $unitRepository);
        if ($unit) {
            $parameters = [
                'title' => $settingRepository->trans('edit_setting'),
                'action' => ['unit/edit', ['unit_id' => $unit->getUnit_id()]],
                'errors' => [],
                'unit'=>$unit,
                's'=>$settingRepository,     
                'body' => [
                    'unit_id'=>$unit->getUnit_id(),
                    'unit_name'=>$unit->getUnit_name(),
                    'unit_name_plrl'=>$unit->getUnit_name_plrl(),               
                ],            
            ];
            return $this->viewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('unit/index');
    } 
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @return Unit|null
     */
    private function unit(CurrentRoute $currentRoute, UnitRepository $unitRepository): Unit|null
    {
        $unit_id = $currentRoute->getArgument('id');
        if (null!==$unit_id) {
            $unit = $unitRepository->repoUnitquery($unit_id);
            return $unit; 
        }
        return null;
    }
    
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function units(UnitRepository $unitRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader{
        $units = $unitRepository->findAllPreloaded();
        return $units;
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
}