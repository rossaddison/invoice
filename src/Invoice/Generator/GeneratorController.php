<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;
use App\Invoice\Generator\GeneratorForm;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Generator\GeneratorService;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use App\Invoice\Helpers\CaCertFileNotFoundException;
use App\Invoice\Helpers\GoogleTranslateJsonFileNotFoundException;
use App\Invoice\Helpers\GoogleTranslateLocaleSettingNotFoundException;
use App\Invoice\Helpers\GenerateCodeFileHelper;
use App\Invoice\Libraries\Lang;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Cycle\Database\DatabaseManager;

use Google\Cloud\Translate\V3\TranslationServiceClient;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\View\View;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\Json\Json;
use Yiisoft\Files\FileHelper;

final class GeneratorController
{
    private DataResponseFactoryInterface $factory;
    private GeneratorService $generatorService;   
    private Session $session;
    private Flash $flash;
    private TranslatorInterface $translator;
    private UserService $userService;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private Aliases $aliases;
    const ENTITY = 'Entity.php';
    const REPO = 'Repository.php';
    const FORM = 'Form.php';
    const SERVICE = 'Service.php';
    const MAPPER = 'Mapper.php';
    const SCOPE = 'Scope.php';
    const CONTROLLER = 'Controller.php';
    const INDEX = 'index.php';
    const INDEX_ADV_PAGINATOR = 'index_adv_paginator.php';
    const INDEX_ADV_PAGINATOR_WITH_FILTER = 'index_adv_paginator_with_filter.php';
    const _FORM = '_form.php';     
    const _VIEW = '_view.php';
    const _ROUTE = '_route.php';
    
    // e.g. i.this_is_a_sentence
    const _IP = '_ip_lang.php';
    
    // e.g. g.this_is_a_gateway 
    const _GATEWAY = '_gateway_lang.php';
    
    // e.g. a complete file that is too big for Google to translate
    const _APP = '_app.php';
    
    // e.g. invoice.invoice.this.is.a.sentence 
    const _LATEST = '_latest_lang.php';
    
    // e.g. site.soletrader.contact.address
    const _COMMON = '_common_lang.php';
    
    // e.g miscellaneous
    const _ANY = '_any_lang.php';
    
    public function __construct(
        DataResponseFactoryInterface $factory,    
        GeneratorService $generatorService,
        Session $session,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
    ) {
        $this->factory = $factory;
        $this->generatorService = $generatorService;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->translator = $translator;
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/generator')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->aliases = $this->setAliases();
    }
    
