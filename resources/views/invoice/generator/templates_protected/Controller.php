<?php

    declare(strict_types=1);

   /**
    * @see GeneratorController function controller
    * @var App\Invoice\Entity\Gentor $generator
    * @var Cycle\Database\Table $orm_schema
    * @var array $relations
    */
    
    echo "<?php\n";             
?>

declare(strict_types=1); 

namespace <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>;

use <?= $generator->getNamespace_path(). DIRECTORY_SEPARATOR. 'Entity'. DIRECTORY_SEPARATOR. $generator->getCamelcase_capital_name(); ?>;
use <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>Form;
use <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>Service;
use <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>Repository;
use App\Invoice\Setting\SettingRepository;

<?php
  /**
   * @var App\Invoice\Entity\GentorRelation $relation
   */ 
  foreach ($relations as $relation) { 
    echo 'use ' . $generator->getNamespace_path() .DIRECTORY_SEPARATOR. ($relation->getCamelcase_name() ?? '#') .DIRECTORY_SEPARATOR.($relation->getCamelcase_name() ?? '#') .'Repository;'."\n"; 
  } 
?>
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

use \Exception;

final class <?= $generator->getCamelcase_capital_name(); ?>Controller
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private <?= $generator->getCamelcase_capital_name(); ?>Service $<?= $generator->getSmall_singular_name(); ?>Service;
    <?= 'private const '.strtoupper($generator->getSmall_plural_name())."_PER_PAGE = 1;"."\n"; ?>
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        <?= $generator->getCamelcase_capital_name(); ?>Service $<?= $generator->getSmall_singular_name(); ?>Service,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('<?= $generator->getRoute_prefix().'/'.$generator->getRoute_suffix(); ?>')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@<?= ltrim($generator->getController_layout_dir_dot_path(),'/'); ?>');
        $this->webService = $webService;
        $this->userService = $userService;
        $this-><?= $generator->getSmall_singular_name(); ?>Service = $<?= $generator->getSmall_singular_name(); ?>Service;
        $this->translator = $translator;
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
                            $rel .= '                        '.($relation->getCamelcase_name() ?? '#').'Repository $'.($relation->getLowercase_name() ?? '#').'Repository,'."\n";
                        }
                        echo rtrim($rel,",\n")."\n";        
                        ?>
    ) : Response
    {
        $<?= $generator->getSmall_singular_name(); ?> = new <?= $generator->getCamelcase_capital_name(); ?>();
        $form = new <?= $generator->getCamelcase_capital_name(); ?>Form($<?= $generator->getSmall_singular_name(); ?>);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'action' => ['<?= $generator->getSmall_singular_name(); ?>/add'],
            'errors' => [],
            'form' => $form,
            <?php echo "\n";
            
           /**
            * @var App\Invoice\Entity\GentorRelation $relation
            */
            foreach ($relations as $relation) {
                echo "            '".($relation->getLowercase_name() ?? '#')."s'=>".'$'.($relation->getLowercase_name() ?? '#').'Repository->findAllPreloaded(),'."\n";
            }
            ?>
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            //echo \Yiisoft\VarDumper\Vardumper::dump($body);
            /**
             * @psalm-suppress PossiblyInvalidArgument $body 
             */
            if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                $this-><?= $generator->getSmall_singular_name(); ?>Service->save<?= $generator->getCamelcase_capital_name(); ?>($<?= $generator->getSmall_singular_name(); ?>, $body);
                return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name(); ?>/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert'
        [
            'flash'=>$this->flash,
            'errors' => [],
        ]);
    }
        
    public function index(<?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name(); ?>Repository, 
                          SettingRepository $settingRepository, #[RouteArgument('page')] int $page = 1): Response
    {      
      $<?= $generator->getSmall_singular_name(); ?> = $<?= $generator->getSmall_singular_name(); ?>Repository->findAllPreloaded();
      $paginator = (new OffsetPaginator($<?= $generator->getSmall_singular_name(); ?>))
      ->withPageSize((int) $settingRepository->getSetting('default_list_limit'))
      ->withCurrentPage($page)
      ->withToken(PageToken::next((string)$page));
      $parameters = [
      '<?= $generator->getSmall_singular_name(); ?>s' => $this-><?= $generator->getSmall_singular_name(); ?>s($<?= $generator->getSmall_singular_name(); ?>Repository),
      'paginator' => $paginator,
      'alert' => $this->alert(),
      'defaultPageSizeOffsetPaginator' => $settingRepository->getSetting('default_list_limit')
                                                    ? (int)$settingRepository->getSetting('default_list_limit') : 1
    ];
    return $this->viewRenderer->render('<?= $generator->getSmall_singular_name(); ?>/index', $parameters);
    }
    
    /**
     * @param <?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name();?>Repository
     * @param int $id
     * @return Response
     */
    public function delete(<?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name();?>Repository, #[RouteArgument('id')] int $id 
    ): Response {
        try {
            $<?= $generator->getSmall_singular_name();?> = $this-><?= $generator->getSmall_singular_name();?>($<?= $generator->getSmall_singular_name();?>Repository, $id);
            if ($<?= $generator->getSmall_singular_name();?>) {
                $this-><?= $generator->getSmall_singular_name();?>Service->delete<?= $generator->getCamelcase_capital_name(); ?>($<?= $generator->getSmall_singular_name();?>);               
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name();?>/index'); 
            }
            return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name();?>/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name();?>/index'); 
        }
    }
        
    public function edit(Request $request, 
                         FormHydrator $formHydrator,
        <?php if ($generator->getCamelcase_capital_name()) {  
            echo $generator->getCamelcase_capital_name().'Repository '. '$'.$generator->getSmall_singular_name().'Repository,';
        }
        ?> 
        <?php
        $rel = '';
        echo "\n";
        /**
         * @var App\Invoice\Entity\GentorRelation $relation
         */
        foreach ($relations as $relation) {
            $rel .= '                        '.($relation->getCamelcase_name() ?? '#').'Repository $'.($relation->getLowercase_name() ?? '#').'Repository,'."\n";
        }
        echo rtrim($rel,",\n").",";
        ?>#[RouteArgument('id')] int $id): Response {
        $<?= $generator->getSmall_singular_name(); ?> = $this-><?= $generator->getSmall_singular_name(); ?>($<?= $generator->getSmall_singular_name(); ?>Repository, $id);
        if ($<?= $generator->getSmall_singular_name(); ?>){
            $form = new <?= $generator->getCamelcase_capital_name(); ?>Form($<?= $generator->getSmall_singular_name(); ?>);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['<?= $generator->getSmall_singular_name(); ?>/edit', ['id' => $id]],
                'errors' => [],
                'form' => $form,
                <?php
                    $rel = '';
                    /**
                     * @var App\Invoice\Entity\GentorRelation $relation
                     */
                    foreach ($relations as $relation) {
                      $rel .= "            '".($relation->getLowercase_name() ?? '#')."s'=>".'$'.($relation->getLowercase_name() ?? '#').'Repository->findAllPreloaded(),'."\n";
                    }
                    echo rtrim($rel,",\n")."\n";
                ?>
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                //echo \Yiisoft\VarDumper\Vardumper::dump($body);
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body 
                     */
                    $this-><?= $generator->getSmall_singular_name();?>Service->save<?= $generator->getCamelcase_capital_name(); ?>($<?= $generator->getSmall_singular_name();?>, $body);
                    return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name(); ?>/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name(); ?>/index');
    }
    
     /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
    
    //For rbac refer to AccessChecker    
    
    /**     
     * @param <?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository
     * @param int $id
     * @return <?= $generator->getCamelcase_capital_name();?>|null
     */
    private function <?= $generator->getSmall_singular_name();?>(<?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository, int $id) : <?= $generator->getCamelcase_capital_name();?>|null
    {
        if ($id) {
            $<?= $generator->getSmall_singular_name();?> = $<?= $generator->getSmall_singular_name();?>Repository->repo<?= $generator->getCamelcase_capital_name();?>Loadedquery((string)$id);
            return $<?= $generator->getSmall_singular_name();?>;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function <?= $generator->getSmall_plural_name();?>(<?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository) : \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $<?= $generator->getSmall_plural_name();?> = $<?= $generator->getSmall_singular_name();?>Repository->findAllPreloaded();        
        return $<?= $generator->getSmall_plural_name();?>;
    }
        
    /**
     * @param <?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name(); ?>Repository
     * @param SettingRepository $settingRepository
     * @param int id
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(<?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name();?>Repository, #[RouteArgument('id')] int $id) 
                         : \Yiisoft\DataResponse\DataResponse|Response 
    {
        $<?= $generator->getSmall_singular_name(); ?> = $this-><?= $generator->getSmall_singular_name(); ?>($<?= $generator->getSmall_singular_name(); ?>Repository, $id); 
        if ($<?= $generator->getSmall_singular_name(); ?>) {
            $form = new <?= $generator->getCamelcase_capital_name(); ?>Form($<?= $generator->getSmall_singular_name(); ?>);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['<?= $generator->getSmall_singular_name(); ?>/view', ['id' => $id]],
                'form' => $form,
                '<?= $generator->getSmall_singular_name();?>'=>$<?= $generator->getSmall_singular_name();?>,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name(); ?>/index');
    }
}

