<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Generator;

use App\Invoice\Generator\GeneratorGoogleTranslateController;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextResponse;
use Google\Cloud\Translate\V3\Translation;
use Mockery as m;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

/**
 * Covers the "translate all locales in one sweep" automation this
 * replaced a fully manual process with: diff a locale's app.php
 * against en/app.php, translate only the missing keys, merge them into
 * the existing array, sort by key, and write the file back in place --
 * no more output_overwrite -> copy -> merge -> sort by hand. Exercises
 * translateOneLocaleDiff() (the per-locale worker) and
 * writeLocaleAppPhp() (the file writer) directly via reflection against
 * real temporary locale directories, matching
 * RedirectControllerBotDetectionTest's established pattern for testing
 * a private method on a controller with injected dependencies these
 * tests never need. TranslationServiceClient is `final`, mockable here
 * because Tests/bootstrap.php enables DG\BypassFinals globally;
 * TranslateTextResponse/Translation are plain protobuf message objects
 * built for real rather than mocked.
 */
#[Test]
final class GeneratorGoogleTranslateControllerAllLocalesTest
{
    private function controller(): GeneratorGoogleTranslateController
    {
        $reflectionClass = new ReflectionClass(GeneratorGoogleTranslateController::class);

        /** @var GeneratorGoogleTranslateController */
        return $reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * @param array<string, string> $enContent
     */
    private function translateOneLocaleDiff(
        TranslationServiceClient $translationClient,
        string $projectId,
        array $enContent,
        string $localeDir
    ): ?string {
        $reflectionClass = new ReflectionClass(GeneratorGoogleTranslateController::class);
        $method = $reflectionClass->getMethod('translateOneLocaleDiff');

        /** @var string|null */
        return $method->invoke($this->controller(), $translationClient, $projectId, $enContent, $localeDir);
    }

    /**
     * @param array<string, string> $content
     */
    private function writeLocaleAppPhp(string $path, array $content): void
    {
        $reflectionClass = new ReflectionClass(GeneratorGoogleTranslateController::class);
        $method = $reflectionClass->getMethod('writeLocaleAppPhp');
        $method->invoke($this->controller(), $path, $content);
    }

    /**
     * Nests the real locale directory (basename literally $locale, since
     * translateOneLocaleDiff() reads it via basename($localeDir)) one
     * level under a uniquely-named parent, so repeated test runs never
     * collide on the same path.
     *
     * @param array<string, string>|null $appPhpContent null means: don't
     *     create an app.php at all in this directory.
     */
    private function makeTempLocaleDir(string $locale, ?array $appPhpContent): string
    {
        $parent = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yii3i_gtranslate_test_' . uniqid();
        $dir = $parent . DIRECTORY_SEPARATOR . $locale;
        mkdir($dir, 0755, true);
        if ($appPhpContent !== null) {
            $this->writeLocaleAppPhp($dir . DIRECTORY_SEPARATOR . 'app.php', $appPhpContent);
        }
        return $dir;
    }

    private function cleanupDir(string $dir): void
    {
        $appPhp = $dir . DIRECTORY_SEPARATOR . 'app.php';
        if (file_exists($appPhp)) {
            unlink($appPhp);
        }
        rmdir($dir);
        rmdir(dirname($dir));
    }

    /**
     * @param list<string> $translatedTextsInOrder
     */
    private function mockTranslationClient(array $translatedTextsInOrder): TranslationServiceClient
    {
        $translations = array_map(
            static fn (string $text): Translation => (new Translation())->setTranslatedText($text),
            $translatedTextsInOrder,
        );
        $response = (new TranslateTextResponse())->setTranslations($translations);

        /** @var TranslationServiceClient&m\MockInterface $client */
        $client = m::mock(TranslationServiceClient::class);
        $client->shouldReceive('translateText')->once()->andReturn($response);

        /** @var TranslationServiceClient */
        return $client;
    }

    public function writeLocaleAppPhpRoundTripsAValueContainingAnApostrophe(): void
    {
        // The real motivating bug: templates_protected/_app.php and
        // _diff_lang.php build the file via raw string concatenation
        // ("'" . $value . "'"), which produces broken PHP syntax the
        // moment $value itself contains a single quote -- a real,
        // existing case in en/app.php (e.g. "Administrator's Email
        // Address"). writeLocaleAppPhp() must not have this problem.
        $dir = $this->makeTempLocaleDir('apostrophe-test', null);
        $path = $dir . DIRECTORY_SEPARATOR . 'app.php';
        try {
            $this->writeLocaleAppPhp($path, [
                'admin.email' => "Administrator's Email Address",
                'plain.key' => 'Plain Value',
            ]);

            /**
             * @var array<string, string> $roundTripped
             * @psalm-suppress UnresolvableInclude
             */
            $roundTripped = include $path; // NOSONAR — test fixture data file, deliberately dynamic

            Assert::same($roundTripped, [
                'admin.email' => "Administrator's Email Address",
                'plain.key' => 'Plain Value',
            ]);
        } finally {
            $this->cleanupDir($dir);
        }
    }

    public function returnsNullForTheEnglishLocaleItselfEvenIfItHasAnAppPhp(): void
    {
        $placeholder = 'Placeholder Value';
        $dir = $this->makeTempLocaleDir('en', ['some.key' => $placeholder]);
        try {
            /** @var TranslationServiceClient&m\MockInterface $client */
            $client = m::mock(TranslationServiceClient::class);
            $result = $this->translateOneLocaleDiff($client, 'proj', ['some.key' => $placeholder], $dir);

            Assert::null($result);
        } finally {
            $this->cleanupDir($dir);
        }
    }

    public function returnsNullWhenTheDirectoryHasNoAppPhpAtAll(): void
    {
        $dir = $this->makeTempLocaleDir('missing-file', null);
        try {
            /** @var TranslationServiceClient&m\MockInterface $client */
            $client = m::mock(TranslationServiceClient::class);
            $result = $this->translateOneLocaleDiff($client, 'proj', ['some.key' => 'Placeholder Value'], $dir);

            Assert::null($result);
        } finally {
            $this->cleanupDir($dir);
        }
    }

    public function reportsUpToDateAndLeavesTheFileUntouchedWhenNothingIsMissing(): void
    {
        $existing = ['greeting' => 'Bonjour'];
        $dir = $this->makeTempLocaleDir('fr', $existing);
        $path = $dir . DIRECTORY_SEPARATOR . 'app.php';
        $beforeContent = file_get_contents($path);
        try {
            /** @var TranslationServiceClient&m\MockInterface $client */
            $client = m::mock(TranslationServiceClient::class);
            $result = $this->translateOneLocaleDiff($client, 'proj', ['greeting' => 'Hello'], $dir);

            Assert::same($result, 'fr: already up to date');
            Assert::same(file_get_contents($path), $beforeContent);
        } finally {
            $this->cleanupDir($dir);
        }
    }

    public function mergesTranslatesAndSortsWhenKeysAreMissing(): void
    {
        $existing = ['existing.key' => 'Existing'];
        $dir = $this->makeTempLocaleDir('de', $existing);
        $path = $dir . DIRECTORY_SEPARATOR . 'app.php';
        // Deliberately not alphabetical in en/app.php's own order --
        // the merged output must end up sorted regardless.
        $enContent = [
            'existing.key' => 'Existing',
            'zzz.last' => 'Zeta',
            'aaa.first' => 'Alpha',
        ];
        try {
            // translateContentBatch() dedupes+reorders via array_unique(),
            // but with two distinct values here the unique order matches
            // insertion order of the missing array: zzz.last then aaa.first.
            $client = $this->mockTranslationClient(['Zeta (DE)', 'Alpha (DE)']);
            $result = $this->translateOneLocaleDiff($client, 'proj', $enContent, $dir);

            Assert::same($result, 'de: +2 new keys (3 total)');

            /**
             * @var array<string, string> $written
             * @psalm-suppress UnresolvableInclude
             */
            $written = include $path; // NOSONAR — test fixture data file, deliberately dynamic
            Assert::same($written, [
                'aaa.first' => 'Alpha (DE)',
                'existing.key' => 'Existing',
                'zzz.last' => 'Zeta (DE)',
            ]);
            Assert::same(array_keys($written), ['aaa.first', 'existing.key', 'zzz.last']);
        } finally {
            $this->cleanupDir($dir);
        }
    }

    public function reportsFailureAndLeavesTheFileUntouchedWhenTranslationThrows(): void
    {
        $existing = ['existing.key' => 'Existing'];
        $dir = $this->makeTempLocaleDir('es', $existing);
        $path = $dir . DIRECTORY_SEPARATOR . 'app.php';
        $beforeContent = file_get_contents($path);
        try {
            /** @var TranslationServiceClient&m\MockInterface $client */
            $client = m::mock(TranslationServiceClient::class);
            $client->shouldReceive('translateText')->once()->andThrow(new \RuntimeException('quota exceeded'));

            $result = $this->translateOneLocaleDiff($client, 'proj', [
                'existing.key' => 'Existing',
                'new.key' => 'New Value',
            ], $dir);

            Assert::true($result !== null);
            Assert::true(str_starts_with($result, 'es: FAILED'));
            Assert::true(str_contains($result, 'quota exceeded'));
            // The original file must be untouched -- a failed
            // translation must never partially overwrite a locale.
            Assert::same(file_get_contents($path), $beforeContent);
        } finally {
            $this->cleanupDir($dir);
        }
    }
}
