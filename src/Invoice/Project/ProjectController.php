<?php

declare(strict_types=1); 

namespace App\Invoice\Project;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Entity\Project;
use App\Invoice\Project\ProjectService;
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
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
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
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param ProjectService $service
     */
    public function index(ProjectRepository $projectRepository, SettingRepository $settingRepository, Request $request, ProjectService $service): \Yiisoft\DataResponse\DataResponse
    {            
        $pageNum = (int)$request->getAttribute('page', '1');
        $paginator = (new OffsetPaginator($this->projects($projectRepository)))
        ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);      
        $canEdit = $this->rbac();
        $parameters = [
              'paginator' => $paginator,  
              's'=>$settingRepository,
              'canEdit' => $canEdit,
              'projects' => $this->projects($projectRepository),
              'alert'=> $this->alert()
        ];  
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                       FormHydrator $formHydrator,
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['project/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            'clients'=>$clientRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ProjectForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
              $this->projectService->saveProject(new Project(),$form);
              return $this->webService->getRedirectResponse('project/index');
          }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
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
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ProjectRepository $projectRepository
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        ProjectRepository $projectRepository, 
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository
    ): Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $parameters = [
                'title' => 'Edit',
                'action' => ['project/edit', ['id' =>$project->getId()]],
                'errors' => [],
                'body' => $this->body($project),
                'head'=>$head,
                's'=>$settingRepository,
                'clients'=>$clientRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new ProjectForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                $this->projectService->saveProject($project, $form);
                    return $this->webService->getRedirectResponse('project/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('project/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @param SettingRepository $sR
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, 
                           ProjectRepository $projectRepository,
                           SettingRepository $sR): Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $this->projectService->deleteProject($project);               
            $this->flash_message('success', $sR->trans('record_successfully_deleted'));
        }
        return $this->webService->getRedirectResponse('project/index'); 
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, ProjectRepository $projectRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['project/view', ['id' =>$project->getId()]],
                'errors' => [],
                'body' => $this->body($project),
                's'=>$settingRepository,             
                'project'=>$projectRepository->repoProjectquery($project->getId()),
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
    
    /**
     * 
     * @param Project $project
     * @return array
     */
    private function body(Project $project): array {
        $body = [                
          'id'=>$project->getId(),
          'client_id'=>$project->getClient_id(),
          'name'=>$project->getName()
                ];
        return $body;
    }
}