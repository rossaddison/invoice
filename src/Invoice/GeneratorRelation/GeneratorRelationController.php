<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\BaseController;
use App\Invoice\Entity\GentorRelation;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class GeneratorRelationController extends BaseController
{
    protected string $controllerName = 'invoice/generatorrelation';

    public function __construct(
        private GeneratorRelationService $generatorrelationService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->generatorrelationService = $generatorrelationService;
    }

    public function index(GeneratorRelationRepository $generatorrelationRepository): \Yiisoft\DataResponse\DataResponse
    {
        $this->rbac();
        $generatorrelations = $this->generatorrelations($generatorrelationRepository);
        $paginator          = (new OffsetPaginator($generatorrelations));
        $parameters         = [
            'alert'     => $this->alert(),
            'paginator' => $paginator,
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function add(Request $request, GeneratorRepository $generatorRepository, FormHydrator $formHydrator): Response
    {
        $generatorrelation = new GentorRelation();
        $form              = new GeneratorRelationForm($generatorrelation);
        $parameters        = [
            'title'           => $this->translator->translate('generator.relation.form'),
            'actionName'      => 'generatorrelation/add',
            'actionArguments' => [],
            'form'            => $form,
            'errors'          => [],
            'generators'      => $generatorRepository->findAllPreloaded(),
        ];

        if (Method::POST === $request->getMethod()) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->generatorrelationService->saveGeneratorRelation($generatorrelation, $body);

                    return $this->webService->getRedirectResponse('generatorrelation/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        GeneratorRelationRepository $generatorrelationRepository,
        GeneratorRepository $generatorRepository,
        FormHydrator $formHydrator,
    ): Response {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $form       = new GeneratorRelationForm($generatorrelation);
            $parameters = [
                'title'           => $this->translator->translate('edit'),
                'actionName'      => 'generatorrelation/edit',
                'actionArguments' => ['id' => $generatorrelation->getRelation_id()],
                'errors'          => [],
                'form'            => $form,
                // relation generator
                'generators' => $generatorRepository->findAllPreloaded(),
            ];
            if (Method::POST === $request->getMethod()) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->generatorrelationService->saveGeneratorRelation($generatorrelation, $body);

                        return $this->webService->getRedirectResponse('generatorrelation/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('generatorrelation/index');
    }

    public function delete(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository): Response
    {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $this->generatorrelationService->deleteGeneratorRelation($generatorrelation);

            return $this->webService->getRedirectResponse('generatorrelation/index');
        }

        return $this->webService->getRedirectResponse('generatorrelation/index');
    }

    public function view(
        CurrentRoute $currentRoute,
        GeneratorRelationRepository $generatorrelationRepository,
        GeneratorRepository $generatorRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $form       = new GeneratorRelationForm($generatorrelation);
            $parameters = [
                'title'             => $this->translator->translate('view'),
                'actionName'        => 'generatorrelation/view',
                'actionArguments'   => ['id' => $generatorrelation->getRelation_id()],
                'errors'            => [],
                'form'              => $form,
                'generatorrelation' => $generatorrelation,
                'generators'        => $generatorRepository->findAllPreloaded(),
                'egrs'              => $generatorrelationRepository->repoGeneratorRelationquery($generatorrelation->getRelation_id()),
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('generatorrelation/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));

            return $this->webService->getRedirectResponse('generatorrelation/index');
        }

        return $canEdit;
    }

    private function generatorrelation(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository): ?GentorRelation
    {
        $generatorrelation_id = $currentRoute->getArgument('id');
        if (null !== $generatorrelation_id) {
            return $generatorrelationRepository->repoGeneratorRelationquery($generatorrelation_id);
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function generatorrelations(GeneratorRelationRepository $generatorrelationRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $generatorrelationRepository->findAllPreloaded();
    }
}
