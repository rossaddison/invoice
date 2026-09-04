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
            if ($data !== false) {
                return $this->performGoogleTranslation($data, $type, $path_and_filename);
            }
        }
        $this->flashMessage('info', $this->translator->translate('generator.file.type.not.found'));
        return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
    }

    /**
     * One sweep across every locale that already has its own
     * resources/messages/{locale}/app.php: diffs it against en/app.php,
     * translates only the missing keys, merges them into the existing
     * array, sorts the result by key, and writes the file back in
     * place -- replacing the manual output_overwrite -> copy -> merge
     * -> sort cycle entirely. Unlike googleTranslateLang(), this
     * ignores the single google_translate_locale setting -- every
     * locale directory's own name is used directly as the Google
     * target language code, the same assumption the existing single-
     * locale dropdown already relies on (its values are the locale
     * folder names themselves).
     *
     * @throws CaCertFileNotFoundException
     * @throws GoogleTranslateJsonFileNotFoundException
     */
    public function googleTranslateAllLocalesDiff(): Response
    {
        $curlcertificate = \ini_get('curl.cainfo');
        if ($curlcertificate === false || strlen($curlcertificate) === 0) {
            throw new CaCertFileNotFoundException();
        }
        $aliases = $this->sR->getGoogleTranslateJsonFileAliases();
        $targetPath = $aliases->get('@google_translate_json_file_folder');
        $path_and_filename = $targetPath . DIRECTORY_SEPARATOR . $this->sR->getSetting('google_translate_json_filename');
        if (strlen($this->sR->getSetting('google_translate_json_filename')) == 0 || !$this->ensureJsonExtension($path_and_filename)) {
            throw new GoogleTranslateJsonFileNotFoundException();
        }
        $data = file_get_contents(FileHelper::normalizePath($path_and_filename));
        if ($data === false) {
            $this->flashMessage('danger', 'Failed to read Google Translate JSON credentials file.');
            return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
        }
        return $this->performAllLocalesTranslation($data, $path_and_filename);
    }

    /**
     * @throws GoogleTranslateLocaleSettingNotFoundException
     */
    private function performGoogleTranslation(string $data, string $type, string $pathAndFilename): Response
    {
        /** @var array $json */
        $json = Json::decode($data, true);
        $projectId = (string) $json['project_id'];
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$pathAndFilename");
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
            $numItems = count($content);
            $numUnique = count($this->uniqueTranslatableValues($content));
            $combined_array = $this->translateContentBatch($translationClient, $projectId, $content, $targetLanguage);
            $templateFile = $this->googleTranslateGetFileFromType($type);
            $path = $this->aliases->get('@generated');
            $file_content = $this->webViewRenderer->renderPartialAsString(
                '//invoice/generator/templates_protected/' . $templateFile,
                ['combined_array' => $combined_array],
            );
            $prefix = $targetLanguage . '_' . $type . '_' . (string) time();
            $this->flashMessage(
                'success',
                sprintf(
                    '%s: %d keys (%d unique strings sent to the API) translated in batches of 100. Output: %s/%s',
                    $templateFile,
                    $numItems,
                    $numUnique,
                    $path,
                    $prefix,
                ),
            );
            $build_file = new GenerateCodeFileHelper("$path/$prefix$templateFile", $file_content);
            $build_file->save();
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
        }
        return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
    }

    /**
     * Orchestrates the sweep: loads en/app.php once, finds every real
     * locale message folder, and hands each one to
     * translateOneLocaleDiff(). One locale's translation failure is
     * caught inside translateOneLocaleDiff() itself and reported in the
     * summary rather than aborting the rest of the sweep -- only a
     * failure to even construct the client, or to read en/app.php at
     * all, stops the whole run early.
     */
    private function performAllLocalesTranslation(string $data, string $pathAndFilename): Response
    {
        /** @var array $json */
        $json = Json::decode($data, true);
        $projectId = (string) $json['project_id'];
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$pathAndFilename");

        $enAppPath = $this->aliases->get('@en') . DIRECTORY_SEPARATOR . 'app.php';
        if (!file_exists($enAppPath)) {
            $this->flashMessage('danger', "English source file not found: $enAppPath");
            return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
        }
        /**
         * @var array<string, string> $enContent
         * @psalm-suppress MixedAssignment
         */
        $enContent = include $enAppPath; // NOSONAR — data file returns an array; include_once returns true on second call

        $messagesPath = $this->aliases->get('@messages');
        $globResult = glob($messagesPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        $localeDirs = $globResult === false ? [] : $globResult;

        $summary = [];
        try {
            $translationClient = new TranslationServiceClient([]);
            foreach ($localeDirs as $localeDir) {
                $line = $this->translateOneLocaleDiff($translationClient, $projectId, $enContent, $localeDir);
                if ($line !== null) {
                    $summary[] = $line;
                }
            }
        } catch (\Exception $e) {
            $this->flashMessage('danger', 'Translation error: ' . $e->getMessage());
            return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
        }
        $this->flashMessage(
            $summary === [] ? 'info' : 'success',
            $summary === [] ? 'No locale folders found under resources/messages.' : implode(' | ', $summary),
        );
        return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
    }

    /**
     * One locale's diff -> translate -> merge -> sort -> write, in
     * place of the manual output_overwrite copy/merge/sort step. A
     * locale with no missing keys is reported as up to date and left
     * untouched; a translation failure for this one locale is caught
     * here and reported, without affecting any other locale in the
     * same sweep.
     *
     * @param array<string, string> $enContent
     * @return string|null a summary line for the flash message, or
     *     null when $localeDir isn't a real locale message folder (no
     *     app.php at all -- e.g. 'en' itself) and nothing was done.
     * @psalm-suppress MixedAssignment
     */
    private function translateOneLocaleDiff(
        TranslationServiceClient $translationClient,
        string $projectId,
        array $enContent,
        string $localeDir
    ): ?string {
        $locale = basename($localeDir);
        $targetAppPath = $localeDir . DIRECTORY_SEPARATOR . 'app.php';
        if ($locale === 'en' || !file_exists($targetAppPath)) {
            return null;
        }
        $existing = $this->includeMessageArray($targetAppPath);
        $missing = array_diff_key($enContent, $existing);
        if ($missing === []) {
            return "{$locale}: already up to date";
        }
        return $this->translateMergeAndWriteLocale(
            $translationClient,
            $projectId,
            $locale,
            $targetAppPath,
            $existing,
            $missing
        );
    }

    /**
     * The translate/merge/write half of translateOneLocaleDiff() -- split
     * out purely to keep that method's own return count under
     * SonarQube's php:S1142 ceiling (max 3).
     *
     * @param array<string, string> $existing
     * @param array<string, string> $missing
     */
    private function translateMergeAndWriteLocale(
        TranslationServiceClient $translationClient,
        string $projectId,
        string $locale,
        string $targetAppPath,
        array $existing,
        array $missing,
    ): string {
        try {
            /** @var array<string, string> $translated */
            $translated = $this->translateContentBatch($translationClient, $projectId, $missing, $locale);
        } catch (\Exception $e) {
            return "{$locale}: FAILED — " . $e->getMessage();
        }
        $merged = $existing + $translated;
        ksort($merged, SORT_STRING);
        $this->writeLocaleAppPhp($targetAppPath, $merged);
        return sprintf('%s: +%d new keys (%d total)', $locale, count($missing), count($merged));
    }

    /**
     * Includes a resources/messages/{locale}/app.php-shaped data file
     * from a path only known at runtime (glob()-discovered, one per
     * locale) -- isolated into its own method since Psalm cannot trace
     * a dynamic include's target back to a literal file path.
     *
     * @return array<string, string>
     * @psalm-suppress UnresolvableInclude
     */
    private function includeMessageArray(string $path): array
    {
        /** @var array<string, string> */
        return include $path; // NOSONAR — data file returns an array; include_once returns true on second call
    }

    /**
     * Writes a full, sorted resources/messages/{locale}/app.php in
     * place. Uses var_export() per key/value pair rather than the raw
     * string concatenation templates_protected/_app.php and
     * _diff_lang.php use, since several genuine en/app.php values
     * contain an apostrophe (e.g. "Administrator's Email Address") --
     * raw concatenation turns into broken PHP syntax the moment a
     * translated value contains one too, which var_export() escapes
     * correctly regardless of what the string contains.
     *
     * @param array<string, string> $content already sorted by the caller
     */
    private function writeLocaleAppPhp(string $path, array $content): void
    {
        $lines = ['<?php', '', 'declare(strict_types=1);', '', 'return ['];
        foreach ($content as $key => $value) {
            $lines[] = '    ' . var_export($key, true) . ' => ' . var_export($value, true) . ',';
        }
        $lines[] = '];';
        $lines[] = '';
        file_put_contents($path, implode("\n", $lines));
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
                throw new \LogicException('Source file not found: ' . $sourceFile);
            }
            $htmlContent = file_get_contents($sourceFile);
            if ($htmlContent === false || strlen($htmlContent) === 0) {
                throw new \LogicException('Failed to read source file.');
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
                $translatedSegments = array_merge(
                    $translatedSegments,
                    $this->collectTranslations($translationClient, $request)
                );
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
        } catch (\LogicException $e) {
            $this->flashMessage('danger', $e->getMessage());
        } catch (\Exception $e) {
            $this->flashMessage('danger', 'Translation error: ' . $e->getMessage());
        }
        return $this->webService->getRedirectResponse('setting/tabIndex', ['_language' => 'en'], ['active' => 'google-translate'], 'settings[google_translate_locale]');
    }

    /**
     * The distinct strings actually worth sending to the Translation
     * API — duplicate keys sharing the exact same English text (e.g.
     * several `*.save` keys all reading "Save") only need translating
     * once; the API bills every character sent, including repeats.
     *
     * @param array<array-key, string> $content
     * @return list<string>
     */
    private function uniqueTranslatableValues(array $content): array
    {
        return array_values(array_unique(array_values($content)));
    }

    /**
     * Reconstructs the per-key translated array by mapping each
     * original value through its (already deduplicated) translation —
     * every key that shared the same original value gets the same
     * translated value back.
     *
     * @param list<array-key> $keys
     * @param list<string> $values
     * @param array<string, string> $valueTranslationMap
     * @return array<array-key, string>
     */
    private function combineKeysWithTranslatedValues(array $keys, array $values, array $valueTranslationMap): array
    {
        $translatedValues = array_map(
            static fn (string $value): string => $valueTranslationMap[$value],
            $values,
        );
        return array_combine($keys, $translatedValues);
    }

    /**
     * Translates every value in $content into $targetLanguage --
     * deduplicated before sending (uniqueTranslatableValues()) and
     * reconstructed back onto every original key afterward
     * (combineKeysWithTranslatedValues()). Shared by the single-locale
     * flow (performGoogleTranslation()) and the all-locales diff sweep
     * (translateOneLocaleDiff()), so both pay the same reduced API cost.
     *
     * @param array<array-key, string> $content
     * @return array<array-key, string>
     * @throws GeneratorException when the API returns a different
     *     number of translations than were requested.
     */
    private function translateContentBatch(
        TranslationServiceClient $translationClient,
        string $projectId,
        array $content,
        string $targetLanguage
    ): array {
        $batchSize = 100;
        $keys = array_keys($content);
        $values = array_values($content);
        $uniqueValues = $this->uniqueTranslatableValues($content);
        $numUnique = count($uniqueValues);
        $translatedUnique = [];
        for ($i = 0; $i < $numUnique; $i += $batchSize) {
            $batchValues = array_slice($uniqueValues, $i, $batchSize);
            $request = new TranslateTextRequest();
            $request->setParent('projects/' . $projectId);
            $request->setContents($batchValues);
            $request->setTargetLanguageCode($targetLanguage);
            // Google's TranslateTextRequest defaults to mimeType text/html
            // when unset (unlike the 'text/html' request just above this
            // one, used for actual HTML content -- these are plain app.php
            // UI label strings). Left unset, an apostrophe comes back
            // HTML-entity-escaped as the literal text "&#39;" instead of
            // "'" -- visible verbatim in a rendered tooltip/label, since
            // nothing downstream expects or decodes HTML entities in a
            // plain translation string. Found 2026-09-01 via the
            // googleTranslateAllLocalesDiff sweep's own real output.
            $request->setMimeType('text/plain');
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
                $translatedUnique[] = $translation->getTranslatedText();
            }
        }
        if (count($translatedUnique) !== $numUnique) {
            throw new GeneratorException("Translation count mismatch for target language '{$targetLanguage}'.");
        }
        $valueTranslationMap = array_combine($uniqueValues, $translatedUnique);
        return $this->combineKeysWithTranslatedValues($keys, $values, $valueTranslationMap);
    }

    /** @return list<string> */
    private function collectTranslations(
        TranslationServiceClient $translationClient,
        TranslateTextRequest $request
    ): array {
        $response = $translationClient->translateText($request);
        /** @var \Google\Cloud\Translate\V3\TranslateTextResponse $response_get_translations */
        $response_get_translations = $response->getTranslations();
        $translated = [];
        /**
         * @psalm-suppress RawObjectIteration $response_get_translations
         * @var \Google\Cloud\Translate\V3\Translation $translation
         */
        foreach ($response_get_translations as $translation) {
            $translated[] = $translation->getTranslatedText();
        }
        return $translated;
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
