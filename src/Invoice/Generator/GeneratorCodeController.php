<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\Gentor\Gentor;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use App\Invoice\Helpers\GenerateCodeFileHelper;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Cycle\Database\DatabaseManager;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\View;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Handles code-generation actions (entity, repo, service, form, controller, views, route).
 * Extracted from GeneratorController to satisfy S1448 (≤20 methods per class).
 */
final class GeneratorCodeController extends BaseController
{
    protected string $controllerName = 'invoice/generator';

    public const string ENTITY = 'Entity.php';
    public const string REPO = 'Repository.php';
    public const string FORM = 'Form.php';
    public const string SERVICE = 'Service.php';
    public const string MAPPER = 'Mapper.php';
    public const string SCOPE = 'Scope.php';
    public const string CONTROLLER = 'Controller.php';
    public const string INDEX = 'index.php';
    public const string INDEX_ADV_PAGINATOR = 'index_adv_paginator.php';
    public const string INDEX_ADV_PAGINATOR_WITH_FILTER = 'index_adv_paginator_with_filter.php';
    public const string WEBVIEW_FORM = '_form.php';
    public const string WEBVIEW_VIEW = '_view.php';
    public const string ROUTE = '_route.php';

    private Aliases $aliases;

    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->aliases = $this->setAliases();
    }

    public function entity(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::ENTITY;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $camelcaseFileName = $g->getCamelcaseCapitalName() . '.php';
        $viewPath = $this->aliases->get('@Entity');
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $build_file = $this->buildAndSave($viewPath, $content, '.php', $g->getCamelcaseCapitalName());
        $this->flashMessage('success', $camelcaseFileName . $this->translator->translate('generator.generated') . $viewPath . '/' . $camelcaseFileName);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    public function repo(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::REPO;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $camelcaseFileName = $g->getCamelcaseCapitalName() . $file;
        $viewPath = $this->aliases->get('@Invoice') . DIRECTORY_SEPARATOR . $g->getCamelcaseCapitalName();
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $build_file = $this->buildAndSave($viewPath, $content, $camelcaseFileName, '');
        $this->flashMessage('success', $camelcaseFileName . $this->translator->translate('generator.generated') . $viewPath . '/' . $camelcaseFileName);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    public function service(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::SERVICE;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $camelcaseFileName = $g->getCamelcaseCapitalName() . $file;
        $viewPath = $this->aliases->get('@Invoice') . DIRECTORY_SEPARATOR . $g->getCamelcaseCapitalName();
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $build_file = $this->buildAndSave($viewPath, $content, $camelcaseFileName, '');
        $this->flashMessage('success', $camelcaseFileName . $this->translator->translate('generator.generated') . $viewPath . '/' . $camelcaseFileName);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    public function form(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::FORM;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $camelcaseFileName = $g->getCamelcaseCapitalName() . $file;
        $viewPath = $this->aliases->get('@Invoice') . DIRECTORY_SEPARATOR . $g->getCamelcaseCapitalName();
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPreEntityTable() */
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $build_file = $this->buildAndSave($viewPath, $content, $camelcaseFileName, '');
        $this->flashMessage('success', $camelcaseFileName . $this->translator->translate('generator.generated') . $viewPath . '/' . $camelcaseFileName);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    public function controller(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::CONTROLLER;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@Invoice') . DIRECTORY_SEPARATOR . $g->getCamelcaseCapitalName();
        $camelcaseFileName = $g->getCamelcaseCapitalName() . $file;
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPreEntityTable() */
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $build_file = $this->buildAndSave($viewPath, $content, $camelcaseFileName, '');
        $this->flashMessage('success', $camelcaseFileName . $this->translator->translate('generator.generated') . $viewPath . '/' . $camelcaseFileName);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    public function generatorIndex(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::INDEX;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice') . DIRECTORY_SEPARATOR . $g->getSmallSingularName();
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPreEntityTable() */
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $build_file = $this->buildAndSave($viewPath, $content, $file, '');
        $this->flashMessage('success', $file . $this->translator->translate('generator.generated') . $viewPath . '/' . $file);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    public function generatorForm(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::WEBVIEW_FORM;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice') . DIRECTORY_SEPARATOR . $g->getSmallSingularName();
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $build_file = $this->buildAndSave($viewPath, $content, $file, '');
        $this->flashMessage('success', $file . $this->translator->translate('generator.generated') . $viewPath . '/' . $file);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    public function generatorView(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::WEBVIEW_VIEW;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice') . DIRECTORY_SEPARATOR . $g->getSmallSingularName();
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $build_file = $this->buildAndSave($viewPath, $content, $file, '');
        $this->flashMessage('success', $file . $this->translator->translate('generator.generated') . $viewPath . '/' . $file);
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    public function generatorRoute(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::ROUTE;
        $path = $this->aliases->get('@generated');
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $table_name = $g->getPreEntityTable();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->reqGentorId();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $this->flashMessage('success', $file . $this->translator->translate('generator.generated') . $path . '/' . $file);
        $build_file = $this->buildAndSave($path, $content, $file, '');
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->webViewRenderer->render('_results', $parameters);
    }

    private function generator(CurrentRoute $curR, GeneratorRepository $gR): ?Gentor
    {
        return $gR->repoGentorQuery((int) $curR->getArgument('id'));
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

    private function getContent(View $view, Gentor $generator, \Yiisoft\Data\Reader\DataReaderInterface $relations, \Cycle\Database\TableInterface $orm_schema, string $file): string
    {
        return $view->render('//invoice/generator/templates_protected/' . $file, [
            'generator' => $generator,
            'relations' => $relations,
            'orm_schema' => $orm_schema,
            'body' => $this->body($generator),
        ]);
    }

    private function buildAndSave(string $generated_dir_path, string $content, string $file, string $name): GenerateCodeFileHelper
    {
        $build_file = new GenerateCodeFileHelper("$generated_dir_path/$name$file", $content);
        $build_file->save();
        return $build_file;
    }

    /** @return array<string, mixed> */
    private function body(Gentor $generator): array
    {
        return [
            'route_prefix' => $generator->getRoutePrefix(),
            'route_suffix' => $generator->getRouteSuffix(),
            'camelcase_capital_name' => $generator->getCamelcaseCapitalName(),
            'small_singular_name' => $generator->getSmallSingularName(),
            'small_plural_name' => $generator->getSmallPluralName(),
            'namespace_path' => $generator->getNamespacePath(),
            'controller_layout_dir' => $generator->getControllerLayoutDir(),
            'controller_layout_dir_dot_path' => $generator->getControllerLayoutDirDotPath(),
            'pre_entity_table' => $generator->getPreEntityTable(),
            'flash_include' => $generator->isFlashInclude(),
        ];
    }

    private function setAliases(): Aliases
    {
        $ds = DIRECTORY_SEPARATOR;
        return new Aliases([
            '@generators' => dirname(__DIR__, 3) .
                '/resources/views/invoice/generator/templates_protected',
            '@generated' => dirname(__DIR__, 3) .
                '/resources/views/invoice/generator/output_overwrite',
            '@Entity' => dirname(__DIR__, 3) . '/src/Invoice/Entity',
            '@Invoice' => dirname(__DIR__, 3) . '/src/Invoice',
            '@invoice' => dirname(__DIR__, 3) . '/resources/views/invoice',
            '@messages' => dirname(__DIR__, 3) . '/resources/messages',
            '@en' => dirname(__DIR__, 3) .
                $ds . 'resources' . $ds . 'messages' . $ds . 'en',
            '@English' => dirname(__DIR__, 3) . '/src/Invoice/Language/English',
        ]);
    }
}
