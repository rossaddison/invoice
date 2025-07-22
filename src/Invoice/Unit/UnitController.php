<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Invoice\BaseController;
use App\Invoice\Entity\Unit;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\UnitPeppol\UnitPeppolRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class UnitController extends BaseController
{
    protected string $controllerName = 'invoice/unit';

    public function __construct(
        private UnitService $unitService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->unitService = $unitService;
    }

    public function index(CurrentRoute $currentRoute, UnitRepository $unitRepository, UnitPeppolRepository $upR): \Yiisoft\DataResponse\DataResponse
    {
        $units   = $this->units($unitRepository);
        $pageNum = (int) $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $pageNum > 0 ? $pageNum : 1;
        $paginator            = (new OffsetPaginator($units))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero);
        $parameters = [
            'alert'     => $this->alert(),
            'paginator' => $paginator,
            'upR'       => $upR,
            'units'     => $units,
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $unit       = new Unit();
        $form       = new UnitForm($unit);
        $parameters = [
            'title'           => $this->translator->translate('add'),
            'actionName'      => 'unit/add',
            'actionArguments' => [],
            'form'            => $form,
            'errors'          => [],
        ];
        if (Method::POST === $request->getMethod()) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->unitService->saveUnit($unit, $body);
                    $this->flashMessage('info', $this->translator->translate('record.successfully.created'));

                    return $this->webService->getRedirectResponse('unit/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('__form', $parameters);
    }

    public function edit(
        Request $request,
        #[RouteArgument('unit_id')]
        string $unit_id,
        UnitRepository $unitRepository,
        FormHydrator $formHydrator,
    ): Response {
        $unit = $this->unit($unit_id, $unitRepository);
        if ($unit) {
            $form       = new UnitForm($unit);
            $parameters = [
                'title'           => $this->translator->translate('unit.edit'),
                'actionName'      => 'unit/edit',
                'actionArguments' => ['unit_id' => $unit_id],
                'form'            => $form,
                'errors'          => [],
            ];
            if (Method::POST === $request->getMethod()) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->unitService->saveUnit($unit, $body);
                        $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));

                        return $this->webService->getRedirectResponse('unit/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('__form', $parameters);
        }

        return $this->webService->getRedirectResponse('unit/index');
    }

    public function delete(#[RouteArgument('unit_id')] string $unit_id, UnitRepository $unitRepository): Response
    {
        try {
            /** @var Unit $unit */
            $unit = $this->unit($unit_id, $unitRepository);
            $this->unitService->deleteUnit($unit);
            $this->flashMessage('success', $this->translator->translate('record.successfully.deleted'));

            return $this->webService->getRedirectResponse('unit/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('unit.history'));

            return $this->webService->getRedirectResponse('unit/index');
        }
    }

    public function view(#[RouteArgument('unit_id')] string $unit_id, UnitRepository $unitRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $unit = $this->unit($unit_id, $unitRepository);
        if ($unit) {
            $form       = new UnitForm($unit);
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'unit/view',
                'actionArguments' => ['unit_id' => $unit_id],
                'form'            => $form,
            ];

            return $this->viewRenderer->render('__view', $parameters);
        }

        return $this->webService->getRedirectResponse('unit/index');
    }

    private function unit(string $unit_id, UnitRepository $unitRepository): ?Unit
    {
        if ($unit_id) {
            return $unitRepository->repoUnitquery($unit_id);
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function units(UnitRepository $unitRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $unitRepository->findAllPreloaded();
    }
}
