<?php

declare(strict_types=1);

/**
 * Related logic: see GeneratorController function controller
 * @var App\Invoice\Entity\Gentor $generator
 * @var Cycle\Database\Table $orm_schema
 * @var array $relations
 */

echo "<?php\n";
?>

declare(strict_types=1); 

namespace <?= $generator->getNamespacePath()
        . DIRECTORY_SEPARATOR
        . $generator->getCamelcaseCapitalName(); ?>;

use App\Invoice\BaseController;
use <?= $generator->getNamespacePath()
                . DIRECTORY_SEPARATOR
                . 'Entity'
                . DIRECTORY_SEPARATOR
                . $generator->getCamelcaseCapitalName(); ?>;
use <?= $generator->getNamespacePath()
                . DIRECTORY_SEPARATOR
                . $generator->getCamelcaseCapitalName()
                . DIRECTORY_SEPARATOR
                . $generator->getCamelcaseCapitalName(); ?>Form;
use <?= $generator->getNamespacePath()
                . DIRECTORY_SEPARATOR
                . $generator->getCamelcaseCapitalName()
                . DIRECTORY_SEPARATOR
                . $generator->getCamelcaseCapitalName(); ?>Service;
use <?= $generator->getNamespacePath()
                . DIRECTORY_SEPARATOR
                . $generator->getCamelcaseCapitalName()
                . DIRECTORY_SEPARATOR
                . $generator->getCamelcaseCapitalName(); ?>Repository;
use App\Invoice\Setting\SettingRepository as sR;

<?php
  /**
   * @var App\Invoice\Entity\GentorRelation $relation
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
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\{
    ResponseInterface as Response, ServerRequestInterface as Request}

use Yiisoft\{Data\Cycle\Reader\EntityReader, Data\Paginator\PageToken,
    Data\Paginator\OffsetPaginator,
    DataResponse\ResponseFactory\DataResponseFactoryInterface,
    FormModel\FormHydrator, Input\Http\Attribute\Parameter\Query,
    Http\Method, Router\FastRoute\UrlGenerator, Router\HydratorAttribute\RouteArgument,
    Session\SessionInterface, Session\Flash\Flash, Translator\TranslatorInterface,
    Yii\View\Renderer\WebViewRenderer}

use \Exception;

final class <?= $generator->getCamelcaseCapitalName(); ?>
                Controller extends BaseController
{
    protected string $controllerName = 'invoice/
            <?= $generator->getSmallSingularName(); ?>';

    public function __construct(
        private <?= $generator->getCamelcaseCapitalName(); ?>
            Service $<?= $generator->getSmallSingularName(); ?>Service,
        private UrlGenerator,    
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    )
    ) {
        parent::__construct($webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
        $this-><?= $generator->getSmallSingularName(); ?>Service = $
            <?= $generator->getSmallSingularName(); ?>Service;
        $this->urlGenerator = $urlGenerator;    
    }
    
    public function add(Request $request, 
                        FormHydrator $formHydrator,
                        <?php
                        $rel = '';
echo "\n";
/**
 * @var App\Invoice\Entity\GentorRelation $relation
 */
