<?php

declare(strict_types=1);

namespace App\Invoice\Project;

use App\Invoice\BaseController;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Entity\Project;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ProjectController extends BaseController
{
    protected string $controllerName = 'invoice/project';
    
    public function __construct(
        private ProjectService $projectService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator, 
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
        $this->projectService = $projectService;
    }

    /**
     * @param int $page
     * @param ProjectRepository $projectRepository
     * @param Request $request
     * @param ProjectService $service
     */
    public function index(ProjectRepository $projectRepository, Request $request, ProjectService $service, #[Query('page')] int $page = null): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $parameters = [
            'page' => $page > 0 ? $page : 1,
            'canEdit' => $canEdit,
            'projects' => $this->projects($projectRepository),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        ClientRepository $clientRepository
    ): Response {
        $project = new Project();
        $form = new ProjectForm($project);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'project/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'clients' => $clientRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->projectService->saveProject($project, $body);
                    return $this->webService->getRedirectResponse('project/index');
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
     * @param ProjectRepository $projectRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ProjectRepository $projectRepository,
        ClientRepository $clientRepository
    ): Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $form = new ProjectForm($project);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'project/edit',
                'actionArguments' => ['id' => $project->getId()],
                'errors' => [],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->projectService->saveProject($project, $body);
                        return $this->webService->getRedirectResponse('project/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('project/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        ProjectRepository $projectRepository
    ): Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $this->projectService->deleteProject($project);
            $this->flashMessage('success', $this->translator->translate('i.record_successfully_deleted'));
        }
        return $this->webService->getRedirectResponse('project/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @param ClientRepository $clientRepository
     */
    public function view(CurrentRoute $currentRoute, ProjectRepository $projectRepository, ClientRepository $clientRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $form = new ProjectForm($project);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'project/view',
                'actionArguments' => ['id' => $project->getId()],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded(),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('project/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('project/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @return Project|null
     */
    private function project(CurrentRoute $currentRoute, ProjectRepository $projectRepository): Project|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $projectRepository->repoProjectquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function projects(ProjectRepository $projectRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $projectRepository->findAllPreloaded();
    }
}