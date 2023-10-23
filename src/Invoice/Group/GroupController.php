<?php

declare(strict_types=1); 

namespace App\Invoice\Group;

use App\Invoice\Entity\Group;
use App\Invoice\Group\GroupService;
use App\Invoice\Group\GroupRepository;
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

final class GroupController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private GroupService $groupService;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        GroupService $groupService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->webService = $webService;
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->groupService = $groupService;
        $this->translator = $translator;
    }
    
    /**
     * @param GroupRepository $groupRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param GroupService $service
     */
    public function index(GroupRepository $groupRepository, SettingRepository $settingRepository, Request $request, GroupService $service): \Yiisoft\DataResponse\DataResponse
    {    
        $pageNum = (int)$request->getAttribute('page', '1');
        $paginator = (new OffsetPaginator($this->groups($groupRepository)))
        ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);
        $canEdit = $this->rbac();
        $parameters = [
              'paginator' => $paginator,
              's'=>$settingRepository,
              'canEdit' => $canEdit,
              'groups' => $this->groups($groupRepository),
              'alert'=> $this->alert()
        ];  
        return $this->viewRenderer->render('group/index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $SettingRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $SettingRepository                        

    ) : Response 
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['group/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$SettingRepository,
            'head'=>$head
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new GroupForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->groupService->saveGroup(new Group(),$form);
                return $this->webService->getRedirectResponse('group/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('/invoice/group/_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param GroupRepository $groupRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        GroupRepository $groupRepository, 
                        SettingRepository $settingRepository                       

    ): Response {
        $group = $this->group($currentRoute, $groupRepository);
        if ($group) {
            $parameters = [
                'title' => 'Edit',
                'action' => ['group/edit', ['id' => $group->getId()]],
                'errors' => [],
                'body' => $this->body($group),
                's'=>$settingRepository,
                'head'=>$head

            ];
            if ($request->getMethod() === Method::POST) {
                $form = new GroupForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->groupService->saveGroup($group, $form);
                    return $this->webService->getRedirectResponse('group/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('/invoice/group/_form', $parameters);
        }
        return $this->webService->getRedirectResponse('group/index');
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, GroupRepository $groupRepository 
    ): Response {
        try {
              $group = $this->group($currentRoute, $groupRepository);
              if ($group) {
                $this->groupService->deleteGroup($group);               
                return $this->webService->getRedirectResponse('group/index'); 
              }
              return $this->webService->getRedirectResponse('group/index'); 
	} catch (\Exception $e) {
              unset($e);
              $this->flash_message('danger', $this->translator->translate('invoice.group.history'));
              return $this->webService->getRedirectResponse('group/index'); 
        }
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, GroupRepository $groupRepository,
        SettingRepository $settingRepository
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $group = $this->group($currentRoute, $groupRepository);
        if ($group) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['invoice/edit', ['id' => $group->getId()]],
                'errors' => [],
                'body' => $this->body($group),
                's'=>$settingRepository,            
                'group'=>$groupRepository->repoGroupquery($group->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('group/index');  
    }
    
    /**
     * 
     * @return bool|Response
     */
    private function rbac() : bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('group/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return Group|null
     */
    private function group(CurrentRoute $currentRoute, GroupRepository $groupRepository) : Group|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $group = $groupRepository->repoGroupquery($id);
            return $group;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function groups(GroupRepository $groupRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $groups = $groupRepository->findAllPreloaded();        
        return $groups;
    }
    
    /**
     * 
     * @param Group $group
     * @return array
     */
    private function body(Group $group): array {
        $body = [
          'id'=>$group->getId(),
          'name'=>$group->getName(),
          'identifier_format'=>$group->getIdentifier_format(),
          'next_id'=>$group->getNext_id(),
          'left_pad'=>$group->getLeft_pad()
        ];
        return $body;
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