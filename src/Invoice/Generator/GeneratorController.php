<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\Gentor;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use App\Invoice\Helpers\CaCertFileNotFoundException;
use App\Invoice\Helpers\GoogleTranslateDiffEmptyException;
use App\Invoice\Helpers\GoogleTranslateJsonFileNotFoundException;
use App\Invoice\Helpers\GoogleTranslateLocaleSettingNotFoundException;
use App\Invoice\Helpers\GenerateCodeFileHelper;
use App\Invoice\Libraries\Lang;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Cycle\Database\DatabaseManager;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Files\FileHelper;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\View\View;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

class GeneratorController extends BaseController
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
    public const string  _FORM = '_form.php';
    public const string _VIEW = '_view.php';
    public const string _ROUTE = '_route.php';

    /**
     * Related logic: see Note: The working file app.php in ./resources/messages/en is too big for google to translate.
     *
     * Related logic: see Note these below filenames e.g. '_ip_lang.php' represent the filenames in:
     * ./resources/views/invoice/generator/templates_protected. As strings they will be
     * used to construct a filename. The translation of the specific ./src/Invoice/Language/English file
     * is placed in the templates_protected/{individual file} 'php shell template' to build a new php file.
     *
     * So using a selected ...settings...view...'Google Translate Locale',
     * these 'English' folder files are used to build a new php ./resources/views/invoice/generator/output_overwrite file.
     *
     * These individual output_overwrite files can then manually be combined into one app.php specific to a language.
     * This app.php is then placed manually in ./resources/messages/{locale}/
     *
     * The files located in the templates_protected folder are array 'php shells' which receive the arrays from the English folder.
     * Any changes to the ./resources/messages/en/app.php during development, requires changes to the English folder files.
     */

    // e.g. a complete file that is now easy for Google to translate
    public const string _APP = '_app.php';

    // e.g. compare resources/messages/en.php with de.php with function rebuildLocale.
    // The missing keys in de.php are input into an array into an overwritable file
    // called _diff.php located at src\Invoice\Language\English
    public const string _DIFF = '_diff_lang.php';

    public function __construct(
        private Aliases $aliases,
        private DataResponseFactoryInterface $factory,
        private GeneratorService $generatorService,
        private readonly UrlGenerator $urlGenerator,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->aliases = $this->setAliases();
        $this->factory = $factory;
        $this->generatorService = $generatorService;
    }

    /**
     * Compare e.g \resources\messages\de\app.php to the base \resources\messages\en\app.php
     * and determine what keys are missing in the selected de\app.php  array
     * Insert English key => value array into file ./src/Invoice/Language/English/diff_lang.php
     * @throws GoogleTranslateLocaleSettingNotFoundException
     * @psalm-suppress MixedAssignment $lang
     */
    private function rebuildLocale(): void
    {
        $targetLanguage = $this->sR->getSetting('google_translate_locale');
        if (empty($targetLanguage)) {
            throw new GoogleTranslateLocaleSettingNotFoundException();
        }
        $en = $this->aliases->get('@en');
        $fileEnAppPath = $en . DIRECTORY_SEPARATOR . 'app.php';

        $lang = [];
        if (($foundEnAppPath = file_exists($fileEnAppPath)) === true) {
            // $lang is a full array inside the file designated by $fileEnAppPath
            $lang = include($fileEnAppPath);
        }
        $arrayEnAppDotPhp = $lang;
        $messages = $this->aliases->get('@messages');
        $targetLangFileAppPath = $messages
                       . DIRECTORY_SEPARATOR
                           . $targetLanguage
                       . DIRECTORY_SEPARATOR . 'app.php';

        $lang = [];
        if (($foundTargetLangFileAppPath = file_exists($targetLangFileAppPath)) === true) {
            // $lang is a full array inside the file designated by $targetLangFileAppPath
            $lang = include($targetLangFileAppPath);
        }

        $arrayTargetLocaleDotPhp = $lang ?? [];

        $diff = [];
        /**
         * @var string $key
         * @var string $value
         */
        foreach ($arrayEnAppDotPhp as $key => $value) {
            if (!array_key_exists($key, (array) $arrayTargetLocaleDotPhp)) {
                $diff[$key] = $value;
            }
        }

        if (empty($diff)) {
            throw new GoogleTranslateDiffEmptyException();
        }

        $content = '<?php declare(strict_types=1); $lang = ' . var_export($diff, true) . ';';
        $diffFileLocation = $this->aliases->get('@English') . DIRECTORY_SEPARATOR . 'diff_lang.php';
        file_put_contents($diffFileLocation, $content);
        $this->flashMessage('success', $fileEnAppPath . ' minus ' . $targetLangFileAppPath . ' at ' . $diffFileLocation);
    }

    /**
     * Version: 2.0.1 replacing 2.0.0 as at 20/06/2025
     * Purpose: To translate one individual file located in src/Invoice/Language/English to folder
     *          ./resources/views/invoice/generator/output_overwrite
     *          This individual file can be copied to an app.php
     *
     * @param CurrentRoute $currentRoute
     * @throws CaCertFileNotFoundException
     * @throws GoogleTranslateJsonFileNotFoundException
     * @throws GoogleTranslateLocaleSettingNotFoundException
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function google_translate_lang(CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse|Response
    {
        // 1. Downloaded https://curl.haxx.se/ca/cacert.pem" into c:\wamp64\bin\php\{active_php} e.g. c:\wamp64\bin\php\php8.2.0
        // 2. Symlink C:\wamp64\bin\apache\apache2.4.54.2\bin\php.ini points to C:\wamp64\bin\php\php8.2\phpForApache.ini.
        // 3. Edit phpForApache.ini at line 1944 [curl] with e.g. curl.cainfo="c:/wamp64/bin/php/php8.2.0/cacert.pem"
        // 4. Note forward slashes and quotes
        $type = $currentRoute->getArgument('type');
        if (null !== $type) {
            $curlcertificate = \ini_get('curl.cainfo');
            if ($curlcertificate == false) {
                throw new CaCertFileNotFoundException();
            }
            // $type == 'app' => rebuild complete array
            // $type == 'diff' => compare the folder e.g 'de' with developing folder 'resources/messages/en' and ONLY translate the difference
            match ($type) {
                'diff' => $this->rebuildLocale(),
                // copy the latest developments in the en folder to the working directory English
                'app' => $this->copyAppPhpToLangPhp(),
                default => null,
            };
            // 1. Downloaded json file at https://console.cloud.google.com/iam-admin/serviceaccounts/details/unique_project_id/keys?project={your_project_name}
            //    into ..src/Invoice/Google_translate_unique_folder
            $aliases = $this->sR->get_google_translate_json_file_aliases();
            $targetPath = $aliases->get('@google_translate_json_file_folder');
            $path_and_filename = $targetPath . DIRECTORY_SEPARATOR . $this->sR->getSetting('google_translate_json_filename');
            if (strlen($this->sR->getSetting('google_translate_json_filename')) == 0 || !$this->ensureJsonExtension($path_and_filename)) {
                throw new GoogleTranslateJsonFileNotFoundException();
            }
            $data = file_get_contents(FileHelper::normalizePath($path_and_filename));
            if ($data != false) {
                /** @var array $json */
                $json = Json::decode($data, true);
                $projectId = (string) $json['project_id'];
                putenv("GOOGLE_APPLICATION_CREDENTIALS=$path_and_filename");
                try {
                    $translationClient = new TranslationServiceClient([]);
                    // Use the ..src/Invoice/Language/English/ip_lang.php associative array as template
                    $lang = new Lang();
                    // type eg. 'ip', 'gateway'  of ip_lang.php or gateway_lang.php respectively
                    $lang->load($type, 'English');
                    /** @var array<array-key, string> $content */
                    $content = $lang->_language;
                    // Retrieve the selected new language according to locale in Settings View Google Translate
                    // eg. 'es' ie. Spanish
                    $targetLanguage = $this->sR->getSetting('google_translate_locale');
                    if (empty($targetLanguage)) {
                        throw new GoogleTranslateLocaleSettingNotFoundException();
                    }
                    // Set the batch size based on Google API limits (e.g., 100)
                    $batchSize = 100;
                    $keys = array_keys($content);
                    $values = array_values($content);
                    $numItems = count($content);
                    $result_array = [];

                    // Loop through in batches
                    for ($i = 0; $i < $numItems; $i += $batchSize) {
                        $batchValues = array_slice($values, $i, $batchSize);
                        $request = new TranslateTextRequest();
                        $request->setParent('projects/' . $projectId);
                        $request->setContents($batchValues);
                        $request->setTargetLanguageCode($targetLanguage);
                        // The request will contain the authentication token based on the default credentials file
                        $response = $translationClient->translateText($request);
                        /**
                         * @var \Google\Cloud\Translate\V3\TranslateTextResponse $response_get_translations
                         * @psalm-suppress DeprecatedClass
                         * Related logic: see Google\Protobuf\Internal\RepeatedField is deprecated. Use Google\Protobuf\RepeatedField instead.
                         */
                        $response_get_translations = $response->getTranslations();
                        /**
                        * @psalm-suppress RawObjectIteration $response_get_translations
                        * @var \Google\Cloud\Translate\V3\Translation $translation
                        * @var string $key
                        * Related logic: see $content = ['view.contact.form.name' => 'Name']
                        * Related logic: see $response_get_translations = ['Name' => 'Naam']
                        */
                        foreach ($response_get_translations as $key => $translation) {
                            $result_array[] = $translation->getTranslatedText();
                        }
                    }
                    if (count($result_array) !== $numItems) {
                        throw new \RuntimeException('Total translation count mismatch.');
                    }
                    $combined_array = array_combine($keys, $result_array);
                    $templateFile = $this->google_translate_get_file_from_type($type);
                    $path = $this->aliases->get('@generated');
                    $content_params = [
                        'combined_array' => $combined_array,
                    ];
                    $file_content = $this->viewRenderer->renderPartialAsString(
                        '//invoice/generator/templates_protected/' . $templateFile,
                        $content_params,
                    );
                    $prefixToFileAsLocaleWithFileTypeAndTimeStamp = $targetLanguage . '_' . $type . '_' . (string) time();
                    $this->flashMessage(
                        'success',
                        sprintf(
                            '%s: %d keys translated in batches of %d. Output: %s/%s',
                            $templateFile,
                            $numItems,
                            $batchSize,
                            $path,
                            $prefixToFileAsLocaleWithFileTypeAndTimeStamp,
                        ),
                    );
                    // output to //invoice/generator/output_overwrite/
                    $this->build_and_save($path, $file_content, $templateFile, $prefixToFileAsLocaleWithFileTypeAndTimeStamp);
                    // return to the Language dropdown under settings ... google translate
                    return $this->webService->getRedirectResponse('setting/tab_index', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
        $this->flashMessage('info', $this->translator->translate('generator.file.type.not.found'));
        return $this->webService->getRedirectResponse('setting/tab_index', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
    }

    /**
     * Ensure the file path ends with .json
     *
     * @param string $filepath
     * @return bool
     */
    public function ensureJsonExtension(string $filepath): bool
    {
        // Remove any trailing whitespace
        $filepath = trim($filepath);

        // If it already ends with .json (case-insensitive), return as-is
        return str_ends_with(strtolower($filepath), '.json');
    }

    /**
     * Copies resources/messages/en/app.php as src/Invoice/Language/English/app_lang.php
     * with the $lang = [...] format.
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     */
    private function copyAppPhpToLangPhp(): void
    {
        $source = $this->aliases->get('@messages') . '/en/app.php';
        $destination = $this->aliases->get('@English') . '/app_lang.php';

        if (!file_exists($source)) {
            throw new \RuntimeException("Source file not found: $source");
        }

        /** @var array<string, string> $app */
        $app = include($source);

        $export = var_export($app, true);

        $php = "<?php\n";
        $php .= "declare(strict_types=1);\n";
        $php .= "\$lang = $export;\n";

        file_put_contents($destination, $php);
    }

    /**
     * @param string $type
     * @return string
     */
    private function google_translate_get_file_from_type(string $type): string
    {
        $file = '';
        switch ($type) {
            case 'app':
                $file = self::_APP;
                break;
                /**
                 * Related logic: see ../resources/views/layout/invoice.php DropdownItem::link($translator->translate('generator.google.translate.latest.a'),
                 *      $urlGenerator->generate('generator/google_translate_lang', ['type' => 'a_latest']),  false, false),
                 */
            case 'diff':
                $file = self::_DIFF;
                break;
            default:
                break;
        }
        return $file;
    }

    /**
     * @return Aliases
     */
    private function setAliases(): Aliases
    {
        $ds = DIRECTORY_SEPARATOR;
        return new Aliases([
            '@generators' => dirname(__DIR__, 3) . '/resources/views/invoice/generator/templates_protected',
            '@generated' => dirname(__DIR__, 3) . '/resources/views/invoice/generator/output_overwrite',
            '@Entity' => dirname(__DIR__, 3) . '/src/Invoice/Entity',
            '@Invoice' => dirname(__DIR__, 3) . '/src/Invoice',
            '@invoice' => dirname(__DIR__, 3) . '/resources/views/invoice',
            '@messages' => dirname(__DIR__, 3) . '/resources/messages',
            '@en' => dirname(__DIR__, 3) . $ds . 'resources' . $ds . 'messages' . $ds . 'en',
            '@English' => dirname(__DIR__, 3) . '/src/Invoice/Language/English',
        ]);
    }

    /**
     * @param GeneratorRepository $generatorRepository
     * @param GeneratorRelationRepository $grR
     */
    public function index(
        GeneratorRepository $generatorRepository,
        GeneratorRelationRepository $grR,
    ): \Yiisoft\DataResponse\DataResponse {
        $this->rbac();
        $generators = $this->generators($generatorRepository);
        $paginator = (new OffsetPaginator($generators));
        $parameters = [
            'grR' => $grR,
            'alert' => $this->alert(),
            'paginator' => $paginator,
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
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->generatorService->saveGenerator($gentor, $body);
                    return $this->webService->getRedirectResponse('generator/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
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
                'title' => $this->translator->translate('edit'),
                'actionName' => 'generator/edit',
                'actionArguments' => ['id' => $generator->getGentor_id()],
                'errors' => [],
                'form' => $form,
                'tables' => $dbal->database('default')->getTables(),
                'selected_table' => $generator->getPre_entity_table(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->generatorService->saveGenerator($generator, $body);
                        $this->flashMessage('warning', $this->translator->translate('record.successfully.updated'));
                        return $this->webService->getRedirectResponse('generator/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('generator/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $generatorRepository
     * @return Response
     */
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
                'title' => $this->translator->translate('view'),
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
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('generator/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $generatorRepository
     * @return Gentor|null
     */
    private function generator(CurrentRoute $currentRoute, GeneratorRepository $generatorRepository): ?Gentor
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $generatorRepository->repoGentorQuery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function generators(GeneratorRepository $generatorRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $generatorRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
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
        $camelcaseFileName = $g->getCamelcase_capital_name() . '.php';
        $viewPath = $this->aliases->get('@Entity');
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        $build_file = $this->build_and_save($viewPath, $content, '.php', $g->getCamelcase_capital_name());
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
        return $this->viewRenderer->render('_results', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
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
        $camelcaseFileName = $g->getCamelcase_capital_name() . $file;
        $viewPath = $this->aliases->get('@Invoice') . DIRECTORY_SEPARATOR . $g->getCamelcase_capital_name();
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        $build_file = $this->build_and_save($viewPath, $content, $camelcaseFileName, '');
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
        return $this->viewRenderer->render('_results', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
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
        $camelcaseFileName = $g->getCamelcase_capital_name() . $file;
        $viewPath = $this->aliases->get('@Invoice') . DIRECTORY_SEPARATOR . $g->getCamelcase_capital_name();
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        $build_file = $this->build_and_save($viewPath, $content, $camelcaseFileName, '');
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
        return $this->viewRenderer->render('_results', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
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
        $camelcaseFileName = $g->getCamelcase_capital_name() . $file;
        $viewPath = $this->aliases->get('@Invoice') . DIRECTORY_SEPARATOR . $g->getCamelcase_capital_name();
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPre_entity_table() */
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        $build_file = $this->build_and_save($viewPath, $content, $camelcaseFileName, '');
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
        return $this->viewRenderer->render('_results', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
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
        $viewPath = $this->aliases->get('@Invoice') . DIRECTORY_SEPARATOR . $g->getCamelcase_capital_name();
        $camelcaseFileName = $g->getCamelcase_capital_name() . $file;
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPre_entity_table() */
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        $build_file = $this->build_and_save($viewPath, $content, $camelcaseFileName, '');
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
        return $this->viewRenderer->render('_results', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function _index(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::INDEX;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice') . DIRECTORY_SEPARATOR . $g->getSmall_singular_name();
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        /** @psalm-suppress ArgumentTypeCoercion $g->getPre_entity_table() */
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        $build_file = $this->build_and_save($viewPath, $content, $file, '');
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
        return $this->viewRenderer->render('_results', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function _form(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::_FORM;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice') . DIRECTORY_SEPARATOR . $g->getSmall_singular_name();
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        $build_file = $this->build_and_save($viewPath, $content, $file, '');
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
        return $this->viewRenderer->render('_results', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     */
    public function _view(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::_VIEW;
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $viewPath = $this->aliases->get('@invoice') . DIRECTORY_SEPARATOR . $g->getSmall_singular_name();
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);

        // also generate a file into the folder created for this view
        $build_file = $this->build_and_save($viewPath, $content, $file, '');
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
    public function _route(
        CurrentRoute $currentRoute,
        GeneratorRepository $gr,
        GeneratorRelationRepository $grr,
        DatabaseManager $dbal,
        View $view,
    ): Response {
        $file = self::_ROUTE;
        $path = $this->aliases->get('@generated');
        /** @var Gentor $g */
        $g = $this->generator($currentRoute, $gr);
        $table_name = $g->getPre_entity_table();
        if (null == $table_name) {
            return $this->webService->getRedirectResponse('generator/index');
        }
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($table_name);
        $content = $this->getContent($view, $g, $relations, $orm, $file);
        $this->flashMessage('success', $file . $this->translator->translate('generator.generated') . $path . '/' . $file);
        $build_file = $this->build_and_save($path, $content, $file, '');
        $parameters = [
            'canEdit' => $this->rbac(),
            'title' => $this->translator->translate('generator.generate') . $file,
            'generator' => $g,
            'orm_schema' => $orm,
            'relations' => $relations,
            'alert' => $this->alert(),
            'generated' => $build_file,
        ];
        return $this->viewRenderer->render('_results', $parameters);
    }

    /**
     * @param CurrentUser $currentUser
     * @param DatabaseManager $dba
     */
    public function quick_view_schema(CurrentUser $currentUser, DatabaseManager $dba): \Yiisoft\DataResponse\DataResponse
    {
        $parameters = [
            'alerts' => $this->alert(),
            'isGuest' => $currentUser->isGuest(),
            'tables' => $dba->database('default')->getTables(),
        ];
        return $this->viewRenderer->render('_schema', $parameters);
    }

    /**
     * @param View $view
     * @param Gentor $generator
     * @param \Yiisoft\Data\Reader\DataReaderInterface $relations
     * @param \Cycle\Database\TableInterface $orm_schema
     * @param string $file
     * @return string
     */
    private function getContent(View $view, Gentor $generator, \Yiisoft\Data\Reader\DataReaderInterface $relations, \Cycle\Database\TableInterface $orm_schema, string $file): string
    {
        return $content = $view->render('//invoice/generator/templates_protected/' . $file, ['generator' => $generator,
            'relations' => $relations,
            'orm_schema' => $orm_schema,
            'body' => $this->body($generator)]);
    }

    /**
     * @param string $generated_dir_path
     * @param string $content
     * @param string $file
     * @param string $name
     * @return GenerateCodeFileHelper
     */
    private function build_and_save(string $generated_dir_path, string $content, string $file, string $name): GenerateCodeFileHelper
    {
        $build_file = new GenerateCodeFileHelper("$generated_dir_path/$name$file", $content);
        $build_file->save();
        return $build_file;
    }

    /**
     * @param Gentor $generator
     * @return array
     */
    private function body(Gentor $generator): array
    {
        return [
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
    }
}
