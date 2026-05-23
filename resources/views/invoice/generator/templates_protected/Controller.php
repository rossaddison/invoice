<?php

declare(strict_types=1);

/**
 * Related logic: see GeneratorController function controller
 * @var App\Infrastructure\Persistence\Gentor\Gentor $generator
 * @var Cycle\Database\Table $orm_schema
 * @var array $relations
 */

echo "<?php\n";
?>

declare(strict_types=1);

namespace <?= $generator->getNamespacePath() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName(); ?>;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\<?= $generator->getCamelcaseCapitalName(); ?>\<?= $generator->getCamelcaseCapitalName(); ?>;
use <?= $generator->getNamespacePath() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName(); ?>Form;
use <?= $generator->getNamespacePath() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName(); ?>Service;
use <?= $generator->getNamespacePath() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName(); ?>Repository;
use App\Invoice\Setting\SettingRepository as sR;
<?php
  /**
   * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
   */
  foreach ($relations as $relation) {
      echo 'use '
        . $generator->getNamespacePath()
        . DIRECTORY_SEPARATOR
        . ($relation->getCamelcaseName() ?? '#')
        . DIRECTORY_SEPARATOR
        . ($relation->getCamelcaseName() ?? '#')
        . 'Repository;' . "\n";
  }
?>
use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\{
    ResponseInterface as Response, ServerRequestInterface as Request
};

use Yiisoft\{
    Data\Cycle\Reader\EntityReader,
    Data\Paginator\PageToken,
    Data\Paginator\OffsetPaginator,
    FormModel\FormHydrator,
    Http\Method,
    Router\FastRoute\UrlGenerator,
    Router\HydratorAttribute\RouteArgument,
    Session\SessionInterface,
    Session\Flash\Flash,
    Translator\TranslatorInterface,
    Yii\View\Renderer\WebViewRenderer
};

use \Exception;

final class <?= $generator->getCamelcaseCapitalName(); ?>Controller extends BaseController
{
    protected string $controllerName = 'invoice/<?= $generator->getSmallSingularName(); ?>';