foreach ($relations as $relation) {
    $rel .= '                        '
            . ($relation->getCamelcaseName() ?? '#')
            . 'Repository $'
            . lcfirst($relation->getCamelcaseName() ?? '#')
            . 'Repository,'
            . "\n";
}
echo rtrim($rel, ",\n") . "\n";
?>
    ) : Response
    {
        $<?= $generator->getSmallSingularName(); ?> = new 
         <?= $generator->getCamelcaseCapitalName(); ?>();
        $form = new 
            <?= $generator->getCamelcaseCapitalName(); ?>
            Form($<?= $generator->getSmallSingularName(); ?>);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => '<?= $generator->getSmallSingularName(); ?>/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            <?php echo "\n";

/**
 * @var App\Invoice\Entity\GentorRelation $relation
 */
foreach ($relations as $relation) {
    echo "            '"
        . ($relation->getLowercaseName() ?? '#')
        . "s'=>"
        . '$'
        . ($relation->getLowercaseName() ?? '#')
        . 'Repository->findAllPreloaded(),' . "\n";
}
?>
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $this-><?= $generator->getSmallSingularName(); ?>Service->save
                    <?= $generator->getCamelcaseCapitalName(); ?>($
                    <?= $generator->getSmallSingularName(); ?>, $body);
                    return $this->webService->getRedirectResponse('
                    <?= $generator->getSmallSingularName(); ?>/index');
                }
                $parameters['errors'] = $form->getValidationResult()
                                             ->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            } // is_array($body)   
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }
        
    public function index(
        <?= $generator->getCamelcaseCapitalName(); ?>Repository $
        <?= lcfirst($generator->getCamelcaseCapitalName()); ?>Repository, 
        sR $sR,
        #[RouteArgument('page')] string $page = '1',
        #[Query('page')]
        ?string $queryPage = null,
        #[Query('sort')]
        ?string $querySort = null
    ): Response
    {      
      $<?= $generator->getSmallSingularName(); ?> = $
      <?= lcfirst($generator->getCamelcaseCapitalName()); ?>
      Repository->findAllPreloaded();
      $page = $queryPage ?? $page;
      $parameters = [
      '<?= $generator->getSmallSingularName(); ?>s' => $this->
      <?= $generator->getSmallSingularName(); ?>
      s($<?= lcfirst($generator->getCamelcaseCapitalName()); ?>Repository),
      'page' => (int) $page > 0 ? (int) $page : 1,
      'sortString' => $querySort ?? '-id',
      'alert' => $this->alert(),
      'defaultPageSizeOffsetPaginator' => $sR->getSetting('default_list_limit')
            ? (int)$sR->getSetting('default_list_limit') : 1
      ];
    return $this->webViewRenderer->render('index', $parameters);
    }
    
    /**
     * @param 
      <?= $generator->getCamelcaseCapitalName(); ?>Repository $
      <?= $generator->getSmallSingularName();?>Repository
     * @param int $id
     * @return Response
     */
    public function delete(
      <?= $generator->getCamelcaseCapitalName(); ?>Repository $
      <?= lcfirst($generator->getCamelcaseCapitalName());?>Repository,
        #[RouteArgument('id')] int $id 
    ): Response {
        try {
            $<?= $generator->getSmallSingularName();?> = $this->
            <?= $generator->getSmallSingularName();?>($
            <?= lcfirst($generator->getCamelcaseCapitalName());?>Repository, $id);
            if ($<?= $generator->getSmallSingularName();?>) {
                $this-><?= $generator->getSmallSingularName();?>Service->delete
                <?= $generator->getCamelcaseCapitalName(); ?>($
                <?= $generator->getSmallSingularName();?>);               
                $this->flashMessage('info',
                    $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('
                <?= $generator->getSmallSingularName();?>/index'); 
            }
            return $this->webService->getRedirectResponse('
                <?= $generator->getSmallSingularName();?>/index'); 
	} catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('
                <?= $generator->getSmallSingularName();?>/index'); 
        }
    }
        
    public function edit(Request $request, 
                         FormHydrator $formHydrator,
        <?php if ($generator->getCamelcaseCapitalName()) {
            echo $generator->getCamelcaseCapitalName()
                    . 'Repository '
                    . '$'
                    . $generator->getSmallSingularName()
                    . 'Repository,';
        }
?> 
        <?php
$rel = '';
echo "\n";
/**
 * @var App\Invoice\Entity\GentorRelation $relation
 */
foreach ($relations as $relation) {
    $rel .= '                        '
            . ($relation->getCamelcaseName() ?? '#')
            . 'Repository $'
            . ($relation->getLowercaseName() ?? '#')
            . 'Repository,' . "\n";
}
echo rtrim($rel, ",\n") . ",";
?>#[RouteArgument('id')] int $id): Response {
        $<?= $generator->getSmallSingularName(); ?> = $this->
        <?= $generator->getSmallSingularName(); ?>($
        <?= $generator->getSmallSingularName(); ?>Repository, $id);
        if ($<?= $generator->getSmallSingularName(); ?>){
            $form = new 
            <?= $generator->getCamelcaseCapitalName(); ?>Form($
            <?= $generator->getSmallSingularName(); ?>);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => '<?= $generator->getSmallSingularName(); ?>/edit', 
                'actionArguments' => ['id' => $id],
                'errors' => [],
                'form' => $form,
                <?php
            $rel = '';
/**
 * @var App\Invoice\Entity\GentorRelation $relation
 */
foreach ($relations as $relation) {
    $rel .= "            '"
            . ($relation->getLowercaseName() ?? '#')
            . "s'=>"
            . '$'
            . ($relation->getLowercaseName() ?? '#')
            . 'Repository->findAllPreloaded(),' . "\n";
}
echo rtrim($rel, ",\n") . "\n";
?>
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                //echo \Yiisoft\VarDumper\Vardumper::dump($body);
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                        $this->
                    <?= $generator->getSmallSingularName();?>Service->save
                    <?= $generator->getCamelcaseCapitalName(); ?>($
                    <?= $generator->getSmallSingularName();?>, $body);
                        return $this->webService->getRedirectResponse('
                            <?= $generator->getSmallSingularName(); ?>/index');
                    }
                    $parameters['errors'] =
                        $form->getValidationResult()
                             ->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }    
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('
                    <?= $generator->getSmallSingularName(); ?>/index');
    }
    
    //For rbac refer to AccessChecker    
    
    /**     
     * @param <?= $generator->getCamelcaseCapitalName();?>Repository $
                    <?= $generator->getSmallSingularName();?>Repository
     * @param int $id
     * @return <?= $generator->getCamelcaseCapitalName();?>|null
     */
    private function <?= $generator->getSmallSingularName();?>(
        <?= $generator->getCamelcaseCapitalName();?>Repository $
        <?= $generator->getSmallSingularName();?>Repository, int $id) : 
        <?= $generator->getCamelcaseCapitalName();?>|null
    {
        if ($id) {
            $<?= $generator->getSmallSingularName();?> = $
            <?= $generator->getSmallSingularName();?>Repository->repo
            <?= $generator->getCamelcaseCapitalName();?>Query((string)$id);
            return $<?= $generator->getSmallSingularName();?>;
        }
        return null;
    }

    /**
     * @return EntityReader
     *
     * @psalm-return EntityReader
     */
    private function <?= $generator->getSmallPluralName();?>(
            <?= $generator->getCamelcaseCapitalName();?>Repository $
            <?= $generator->getSmallSingularName();?>Repository) : EntityReader
    {
        $<?= $generator->getSmallPluralName();?> = $
         <?= $generator->getSmallSingularName();?>Repository->findAllPreloaded(); 
        return $<?= $generator->getSmallPluralName();?>;
    }
        
    /**
     * @param <?= $generator->getCamelcaseCapitalName(); ?>Repository $
        <?= $generator->getSmallSingularName(); ?>Repository
     * @param int $id
     * @return Response
     */
    public function view(<?= $generator->getCamelcaseCapitalName(); ?>
    Repository $
        <?= $generator->getSmallSingularName();?>Repository,
            #[RouteArgument('id')] int $id) 
                         : Response 
    {
        $<?= $generator->getSmallSingularName(); ?> = $this->
        <?= $generator->getSmallSingularName(); ?>($
        <?= $generator->getSmallSingularName(); ?>Repository, $id); 
        if ($<?= $generator->getSmallSingularName(); ?>) {
            $form = new <?= $generator->getCamelcaseCapitalName(); ?>Form($
            <?= $generator->getSmallSingularName(); ?>);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => '
            <?= $generator->getSmallSingularName(); ?>/view', 
                'actionArguments' => ['id' => $id],
                'form' => $form,
                '<?= $generator->getSmallPluralName();?>' => $
                <?= $generator->getSmallSingularName();?>,
            ];        
        return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('
                <?= $generator->getSmallSingularName(); ?>/index');
    }
}
