<?php

declare(strict_types=1); 

namespace App\Invoice\Project;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Entity\Project;
use App\Invoice\Project\ProjectService;
use App\Invoice\Project\ProjectForm;
use App\Invoice\Project\ProjectRepository;
use App\Invoice\Setting\SettingRepository;
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

final class ProjectController
{
    private Session $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ProjectService $projectService;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ProjectService $projectService,
        TranslatorInterface $translator,
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/project')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->projectService = $projectService;
        $this->translator = $translator;
    }
    
    /**
     * @param ProjectRepository $projectRepository
     * @param SettingRepository $sR
     * @param Request $request
     * @param ProjectService $service
     */
    public function index(ProjectRepository $projectRepository, SettingRepository $sR, Request $request, ProjectService $service): \Yiisoft\DataResponse\DataResponse
    {            
        $pageNum = (int)$request->getAttribute('page', '1');
        $paginator = (new OffsetPaginator($this->projects($projectRepository)))
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);      
        $canEdit = $this->rbac();
        $parameters = [
              'paginator' => $paginator,  
              'canEdit' => $canEdit,
              'projects' => $this->projects($projectRepository),
              'grid_summary' => $sR->grid_summary($paginator, $this->translator, (int)$sR->get_setting('default_list_limit'), $this->translator->translate('invoice.products'), ''),
              'alert'=> $this->alert()
        ];  
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(Request $request, 
                        FormHydrator $formHydrator,
                        ClientRepository $clientRepository
    ): Response
    {
        $project = new Project();
        $form = new ProjectForm($project);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['project/add'],
            'errors' => [],
            'form' => $form,
            'clients' => $clientRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->projectService->saveProject($project, $body);
                return $this->webService->getRedirectResponse('project/index');
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
     return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
     [ 
       'flash' => $this->flash
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
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ProjectRepository $projectRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function edit(
                        Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        ProjectRepository $projectRepository, 
                        ClientRepository $clientRepository
    ): Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $form = new ProjectForm($project);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['project/edit', ['id' => $project->getId()]],
                'errors' => [],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody();
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->projectService->saveProject($project, $body);
                    return $this->webService->getRedirectResponse('project/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
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
    public function delete(CurrentRoute $currentRoute, 
                           ProjectRepository $projectRepository): Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $this->projectService->deleteProject($project);               
            $this->flash_message('success', $this->translator->translate('i.record_successfully_deleted'));
        }
        return $this->webService->getRedirectResponse('project/index'); 
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @param ClientRepository $clientRepository
     */
    public function view(CurrentRoute $currentRoute, ProjectRepository $projectRepository, ClientRepository $clientRepository): \Yiisoft\DataResponse\DataResponse|Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) { 
            $form = new ProjectForm($project);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['project/view', ['id' => $project->getId()]],
                'errors' => [],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded(),
                'project' => $projectRepository->repoProjectquery($project->getId()),
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
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
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
        if (null!==$id) {
            $project = $projectRepository->repoProjectquery($id);
            return $project;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function projects(ProjectRepository $projectRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $projects = $projectRepository->findAllPreloaded();        
        return $projects;
    }
}