<?php
   echo "<?php\n";             
?>

declare(strict_types=1); 

namespace <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>;

use <?= $generator->getNamespace_path(). DIRECTORY_SEPARATOR. 'Entity'. DIRECTORY_SEPARATOR. $generator->getCamelcase_capital_name(); ?>;
use <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>Form;
use <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>Service;
use <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>Repository;

<?php
  foreach ($relations as $relation) { 
    echo 'use ' . $generator->getNamespace_path() .DIRECTORY_SEPARATOR. $relation->getCamelcase_name().DIRECTORY_SEPARATOR.$relation->getCamelcase_name() .'Repository;'."\n"; 
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
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

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
                                           ->withLayout('@<?= $generator->getController_layout_dir_dot_path() ?>');
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
                        foreach ($relations as $relation) {
                            $rel .= '                        '.$relation->getCamelcase_name().'Repository $'.$relation->getLowercase_name().'Repository,'."\n";
                        }
                        echo rtrim($rel,",\n")."\n";        
                        ?>
    ) : Response
    {
        $<?= $generator->getSmall_singular_name(); ?> = new <?= $generator->getCamelcase_capital_name(); ?>();
        $form = new Form($<?= $generator->getSmall_singular_name(); ?>);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'action' => ['<?= $generator->getSmall_singular_name(); ?>/add'],
            'errors' => [],
            'form' => $form,
            <?php echo "\n";
            foreach ($relations as $relation) {
                echo "            '".$relation->getLowercase_name()."s'=>".'$'.$relation->getLowercase_name().'Repository->findAllPreloaded(),'."\n";
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
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash,
            'errors' => [],
        ]);
    }
        
    public function index(CurrentRoute $currentRoute, 
                          <?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name(); ?>Repository, 
                          SettingRepository $settingRepository): Response
    {      
      $page = $currentRoute->getArgument('page', '1');
      $<?= $generator->getSmall_singular_name(); ?> = $<?= $generator->getSmall_singular_name(); ?>Repository->findAllPreloaded();
      $paginator = (new OffsetPaginator($<?= $generator->getSmall_singular_name(); ?>))
      ->withPageSize((int) $settingRepository->get_setting('default_list_limit'))
      ->withCurrentPage((int)$page)
      ->withToken(PageToken::next((string)$page));
      $parameters = [
      '<?= $generator->getSmall_singular_name(); ?>s' => $this-><?= $generator->getSmall_singular_name(); ?>s($<?= $generator->getSmall_singular_name(); ?>Repository),
      'paginator' => $paginator,
      'alert' => $this->alert(),
      'max' => (int) $settingRepository->get_setting('default_list_limit'),
      'grid_summary' => $settingRepository->grid_summary($paginator, $this->translator, (int) $settingRepository->get_setting('default_list_limit'), $this->translator->translate('plural'), ''),
    ];
    return $this->viewRenderer->render('/invoice/<?= $generator->getSmall_singular_name(); ?>/index', $parameters);
    }
    
    /**
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param <?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name();?>Repository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, 
                           CurrentRoute $currentRoute,<?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name();?>Repository 
    ): Response {
        try {
            $<?= $generator->getSmall_singular_name();?> = $this-><?= $generator->getSmall_singular_name();?>($currentRoute, $<?= $generator->getSmall_singular_name();?>Repository);
            if ($<?= $generator->getSmall_singular_name();?>) {
                $this-><?= $generator->getSmall_singular_name();?>Service->delete<?= $generator->getCamelcase_capital_name(); ?>($<?= $generator->getSmall_singular_name();?>);               
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name();?>/index'); 
            }
            return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name();?>/index'); 
	} catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name();?>/index'); 
        }
    }
        
    public function edit(Request $request, 
                         CurrentRoute $currentRoute, 
                         FormHydrator $formHydrator,
        <?php if ($generator->getCamelcase_capital_name()) {  
            echo $generator->getCamelcase_capital_name().'Repository '. '$'.$generator->getSmall_singular_name().'Repository,';
        }
        ?> 
        <?php
        $rel = '';
        echo "\n";
        foreach ($relations as $relation) {
            $rel .= '                        '.$relation->getCamelcase_name().'Repository $'.$relation->getLowercase_name().'Repository,'."\n";
        }
        echo rtrim($rel,",\n")."\n";
        ?>
    ): Response {
        $<?= $generator->getSmall_singular_name(); ?> = $this-><?= $generator->getSmall_singular_name(); ?>($currentRoute, $<?= $generator->getSmall_singular_name(); ?>Repository);
        if ($<?= $generator->getSmall_singular_name(); ?>){
            $form = new Form($<?= $generator->getSmall_singular_name(); ?>);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['<?= $generator->getSmall_singular_name(); ?>/edit', ['id' => $<?= $generator->getSmall_singular_name();?>->getId()]],
                'errors' => [],
                'form' => $form,
                <?php
                    $rel = '';
                    foreach ($relations as $relation) {
                      $rel .= "            '".$relation->getLowercase_name()."s'=>".'$'.$relation->getLowercase_name().'Repository->findAllPreloaded(),'."\n";
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
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name(); ?>/index');
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash{
        $this->flash->add($level, $message, true); 
        return $this->flash;
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param <?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository
     * @return <?= $generator->getCamelcase_capital_name();?>|null
     */
    private function <?= $generator->getSmall_singular_name();?>(CurrentRoute $currentRoute,<?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository) : <?= $generator->getCamelcase_capital_name();?>|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $<?= $generator->getSmall_singular_name();?> = $<?= $generator->getSmall_singular_name();?>Repository->repo<?= $generator->getCamelcase_capital_name();?>Loadedquery($id);
            return $<?= $generator->getSmall_singular_name();?>;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function <?= $generator->getSmall_plural_name();?>(<?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $<?= $generator->getSmall_plural_name();?> = $<?= $generator->getSmall_singular_name();?>Repository->findAllPreloaded();        
        return $<?= $generator->getSmall_plural_name();?>;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param <?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name(); ?>Repository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,
                         <?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name();?>Repository) 
                         : \Yiisoft\DataResponse\DataResponse|Response 
    {
        $<?= $generator->getSmall_singular_name(); ?> = $this-><?= $generator->getSmall_singular_name(); ?>($currentRoute, $<?= $generator->getSmall_singular_name(); ?>Repository); 
        if ($<?= $generator->getSmall_singular_name(); ?>) {
            $form = new Form($<?= $generator->getSmall_singular_name(); ?>);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['<?= $generator->getSmall_singular_name(); ?>/view', ['id' => $<?= $generator->getSmall_singular_name();?>->getId()]],
                'form' => $form,
                '<?= $generator->getSmall_singular_name();?>'=>$<?= $generator->getSmall_singular_name();?>,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name(); ?>/index');
    }
}

