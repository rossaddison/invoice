<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GeneratorRelationController
{
    use FlashMessage;
    private Flash $flash;
    private ViewRenderer $viewRenderer;

    public function __construct(
        private Session $session,
        ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private GeneratorRelationService $generatorrelationService,
        private UserService $userService,
        private TranslatorInterface $translator
    ) {
        $this->flash = new Flash($this->session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/generatorrelation')
                                           ->withLayout('@views/layout/invoice.php');
    }

    /**
     * @param GeneratorRelationRepository $generatorrelationRepository
     */
    public function index(GeneratorRelationRepository $generatorrelationRepository): \Yiisoft\DataResponse\DataResponse
    {
        $this->rbac();
        $generatorrelations = $this->generatorrelations($generatorrelationRepository);
        $paginator = (new OffsetPaginator($generatorrelations));
        $parameters = [
            'alert' => $this->alert(),
            'paginator' => $paginator,
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param GeneratorRepository $generatorRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, GeneratorRepository $generatorRepository, FormHydrator $formHydrator): Response
    {
        $generatorrelation = new GentorRelation();
        $form = new GeneratorRelationForm($generatorrelation);
        $parameters = [
            'title' => $this->translator->translate('invoice.generator.relation.form'),
            'actionName' => 'generatorrelation/add',
            'actionArguments' => [],
            'form' => $form,
            'errors' => [],
            'generators' => $generatorRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->generatorrelationService->saveGeneratorRelation($generatorrelation, $body);
                    return $this->webService->getRedirectResponse('generatorrelation/index');
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
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @param GeneratorRepository $generatorRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        GeneratorRelationRepository $generatorrelationRepository,
        GeneratorRepository $generatorRepository,
        FormHydrator $formHydrator
    ): Response {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $form = new GeneratorRelationForm($generatorrelation);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'generatorrelation/edit',
                'actionArguments' => ['id' => $generatorrelation->getRelation_id()],
                'errors' => [],
                'form' => $form,
                //relation generator
                'generators' => $generatorRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->generatorrelationService->saveGeneratorRelation($generatorrelation, $body);
                        return $this->webService->getRedirectResponse('generatorrelation/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('generatorrelation/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository): Response
    {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $this->generatorrelationService->deleteGeneratorRelation($generatorrelation);
            return $this->webService->getRedirectResponse('generatorrelation/index');
        }
        return $this->webService->getRedirectResponse('generatorrelation/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @param GeneratorRepository $generatorRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CurrentRoute $currentRoute,
        GeneratorRelationRepository $generatorrelationRepository,
        GeneratorRepository $generatorRepository
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $form = new GeneratorRelationForm($generatorrelation);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'generatorrelation/view',
                'actionArguments' => ['id' => $generatorrelation->getRelation_id()],
                'errors' => [],
                'form' => $form,
                'generatorrelation' => $generatorrelation,
                'generators' => $generatorRepository->findAllPreloaded(),
                'egrs' => $generatorrelationRepository->repoGeneratorRelationquery($generatorrelation->getRelation_id()),
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
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('generatorrelation/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @return GentorRelation|null
     */
    private function generatorrelation(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository): GentorRelation|null
    {
        $generatorrelation_id = $currentRoute->getArgument('id');
        if (null !== $generatorrelation_id) {
            return $generatorrelationRepository->repoGeneratorRelationquery($generatorrelation_id);
        }
        return null;
    }

    //$generatorrelations = $this->generatorrelations();

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function generatorrelations(GeneratorRelationRepository $generatorrelationRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $generatorrelationRepository->findAllPreloaded();
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash' => $this->flash,
            ]
        );
    }
}