    /**
     * @param GeneratorRepository $generatorRepository
     * @param SettingRepository $sR
     * @param GeneratorRelationRepository $grR
     */
    public function index(
            GeneratorRepository $generatorRepository,
            GeneratorRelationRepository $grR): \Yiisoft\DataResponse\DataResponse
    {
        $this->rbac();
        $generators = $this->generators($generatorRepository);
        $paginator = (new OffsetPaginator($generators));
        $parameters = [
            'grR' => $grR,
            'alert' => $this->alert(),
            'paginator' => $paginator 
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param DatabaseManager $dbal
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator, DatabaseManager $dbal): Response
    {
        $gentor = new Gentor();
        $form = new GeneratorForm($gentor);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'actionName' => 'generator/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'tables' => $dbal->database('default')->getTables(),
            'selected_table' => '',
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                $this->generatorService->saveGenerator($gentor, $body);
                return $this->webService->getRedirectResponse('generator/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param GeneratorRepository $generatorRepository
     * @param FormHydrator $formHydrator
     * @param DatabaseManager $dbal
     * @return Response
     */
    public function edit(CurrentRoute $currentRoute, Request $request, GeneratorRepository $generatorRepository, FormHydrator $formHydrator, DatabaseManager $dbal): Response 
    {
        $generator = $this->generator($currentRoute, $generatorRepository);
        if ($generator) {
            $form = new GeneratorForm($generator);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'generator/edit', 
                'actionArguments' => ['id' => $generator->getGentor_id()],
                'errors' => [],
                'form' => $form,
                'tables' => $dbal->database('default')->getTables(),
                'selected_table' => $generator->getPre_entity_table(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body 
                     */
                    $this->generatorService->saveGenerator($generator, $body);
                    $this->flash_message('warning', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('generator/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        } else {
            return $this->webService->getRedirectResponse('generator/index');
        }    
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $generatorRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, GeneratorRepository $generatorRepository) : Response 
    {
        try {
            $generator = $this->generator($currentRoute, $generatorRepository);
            if ($generator) {
                $this->flash_message('danger', $this->translator->translate('i.record_successfully_deleted'));
                $this->generatorService->deleteGenerator($generator);
                return $this->webService->getRedirectResponse('generator/index');  
            }           
            return $this->webService->getRedirectResponse('generator/index');
        }
        catch (\Exception $e) {
           unset($e);  
           $this->flash_message('danger', $this->translator->translate('invoice.generator.history'));
        }
        return $this->webService->getRedirectResponse('generator/index');   
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $generatorRepository
     * @param DatabaseManager $dbal 
     */
    public function view(
        CurrentRoute $currentRoute, 
        GeneratorRepository $generatorRepository,
    ): Response {
        $generator = $this->generator($currentRoute, $generatorRepository);
        if ($generator) {
            $form = new GeneratorForm($generator);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'generator/view', 
                'actionArguments' => ['id' => $generator->getGentor_id()],
                'generator' => $generator,
                'form' => $form, 
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('generator/index');   
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('generator/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $generatorRepository
     * @return Gentor|null
     */
    private function generator(CurrentRoute $currentRoute, GeneratorRepository $generatorRepository): Gentor|null{
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
            $generator = $generatorRepository->repoGentorQuery($id);
            return $generator; 
        }
        return null;
    }
   
    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function generators(GeneratorRepository $generatorRepository): \Yiisoft\Data\Cycle\Reader\EntityReader {
        $generators = $generatorRepository->findAllPreloaded();
        return $generators;
    }
    
    /**
     * @return string
     */
     private function alert(): string {
      return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
      [ 
        'flash' => $this->flash
      ]);
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
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function entity(CurrentRoute $currentRoute, GeneratorRepository $gr, GeneratorRelationRepository $grr,
                           DatabaseManager $dbal, View $view): Response {
        $file = self::ENTITY;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $camelcaseFileName = $g->getCamelcase_capital_name(). '.php';
        $viewPath = $this->aliases->get('@Entity');
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        
        $build_file = $this->build_and_save($viewPath, $content, '.php', $g->getCamelcase_capital_name());
        $this->flash_message('success', $camelcaseFileName.$this->translator->translate('invoice.generator.generated'). $viewPath.'/'.$camelcaseFileName);
        
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate') .$file,
            'generator' => $g,
            'orm_schema' =>$orm,
            'relations' => $relations,
            'alert'=> $this->alert(),
            'generated' => $build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function repo(CurrentRoute $currentRoute, GeneratorRepository $gr, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::REPO;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $camelcaseFileName = $g->getCamelcase_capital_name(). $file;
        $viewPath = $this->aliases->get('@Invoice').DIRECTORY_SEPARATOR.$g->getCamelcase_capital_name();
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        
        $build_file = $this->build_and_save($viewPath, $content, $camelcaseFileName, '');
        $this->flash_message('success', $camelcaseFileName.$this->translator->translate('invoice.generator.generated').$viewPath.'/'.$camelcaseFileName);
        
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate').$file,
            'generator' => $g,
            'orm_schema' =>$orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' =>$build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function service(CurrentRoute $currentRoute, GeneratorRepository $gr, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::SERVICE;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $camelcaseFileName = $g->getCamelcase_capital_name().$file;
        $viewPath = $this->aliases->get('@Invoice').DIRECTORY_SEPARATOR.$g->getCamelcase_capital_name();
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        
        $build_file = $this->build_and_save($viewPath, $content, $camelcaseFileName, '');
        $this->flash_message('success', $camelcaseFileName.$this->translator->translate('invoice.generator.generated').$viewPath.'/'.$camelcaseFileName);
        
        $parameters = [
            'canEdit'=>$this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate').$file,
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'alert'=> $this->alert(),
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function form(CurrentRoute $currentRoute, GeneratorRepository $gr, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::FORM;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $camelcaseFileName = $g->getCamelcase_capital_name().$file;
        $viewPath = $this->aliases->get('@Invoice').DIRECTORY_SEPARATOR.$g->getCamelcase_capital_name();
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPre_entity_table() */
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        
        $build_file = $this->build_and_save($viewPath, $content, $camelcaseFileName, '');
        $this->flash_message('success', $camelcaseFileName.$this->translator->translate('invoice.generator.generated').$viewPath.'/'.$camelcaseFileName);
        
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate').$file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function controller(CurrentRoute $currentRoute, GeneratorRepository $gr, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::CONTROLLER;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@Invoice').DIRECTORY_SEPARATOR.$g->getCamelcase_capital_name();
        $camelcaseFileName = $g->getCamelcase_capital_name().$file;
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPre_entity_table() */
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        $build_file = $this->build_and_save($viewPath, $content, $camelcaseFileName, '');
        $this->flash_message('success',  $camelcaseFileName.$this->translator->translate('invoice.generator.generated').$viewPath.'/'. $camelcaseFileName);
        $parameters = [
            'canEdit'=>$this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate').$file,
            'generator'=> $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert'=> $this->alert(),
            'generated' => $build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function _index(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                           GeneratorRelationRepository $grr,
                           DatabaseManager $dbal, View $view): Response 
    {
        $file = self::INDEX;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice').DIRECTORY_SEPARATOR.$g->getSmall_singular_name();
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPre_entity_table() */
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        
        $build_file = $this->build_and_save($viewPath, $content, $file, '');
        $this->flash_message('success', $file.$this->translator->translate('invoice.generator.generated').$viewPath.'/'.$file);
        
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate').$file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' =>$build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }
    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function _form(CurrentRoute $currentRoute, GeneratorRepository $gr, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::_FORM;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice').DIRECTORY_SEPARATOR.$g->getSmall_singular_name();
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        
        $build_file = $this->build_and_save($viewPath, $content, $file, '');
        $this->flash_message('success', $file.$this->translator->translate('invoice.generator.generated').$viewPath.'/'.$file);
        
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate').$file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function _view(CurrentRoute $currentRoute, GeneratorRepository $gr, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): 
        Response {
        $file = self::_VIEW;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice').DIRECTORY_SEPARATOR.$g->getSmall_singular_name();
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        
        // also generate a file into the folder created for this view
        $build_file = $this->build_and_save($viewPath, $content, $file, '');
        $this->flash_message('success', $file.$this->translator->translate('invoice.generator.generated').$viewPath.'/'.$file);
        
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate').$file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);        
    }
    
    //generate this individual route. Append to config/routes file.  
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function _route(CurrentRoute $currentRoute, GeneratorRepository $gr, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                           ): Response {
        $file = self::_ROUTE;
        $path = $this->aliases->get('@generated');
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $table_name = $g->getPre_entity_table();
        if (null==$table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $this->flash_message('success',$file.$this->translator->translate('invoice.generator.generated').$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac(),
            'title' => $this->translator->translate('invoice.generator.generate').$file,
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'alert'=> $this->alert(),
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }
    
    /**
     * @param CurrentUser $currentUser
     * @param DatabaseManager $dba
     */
    public function quick_view_schema(CurrentUser $currentUser, DatabaseManager $dba) : \Yiisoft\DataResponse\DataResponse{
        $parameters = [
            'alerts' => $this->alert(),
            'isGuest' => $currentUser->isGuest(),
            'tables' => $dba->database('default')->getTables(),
        ];
        return $this->viewRenderer->render('_schema', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $sR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     * @throws CaCertFileNotFoundException
     * @throws GoogleTranslateJsonFileNotFoundException
     * @throws GoogleTranslateLocaleSettingNotFoundException
     */
    public function google_translate_lang(CurrentRoute $currentRoute, SettingRepository $sR) : \Yiisoft\DataResponse\DataResponse|Response {
        // ? Downloaded https://curl.haxx.se/ca/cacert.pem" into 
        // c:\wamp64\bin\php\{active_php} eg. c:\wamp64\bin\php\php8.2.0
        // check your localhost phpinfo() (or create a script) for the location of your php.ini in wampserver
        // normally eg. Loaded Configuration File C:\wamp64\bin\apache\apache2.4.54.2\bin\php.ini
        // Edit this symlink file manually at [curl] with eg. "c:/wamp64/bin/php/php8.2.0/cacert.pem"
        // Note forward slashes and quotes 
        $type = $currentRoute->getArgument('type');
        if (null!==$type) {
            $curlcertificate = \ini_get('curl.cainfo');
            if ($curlcertificate == false) {
                throw new CaCertFileNotFoundException(); 
            }
            // ? Downloaded json file at 
            // https://console.cloud.google.com/iam-admin/serviceaccounts/details/
            // {unique_project_id}/keys?project={your_project_name}
            // into ..src/Invoice/Google_translate_unique_folder
            $aliases = $sR->get_google_translate_json_file_aliases();
            $targetPath = $aliases->get('@google_translate_json_file_folder');
            $path_and_filename = $targetPath .DIRECTORY_SEPARATOR.$sR->get_setting('google_translate_json_filename');
            if (empty($path_and_filename)){
                throw new GoogleTranslateJsonFileNotFoundException(); 
            }
            $data = file_get_contents(FileHelper::normalizePath($path_and_filename));
            /** @var array $json */
            $json = Json::decode($data, true);
            $projectId = (string)$json['project_id']; 
            putenv("GOOGLE_APPLICATION_CREDENTIALS=$path_and_filename");
            $translationClient = new TranslationServiceClient();
            // Use the ..src/Invoice/Language/English/ip_lang.php associative array as template
            $folder_language = 'English';           
            $lang = new Lang();
            // type eg. 'ip', 'gateway'  of ip_lang.php or gateway_lang.php or latest_lang.php i.e. invoice.invoice. lines
            $lang->load($type, $folder_language);
            $content = $lang->_language;
            // Build a template array using keys from $content
            // These keys will be filled with the associated translated text values
            // generated below by merging the two arrays.
            $content_keys_array = array_keys($content);
            // Retrieve the selected new language according to locale in Settings View Google Translate
            // eg. 'es' ie. Spanish
            $targetLanguage = $sR->get_setting('google_translate_locale');
            if (empty($targetLanguage)){
                throw new GoogleTranslateLocaleSettingNotFoundException(); 
            }
            // https://github.com/googleapis/google-cloud-php-translate
            /** @var array<array-key, string> $content */ 
            $response = $translationClient->translateText(
                $content,
                $targetLanguage,
                TranslationServiceClient::locationName($projectId, 'global')
            );
            $result_array = [];
            /** 
             * @var \Google\Cloud\Translate\V3\TranslateTextResponse $response_get_translations                        
             */
            $response_get_translations = $response->getTranslations();
            /** 
             * @psalm-suppress RawObjectIteration $response_get_translations 
             * @var \Google\Cloud\Translate\V3\Translation $translation
             * @var string $key
             */
            foreach ($response_get_translations as $key => $translation) {
                $result_array[$key] = $translation->getTranslatedText().',';
            }
            $combined_array = array_combine($content_keys_array, $result_array);
            $file = $this->google_translate_get_file_from_type($type);
            $path = $this->aliases->get('@generated');
            $content_params = [
                'combined_array' => $combined_array
            ];
            $file_content = $this->viewRenderer->renderPartialAsString(
            'generator/templates_protected/'.$file, $content_params);
            $this->flash_message('success', $file.$this->translator->translate('invoice.generator.generated'). $path .'/'.$file);
            $this->build_and_save($path, $file_content, $file, $type);
            $parameters = [
               'alert' => $this->alert(),
               'combined_array' => $combined_array
            ];      
            return $this->viewRenderer->render('_google_translate_lang', $parameters);
        }
        $this->flash_message('info', $this->translator->translate('invoice.generator.file.type.not.found'));
        return $this->webService->getRedirectResponse('site/index');
    }
    
    /**
     * @param string $type
     * @return string
     */
    private function google_translate_get_file_from_type(string $type) : string {
        $file = '';
        switch ($type) {
            case 'ip':
                $file = self::_IP;
                break;
            case 'gateway':
                $file = self::_GATEWAY;
                break;
            case 'app':
                $file = self::_APP;
                break;
            case 'latest':
                $file = self::_LATEST;
                break;
            case 'common':
                $file = self::_COMMON;
                break;
            case 'any':
                $file = self::_ANY;
                break;
            default:
                break;
        }
        return $file;
    }
    
    /**
     * @return Aliases
     */
    private function setAliases(): Aliases {
        return new Aliases([
            '@generators' => dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/generator/templates_protected',
            '@generated' => dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/generator/output_overwrite',            
            '@Entity' => dirname(dirname(dirname(__DIR__))).'/src/Invoice/Entity',
            '@Invoice' => dirname(dirname(dirname(__DIR__))).'/src/Invoice',
            '@invoice' => dirname(dirname(dirname(__DIR__))).'/resources/views/invoice' 
        ]); 
    }
    
    /**
     * @param View $view
     * @param Gentor $generator
     * @param \Yiisoft\Data\Reader\DataReaderInterface $relations
     * @param \Cycle\Database\TableInterface $orm_schema
     * @param string $file
     * @return string
     */
    private function getContent(View $view, Gentor $generator,\Yiisoft\Data\Reader\DataReaderInterface $relations,\Cycle\Database\TableInterface $orm_schema,string $file): string{
        return $content = $view->render("//invoice/generator/templates_protected/".$file,['generator' => $generator,
                'relations'=>$relations,
                'orm_schema'=>$orm_schema,
                'body'=>$this->body($generator)]);
    }
    
    /**
     * @param string $generated_dir_path
     * @param string $content
     * @param string $file
     * @param string $name
     * @return GenerateCodeFileHelper
     */
    private function build_and_save(string $generated_dir_path, string $content, string $file, string $name): GenerateCodeFileHelper{
        $build_file = new GenerateCodeFileHelper("$generated_dir_path/$name$file", $content); 
        $build_file->save();
        return $build_file;
    }
    
    /**
     * @param Gentor $generator
     * @return array
     */
    private function body(Gentor $generator): array {
        $body = [
            'route_prefix' => $generator->getRoute_prefix(),
            'route_suffix' => $generator->getRoute_suffix(),
            'camelcase_capital_name' => $generator->getCamelcase_capital_name(),
            'small_singular_name' => $generator->getSmall_singular_name(),
            'small_plural_name' => $generator->getSmall_plural_name(),
            'namespace_path' => $generator->getNamespace_path(),
            'controller_layout_dir' => $generator->getController_layout_dir(),
            'controller_layout_dir_dot_path' => $generator->getController_layout_dir_dot_path(),
            'pre_entity_table' => $generator->getPre_entity_table(),
            'flash_include' => $generator->isFlash_include(),
        ];
        return $body;
    }
}    
