<?php

declare(strict_types=1);

namespace App\Invoice\Sumex;

use App\Invoice\BaseController;
use App\Invoice\Entity\Sumex;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\FormModel\FormHydrator;

final class SumexController extends BaseController
{
    protected string $controllerName = 'invoice/sumex';
    
    public function __construct(
        private SumexService $sumexService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator, 
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
        $this->sumexService = $sumexService;
        $this->factory = $factory;
    }

    /**
     * @param SumexRepository $sumexRepository
     */
    public function index(SumexRepository $sumexRepository): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $sumexs = $this->sumexs($sumexRepository);
        $paginator = (new OffsetPaginator($sumexs));
        $parameters = [
            'canEdit' => $canEdit,
            'sumexs' => $sumexs,
            'paginator' => $paginator,
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator
    ): Response {
        $inv_id = $currentRoute->getArgument('inv_id');
        $model = new Sumex();
        $form = new SumexForm($model);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'sumex/add',
            'actionArguments' => ['inv_id' => $inv_id],
            'inv_id' => $inv_id,
            'form' => $form,
            'optionsDataReasons' => $this->optionsDataReasons(),
            'errors' => [],
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->sumexService->saveSumex($model, $body);
                    return $this->webService->getRedirectResponse('sumex/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param SumexRepository $sumexRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        SumexRepository $sumexRepository
    ): Response {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {
            $form = new SumexForm($sumex);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'sumex/edit',
                'actionArguments' => ['id' => $sumex->getId()],
                'form' => $form,
                'optionsDataReasons' => $this->optionsDataReasons(),
                'errors' => [],
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body) && isset($body['invoice'])) {
                        $this->sumexService->saveSumex($sumex, $body);
                        $this->flashMessage('success', $this->translator->translate('i.record_successfully_updated'));
                        $id = (string)$body['invoice'];
                        return $this->webService->getRedirectResponse('inv/view', ['id' => $id]);
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('sumex/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        SumexRepository $sumexRepository
    ): Response {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {
            $this->sumexService->deleteSumex($sumex);
            $this->flashMessage('success', $this->translator->translate('i.record_successfully_deleted'));
        }
        return $this->webService->getRedirectResponse('sumex/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, SumexRepository $sumexRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {
            $form = new SumexForm($sumex);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'sumex/view',
                'actionArguments' => ['id' => $sumex->getId()],
                'optionsDataReasons' => $this->optionsDataReasons(),
                'errors' => [],
                'form' => $form,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('sumex/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('sumex/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return Sumex|null
     */
    private function sumex(CurrentRoute $currentRoute, SumexRepository $sumexRepository): Sumex|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $sumexRepository->repoSumexquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function sumexs(SumexRepository $sumexRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $sumexRepository->findAllPreloaded();
    }

    private function optionsDataReasons(): array
    {
        $reasons = [
            'disease',
            'accident',
            'maternity',
            'prevention',
            'birthdefect',
            'unknown',
        ];
        $optionsDataReasons = [];
        foreach ($reasons as $key => $value) {
            $optionsDataReasons[$key] = $this->translator->translate('i.reason_' . $value);
        }
        return $optionsDataReasons;
    }
}