    public function __construct(
        private <?= $generator->getCamelcaseCapitalName(); ?>Service $<?= $generator->getSmallSingularName(); ?>Service,
        private UrlGenerator $urlGenerator,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
        $this-><?= $generator->getSmallSingularName(); ?>Service = $<?= $generator->getSmallSingularName(); ?>Service;
        $this->urlGenerator = $urlGenerator;
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
<?php
$rel = '';
/**
 * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
 */
foreach ($relations as $relation) {
    $rel .= '        '
            . ($relation->getCamelcaseName() ?? '#')
            . 'Repository $'
            . lcfirst($relation->getCamelcaseName() ?? '#')
            . 'Repository,' . "\n";
}
echo rtrim($rel, ",\n");
if (!empty($rel)) {
    echo "\n";
}
?>
    ): Response {
        $<?= $generator->getSmallSingularName(); ?> = new <?= $generator->getCamelcaseCapitalName(); ?>();
        $form = new <?= $generator->getCamelcaseCapitalName(); ?>Form();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => '<?= $generator->getSmallSingularName(); ?>/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
<?php
$rel = '';
/**
 * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
 */
foreach ($relations as $relation) {
    $rel .= "            '"
            . ($relation->getLowercaseName() ?? '#')
            . "s' => $"
            . ($relation->getLowercaseName() ?? '#')
            . 'Repository->findAllPreloaded(),' . "\n";
}
echo rtrim($rel, "\n");
if (!empty($rel)) {
    echo "\n";
}
?>
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this-><?= $generator->getSmallSingularName(); ?>Service->save<?= $generator->getCamelcaseCapitalName(); ?>($<?= $generator->getSmallSingularName(); ?>, $body);
                    return $this->webService->getRedirectResponse('<?= $generator->getSmallSingularName(); ?>/index');
                }
                $parameters['errors'] = $form->getValidationResult()
                                             ->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    public function index(
        <?= $generator->getCamelcaseCapitalName(); ?>Repository $<?= lcfirst($generator->getCamelcaseCapitalName()); ?>Repository,
        sR $sR,
        #[RouteArgument('page')] int $page = 1,
    ): Response {
        $<?= $generator->getSmallPluralName(); ?> = $this-><?= $generator->getSmallPluralName(); ?>($<?= lcfirst($generator->getCamelcaseCapitalName()); ?>Repository);
        $parameters = [
            '<?= $generator->getSmallPluralName(); ?>' => $<?= $generator->getSmallPluralName(); ?>,
            'page' => $page > 0 ? $page : 1,
            'alert' => $this->alert(),
            'defaultPageSizeOffsetPaginator' => $sR->getSetting('default_list_limit')
                ? (int) $sR->getSetting('default_list_limit') : 1,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    public function delete(
        <?= $generator->getCamelcaseCapitalName(); ?>Repository $<?= lcfirst($generator->getCamelcaseCapitalName()); ?>Repository,
        #[RouteArgument('id')] int $id,
    ): Response {
        try {
            $<?= $generator->getSmallSingularName(); ?> = $this-><?= $generator->getSmallSingularName(); ?>($<?= lcfirst($generator->getCamelcaseCapitalName()); ?>Repository, $id);
            if ($<?= $generator->getSmallSingularName(); ?>) {
                $this-><?= $generator->getSmallSingularName(); ?>Service->delete<?= $generator->getCamelcaseCapitalName(); ?>($<?= $generator->getSmallSingularName(); ?>);
                $this->flashMessage('info',
                    $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('<?= $generator->getSmallSingularName(); ?>/index');
            }
            return $this->webService->getRedirectResponse('<?= $generator->getSmallSingularName(); ?>/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('<?= $generator->getSmallSingularName(); ?>/index');
        }
    }

    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        <?= $generator->getCamelcaseCapitalName(); ?>Repository $<?= $generator->getSmallSingularName(); ?>Repository,
<?php
$rel = '';
/**
 * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
 */
foreach ($relations as $relation) {
    $rel .= '        '
            . ($relation->getCamelcaseName() ?? '#')
            . 'Repository $'
            . ($relation->getLowercaseName() ?? '#')
            . 'Repository,' . "\n";
}
echo rtrim($rel, "\n");
if (!empty($rel)) {
    echo "\n";
}
?>
        #[RouteArgument('id')] int $id,
    ): Response {
        $<?= $generator->getSmallSingularName(); ?> = $this-><?= $generator->getSmallSingularName(); ?>($<?= $generator->getSmallSingularName(); ?>Repository, $id);
        if ($<?= $generator->getSmallSingularName(); ?>) {
            $form = <?= $generator->getCamelcaseCapitalName(); ?>Form::show($<?= $generator->getSmallSingularName(); ?>);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => '<?= $generator->getSmallSingularName(); ?>/edit',
                'actionArguments' => ['id' => $id],
                'errors' => [],
                'form' => $form,
<?php
$rel = '';
/**
 * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
 */
foreach ($relations as $relation) {
    $rel .= "                '"
            . ($relation->getLowercaseName() ?? '#')
            . "s' => $"
            . ($relation->getLowercaseName() ?? '#')
            . 'Repository->findAllPreloaded(),' . "\n";
}
echo rtrim($rel, "\n");
if (!empty($rel)) {
    echo "\n";
}
?>
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this-><?= $generator->getSmallSingularName(); ?>Service->save<?= $generator->getCamelcaseCapitalName(); ?>($<?= $generator->getSmallSingularName(); ?>, $body);
                        return $this->webService->getRedirectResponse('<?= $generator->getSmallSingularName(); ?>/index');
                    }
                    $parameters['errors'] = $form->getValidationResult()
                                                 ->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('<?= $generator->getSmallSingularName(); ?>/index');
    }

    /**
     * @param <?= $generator->getCamelcaseCapitalName(); ?>Repository $<?= $generator->getSmallSingularName(); ?>Repository
     * @param int $id
     * @return <?= $generator->getCamelcaseCapitalName(); ?>|null
     */
    private function <?= $generator->getSmallSingularName(); ?>(
        <?= $generator->getCamelcaseCapitalName(); ?>Repository $<?= $generator->getSmallSingularName(); ?>Repository,
        int $id,
    ): <?= $generator->getCamelcaseCapitalName(); ?>|null {
        if ($id) {
            return $<?= $generator->getSmallSingularName(); ?>Repository->repo<?= $generator->getCamelcaseCapitalName(); ?>Query($id);
        }
        return null;
    }

    /**
     * @return EntityReader
     * @psalm-return EntityReader
     */
    private function <?= $generator->getSmallPluralName(); ?>(
        <?= $generator->getCamelcaseCapitalName(); ?>Repository $<?= $generator->getSmallSingularName(); ?>Repository,
    ): EntityReader {
        return $<?= $generator->getSmallSingularName(); ?>Repository->findAllPreloaded();
    }

    /**
     * @param <?= $generator->getCamelcaseCapitalName(); ?>Repository $<?= $generator->getSmallSingularName(); ?>Repository
     * @param int $id
     * @return Response
     */
    public function view(
        <?= $generator->getCamelcaseCapitalName(); ?>Repository $<?= $generator->getSmallSingularName(); ?>Repository,
        #[RouteArgument('id')] int $id,
    ): Response {
        $<?= $generator->getSmallSingularName(); ?> = $this-><?= $generator->getSmallSingularName(); ?>($<?= $generator->getSmallSingularName(); ?>Repository, $id);
        if ($<?= $generator->getSmallSingularName(); ?>) {
            $form = <?= $generator->getCamelcaseCapitalName(); ?>Form::show($<?= $generator->getSmallSingularName(); ?>);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => '<?= $generator->getSmallSingularName(); ?>/view',
                'actionArguments' => ['id' => $id],
                'form' => $form,
                '<?= $generator->getSmallPluralName(); ?>' => $<?= $generator->getSmallSingularName(); ?>,
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('<?= $generator->getSmallSingularName(); ?>/index');
    }
}
