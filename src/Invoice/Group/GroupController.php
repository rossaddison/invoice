<?php

declare(strict_types=1);

namespace App\Invoice\Group;

use App\Invoice\Entity\Group;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator as DataOffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class GroupController
{
    use FlashMessage;

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
    ) {
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
        $page = (int)$request->getAttribute('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $paginator = (new DataOffsetPaginator($this->groups($groupRepository)))
        ->withPageSize($settingRepository->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string)$page));
        // Generate a flash message in the index if the user does not have permission
        $this->rbac();
        $parameters = [
            'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                                                    ? (int)$settingRepository->getSetting('default_list_limit') : 1,
            'paginator' => $paginator,
            'groups' => $this->groups($groupRepository),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('group/index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator
    ): Response {
        $group = new Group();
        $form = new GroupForm($group);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'group/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->groupService->saveGroup($group, $body);
                    return $this->webService->getRedirectResponse('group/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('group/_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param GroupRepository $groupRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        GroupRepository $groupRepository
    ): Response {
        $group = $this->group($currentRoute, $groupRepository);
        if ($group) {
            $form = new GroupForm($group);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'group/edit',
                'actionArguments' => ['id' => $group->getId()],
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->groupService->saveGroup($group, $body);
                        return $this->webService->getRedirectResponse('group/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('group/_form', $parameters);
        }
        return $this->webService->getRedirectResponse('group/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        GroupRepository $groupRepository
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
            $this->flashMessage('danger', $this->translator->translate('invoice.group.history'));
            return $this->webService->getRedirectResponse('group/index');
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CurrentRoute $currentRoute,
        GroupRepository $groupRepository
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $group = $this->group($currentRoute, $groupRepository);
        if ($group) {
            $form = new GroupForm($group);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'group/view',
                'actionArguments' => ['id' => $group->getId()],
                'errors' => [],
                'form' => $form,
                'group' => $groupRepository->repoGroupquery($group->getId()),
            ];
            return $this->viewRenderer->render('group/_view', $parameters);
        }
        return $this->webService->getRedirectResponse('group/index');
    }

    /**
     * @return bool|Response
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('group/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return Group|null
     */
    private function group(CurrentRoute $currentRoute, GroupRepository $groupRepository): Group|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $groupRepository->repoGroupquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function groups(GroupRepository $groupRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $groupRepository->findAllPreloaded();
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
