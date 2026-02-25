<?php

declare(strict_types=1);

namespace App\Invoice\Group;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\Group;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator as DataOffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class GroupController extends BaseController
{
    protected string $controllerName = 'invoice/group';

    public function __construct(
        private GroupService $groupService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->groupService = $groupService;
    }

    /**
     * @param GroupRepository $groupRepository
     * @param Request $request
     * @param GroupService $service
     */
    public function index(GroupRepository $groupRepository, Request $request, GroupService $service): \Psr\Http\Message\ResponseInterface
    {
        $page = (int) $request->getAttribute('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $paginator = (new DataOffsetPaginator($this->groups($groupRepository)))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next((string) $page));
        // Generate a flash message in the index if the user does not have permission
        $this->rbac();
        $parameters = [
            'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                    ? (int) $this->sR->getSetting('default_list_limit') : 1,
            'paginator' => $paginator,
            'groups' => $this->groups($groupRepository),
            'alert' => $this->alert(),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $group = new Group();
        $form = new GroupForm($group);
        $parameters = [
            'title' => $this->translator->translate('add'),
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
        return $this->webViewRenderer->render('_form', $parameters);
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
        GroupRepository $groupRepository,
    ): Response {
        $group = $this->group($currentRoute, $groupRepository);
        if ($group) {
            $form = new GroupForm($group);
            $parameters = [
                'title' => $this->translator->translate('edit'),
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
            return $this->webViewRenderer->render('_form', $parameters);
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
        GroupRepository $groupRepository,
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
            $this->flashMessage('danger', $this->translator->translate('group.history'));
            return $this->webService->getRedirectResponse('group/index');
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(
        CurrentRoute $currentRoute,
        GroupRepository $groupRepository,
    ): \Psr\Http\Message\ResponseInterface {
        $group = $this->group($currentRoute, $groupRepository);
        if ($group) {
            $form = new GroupForm($group);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'group/view',
                'actionArguments' => ['id' => $group->getId()],
                'errors' => [],
                'form' => $form,
                'group' => $groupRepository->repoGroupquery($group->getId()),
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('group/index');
    }

    /**
     * @return bool|Response
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('group/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return Group|null
     */
    private function group(CurrentRoute $currentRoute, GroupRepository $groupRepository): ?Group
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
}
