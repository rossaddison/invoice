<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\Gentor\Gentor;
use App\Invoice\Generator\Widget\GeneratorListWidget;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Cycle\Database\DatabaseManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

class GeneratorController extends BaseController
{
    protected string $controllerName = 'invoice/generator';

    public function __construct(
        private GeneratorService $generatorService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    public function index(
        Request $request,
        HtmlResponseFactory $htmlResponseFactory,
        GeneratorRepository $generatorRepository,
        GeneratorRelationRepository $grR,
    ): Response {
        $rbacResult = $this->rbac();
        if ($rbacResult instanceof Response) {
            return $rbacResult;
        }
        $q = $request->getQueryParams();
        /** @psalm-suppress MixedAssignment */
        $sortString = isset($q['sort']) ? (string) $q['sort'] : '-id';
        $sort = Sort::only(['id'])->withOrderString($sortString);
        $currentPage = max(1, isset($q['page']) ? (int) $q['page'] : 1);
        /** @psalm-suppress InvalidArgument */
        $paginator = (new OffsetPaginator($this->generators($generatorRepository)))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPage)
            ->withSort($sort);
        $gridSummary = $this->sR->gridSummary(
            $paginator,
            $this->translator,
            (int) $this->sR->getSetting('default_list_limit'),
            $this->translator->translate('generators'),
            '',
        );
        $body = $request->getParsedBody();
        $widget = GeneratorListWidget::widget()
            ->withPaginator($paginator)
            ->withGrR($grR)
            ->withCsrf((string) (is_array($body) ? ($body['_csrf'] ?? '') : ''))
            ->withGridSummary($gridSummary)
            ->withSortString($sortString);
        if ($request->hasHeader('Hx-Request')) {
            return $htmlResponseFactory->createResponse($widget->render());
        }
        $parameters = [
            'alert'       => $this->alert(),
            'grR'         => $grR,
            'paginator'   => $paginator,
            'gridSummary' => $gridSummary,
            'sortString'  => $sortString,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    public function add(Request $request, FormHydrator $formHydrator, DatabaseManager $dbal): Response
    {
        $gentor = new Gentor();
        $form = new GeneratorForm();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'generator/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'tables' => $dbal->database('default')->getTables(),
            'selected_table' => '',
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request) && is_array($body)) {
                $this->generatorService->saveGenerator($gentor, $body);
                return $this->webService->getRedirectResponse('generator/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    public function edit(CurrentRoute $currentRoute, Request $request, GeneratorRepository $generatorRepository, FormHydrator $formHydrator, DatabaseManager $dbal): Response
    {
        $generator = $this->generator($currentRoute, $generatorRepository);
        if ($generator) {
            $form = GeneratorForm::show($generator);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'generator/edit',
                'actionArguments' => ['id' => $generator->reqGentorId()],
                'errors' => [],
                'form' => $form,
                'tables' => $dbal->database('default')->getTables(),
                'selected_table' => $generator->getPreEntityTable(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request) && is_array($body)) {
                    $this->generatorService->saveGenerator($generator, $body);
                    $this->flashMessage('warning', $this->translator->translate('record.successfully.updated'));
                    return $this->webService->getRedirectResponse('generator/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('generator/index');
    }

    public function delete(CurrentRoute $currentRoute, GeneratorRepository $generatorRepository): Response
    {
        try {
            $generator = $this->generator($currentRoute, $generatorRepository);
            if ($generator) {
                $this->flashMessage('danger', $this->translator->translate('record.successfully.deleted'));
                $this->generatorService->deleteGenerator($generator);
                return $this->webService->getRedirectResponse('generator/index');
            }
            return $this->webService->getRedirectResponse('generator/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('generator.history'));
        }
        return $this->webService->getRedirectResponse('generator/index');
    }

    public function view(
        CurrentRoute $currentRoute,
        GeneratorRepository $generatorRepository,
    ): Response {
        $generator = $this->generator($currentRoute, $generatorRepository);
        if ($generator) {
            $form = GeneratorForm::show($generator);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'generator/view',
                'actionArguments' => ['id' => $generator->reqGentorId()],
                'generator' => $generator,
                'form' => $form,
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('generator/index');
    }

    public function quickViewSchema(CurrentUser $currentUser, DatabaseManager $dba): Response
    {
        $parameters = [
            'alerts' => $this->alert(),
            'isGuest' => $currentUser->isGuest(),
            'tables' => $dba->database('default')->getTables(),
        ];
        return $this->webViewRenderer->render('_schema', $parameters);
    }

    /** @return Response|true */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('generator/index');
        }
        return $canEdit;
    }

    private function generator(CurrentRoute $curR, GeneratorRepository $gR): ?Gentor
    {
        return $gR->repoGentorQuery((int) $curR->getArgument('id'));
    }

    /** @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader */
    private function generators(GeneratorRepository $generatorRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $generatorRepository->findAllPreloaded();
    }
}
