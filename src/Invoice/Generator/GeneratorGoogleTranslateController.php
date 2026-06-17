<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\BaseController;
use App\Invoice\Helpers\CaCertFileNotFoundException;
use App\Invoice\Helpers\GoogleTranslateDiffEmptyException;
use App\Invoice\Helpers\GoogleTranslateJsonFileNotFoundException;
use App\Invoice\Helpers\GoogleTranslateLocaleSettingNotFoundException;
use App\Invoice\Helpers\GenerateCodeFileHelper;
use App\Invoice\Libraries\Lang;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Handles Google Translate actions for translation-file generation and documentation.
 * Extracted from GeneratorController to satisfy S1448 (≤20 methods per class).
 */
final class GeneratorGoogleTranslateController extends BaseController
{
    protected string $controllerName = 'invoice/generator';

    public const string APP = '_app.php';
    public const string DIFF_LANG = '_diff_lang.php';

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

    /**
     * @throws CaCertFileNotFoundException
     * @throws GoogleTranslateJsonFileNotFoundException
     * @throws GoogleTranslateLocaleSettingNotFoundException
     */
    public function googleTranslateLang(CurrentRoute $currentRoute): Response
    {
        $type = $currentRoute->getArgument('type');
        if (null !== $type) {
            $curlcertificate = \ini_get('curl.cainfo');
            if ($curlcertificate === false || strlen($curlcertificate) === 0) {
                throw new CaCertFileNotFoundException();
            }
            match ($type) {
                'diff' => $this->rebuildLocale(),
                'app' => $this->copyAppPhpToLangPhp(),
                default => null,
            };
            $aliases = $this->sR->getGoogleTranslateJsonFileAliases();
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
                    $lang = new Lang();
                    $lang->load($type, 'English');
                    /** @var array<array-key, string> $content */
                    $content = $lang->uLanguage;
                    $targetLanguage = $this->sR->getSetting('google_translate_locale');
                    if (empty($targetLanguage)) {
                        throw new GoogleTranslateLocaleSettingNotFoundException();
                    }
                    $batchSize = 100;
                    $keys = array_keys($content);
                    $values = array_values($content);
                    $numItems = count($content);
                    $result_array = [];
                    for ($i = 0; $i < $numItems; $i += $batchSize) {
                        $batchValues = array_slice($values, $i, $batchSize);
                        $request = new TranslateTextRequest();
                        $request->setParent('projects/' . $projectId);
                        $request->setContents($batchValues);
                        $request->setTargetLanguageCode($targetLanguage);
                        $response = $translationClient->translateText($request);
                        /**
                         * @var \Google\Cloud\Translate\V3\TranslateTextResponse $response_get_translations
                         * @psalm-suppress DeprecatedClass
                         */
                        $response_get_translations = $response->getTranslations();
                        /**
                         * @psalm-suppress RawObjectIteration $response_get_translations
                         * @var \Google\Cloud\Translate\V3\Translation $translation
                         */
                        foreach ($response_get_translations as $translation) {
                            $result_array[] = $translation->getTranslatedText();
                        }
                    }
                    if (count($result_array) !== $numItems) {
                        throw new GeneratorException('Total translation count mismatch.');
                    }
                    $combined_array = array_combine($keys, $result_array);
                    $templateFile = $this->googleTranslateGetFileFromType($type);
                    $path = $this->aliases->get('@generated');
                    $content_params = [
                        'combined_array' => $combined_array,
                    ];
                    $file_content = $this->webViewRenderer->renderPartialAsString(
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
                    $build_file = new GenerateCodeFileHelper("$path/$prefixToFileAsLocaleWithFileTypeAndTimeStamp$templateFile", $file_content);
                    $build_file->save();
                    return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
                } catch (\Exception $e) {
                    $this->flashMessage('danger', $e->getMessage());
                    return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
                }
            }
        }
        $this->flashMessage('info', $this->translator->translate('generator.file.type.not.found'));
        return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
    }

    /**
     * @throws CaCertFileNotFoundException
     * @throws GoogleTranslateJsonFileNotFoundException
     * @throws GoogleTranslateLocaleSettingNotFoundException
     */
    public function googleTranslateInfo(): Response
    {
        $curlcertificate = \ini_get('curl.cainfo');
        if ($curlcertificate == false) {
            throw new CaCertFileNotFoundException();
        }
        $targetLanguage = $this->sR->getSetting('google_translate_locale');
        if (empty($targetLanguage)) {
            throw new GoogleTranslateLocaleSettingNotFoundException();
        }
        $aliases = $this->sR->getGoogleTranslateJsonFileAliases();
        $targetPath = $aliases->get('@google_translate_json_file_folder');
        $path_and_filename = $targetPath . DIRECTORY_SEPARATOR . $this->sR->getSetting('google_translate_json_filename');
        if (strlen($this->sR->getSetting('google_translate_json_filename')) == 0 || !$this->ensureJsonExtension($path_and_filename)) {
            throw new GoogleTranslateJsonFileNotFoundException();
        }
        $data = file_get_contents(FileHelper::normalizePath($path_and_filename));
        if ($data == false) {
            $this->flashMessage('danger', 'Failed to read Google Translate JSON credentials file.');
            return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
        }
        /** @var array $json */
        $json = Json::decode($data, true);
        $projectId = (string) $json['project_id'];
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$path_and_filename");
        try {
            $translationClient = new TranslationServiceClient([]);
            $sourceFile = dirname(__DIR__, 3) . '/resources/views/invoice/info/en/invoice.php';
            if (!file_exists($sourceFile)) {
                $this->flashMessage('danger', 'Source file not found: ' . $sourceFile);
                return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
            }
            $htmlContent = file_get_contents($sourceFile);
            if ($htmlContent === false || strlen($htmlContent) === 0) {
                $this->flashMessage('danger', 'Failed to read source file.');
                return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
            }
            $segments = $this->extractTranslatableSegments($htmlContent);
            $batchSize = 5;
            $translatedSegments = [];
            $numSegments = count($segments);
            for ($i = 0; $i < $numSegments; $i += $batchSize) {
                $batch = array_slice($segments, $i, $batchSize);
                $batchNumber = (int) ($i / $batchSize) + 1;
                $totalBatches = (int) ceil($numSegments / $batchSize);
                $request = new TranslateTextRequest();
                $request->setParent('projects/' . $projectId);
                $request->setContents($batch);
                $request->setTargetLanguageCode($targetLanguage);
                $request->setMimeType('text/html');
                $response = $translationClient->translateText($request);
                /** @var \Google\Cloud\Translate\V3\TranslateTextResponse $response_get_translations */
                $response_get_translations = $response->getTranslations();
                /**
                 * @psalm-suppress RawObjectIteration $response_get_translations
                 * @var \Google\Cloud\Translate\V3\Translation $translation
                 */
                foreach ($response_get_translations as $translation) {
                    $translatedSegments[] = $translation->getTranslatedText();
                }
                if ($batchNumber % 5 == 0 || $batchNumber == $totalBatches) {
                    error_log(sprintf('Translated batch %d of %d', $batchNumber, $totalBatches));
                }
            }
            $translatedContent = implode('', $translatedSegments);
            $targetDir = dirname(__DIR__, 3) . '/resources/views/invoice/info/' . $targetLanguage;
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $targetFile = $targetDir . '/invoice.php';
            file_put_contents($targetFile, $translatedContent);
            $this->flashMessage(
                'success',
                sprintf(
                    'Successfully translated invoice.php to %s in %d batches (%d segments). Output: %s',
                    $targetLanguage,
                    (int) ceil($numSegments / $batchSize),
                    $numSegments,
                    $targetFile
                )
            );
            return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
        } catch (\Exception $e) {
            $this->flashMessage('danger', 'Translation error: ' . $e->getMessage());
            return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
        }
    }

    public function ensureJsonExtension(string $filepath): bool
    {
        $filepath = trim($filepath);
        return str_ends_with(strtolower($filepath), '.json');
    }

    /**
     * @throws GoogleTranslateLocaleSettingNotFoundException
     * @psalm-suppress MixedAssignment
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
        if ((file_exists($fileEnAppPath)) === true) {
            $lang = include $fileEnAppPath; // NOSONAR — data file returns an array; include_once returns true on second call
        }
        $arrayEnAppDotPhp = $lang;
        $messages = $this->aliases->get('@messages');
        $targetLangFileAppPath = $messages
                       . DIRECTORY_SEPARATOR
                           . $targetLanguage
                       . DIRECTORY_SEPARATOR . 'app.php';
        $lang = [];
        if ((file_exists($targetLangFileAppPath)) === true) {
            $lang = include $targetLangFileAppPath; // NOSONAR — data file returns an array; include_once returns true on second call
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
        $content = '<?php declare(strict_types=1); $lang = '
                . var_export($diff, true)
                . ';';
        $diffFileLocation = $this->aliases->get('@English')
                . DIRECTORY_SEPARATOR . 'diff_lang.php';
        file_put_contents($diffFileLocation, $content);
        $this->flashMessage('success', $fileEnAppPath
                . ' minus '
                . $targetLangFileAppPath
                . ' at '
                . $diffFileLocation);
    }

    /** @return array<int, string> */
    private function extractTranslatableSegments(string $html): array
    {
        $maxChunkSize = 5000;
        $segments = [];
        $length   = strlen($html);
        $offset   = 0;
        while ($offset < $length) {
            $chunk   = substr($html, $offset, $maxChunkSize);
            $advance = $maxChunkSize;
            if ($offset + $maxChunkSize < $length) {
                $lastCloseTag = strrpos($chunk, '>');
                if ($lastCloseTag !== false && $lastCloseTag > $maxChunkSize * 0.8) {
                    $chunk   = substr($chunk, 0, $lastCloseTag + 1);
                    $advance = $lastCloseTag + 1;
                }
            }
            $segments[] = $chunk;
            $offset    += $advance;
        }
        return $segments;
    }

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     */
    private function copyAppPhpToLangPhp(): void
    {
        $source = $this->aliases->get('@messages') . '/en/app.php';
        $destination = $this->aliases->get('@English') . '/app_lang.php';
        if (!file_exists($source)) {
            throw new GeneratorException("Source file not found: $source");
        }
        /** @var array<string, string> $app */
        $app = include $source; // NOSONAR — data file returns an array; include_once returns true on second call
        $export = var_export($app, true);
        $php = "<?php\n";
        $php .= "declare(strict_types=1);\n";
        $php .= "\$lang = $export;\n";
        file_put_contents($destination, $php);
    }

    private function googleTranslateGetFileFromType(string $type): string
    {
        $file = '';
        switch ($type) {
            case 'app':
                $file = self::APP;
                break;
            case 'diff':
                $file = self::DIFF_LANG;
                break;
            default:
                break;
        }
        return $file;
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
