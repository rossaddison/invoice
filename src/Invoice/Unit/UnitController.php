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
use Yiisoft\FormModel\FormHydrator;
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
            'grid_summary'=> $settingRepository->grid_summary(
                    $paginator, 
                    $this->translator, 
                    (int)$settingRepository->get_setting('default_list_limit'), 
                    $this->translator->translate('i.units'), ''
            ),
            'paginator'=> $paginator,
            'upR'=>$upR,
            'units' => $units, 
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $unit = new Unit();
        $form = new UnitForm($unit);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'action' => ['unit/add'],
            'form' => $form,
            'errors' => []
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->unitService->saveUnit($unit, $body);
                $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('unit/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute,
      UnitRepository $unitRepository, FormHydrator $formHydrator): Response 
    {
        $unit = $this->unit($currentRoute, $unitRepository);
        if ($unit) {
            $form = new UnitForm($unit);
            $parameters = [
                'title' => $this->translator->translate('invoice.unit.edit'),
                'action' => ['unit/edit', ['unit_id' => $unit->getUnit_id()]],
                'form' => $form,
                'errors' => []
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                    $body = $request->getParsedBody();
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->unitService->saveUnit($unit, $body);
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('unit/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
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
          $this->flash_message('success', $this->translator->translate('i.record_successfully_deleted'));
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
     */
    public function view(CurrentRoute $currentRoute, UnitRepository $unitRepository)
    : \Yiisoft\DataResponse\DataResponse|Response {
        $unit = $this->unit($currentRoute, $unitRepository);
        if ($unit) {
            $form = new UnitForm($unit);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['unit/view', ['unit_id' => $unit->getUnit_id()]],
                'form' => $form
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
        $unit_id = $currentRoute->getArgument('unit_id');
        if (null!==$unit_id) {
            $unit = $unitRepository->repoUnitquery($unit_id);
            return $unit; 
        }
        return null;
    }
    
    
    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function units(UnitRepository $unitRepository): \Yiisoft\Data\Cycle\Reader\EntityReader{
        $units = $unitRepository->findAllPreloaded();
        return $units;
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
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
}