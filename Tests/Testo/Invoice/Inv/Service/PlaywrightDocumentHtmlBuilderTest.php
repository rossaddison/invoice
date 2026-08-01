<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Service;

use App\Invoice\Inv\Service\PlaywrightDocumentHtmlBuilder;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers PlaywrightDocumentHtmlBuilder: the company logo's <img src> is an
 * absolute filesystem path (correct for mPDF, which reads image files
 * straight off disk), but a real browser navigating to
 * inv/pdfPlaywrightDocument can't load a bare filesystem path as an image
 * URL, so build() rewrites it to a web-relative one. loadCss() (file reads)
 * isn't covered here — only the pure string logic.
 */
#[Test]
final class PlaywrightDocumentHtmlBuilderTest
{
    private function makeAliases(string $publicPath, string $root = '/nonexistent-root'): Aliases
    {
        $aliases = m::mock(Aliases::class);
        /** @var \Mockery\Expectation $e */
        $e = $aliases->shouldReceive('get');
        $e->with('@root')->andReturn($root);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $aliases->shouldReceive('get');
        $e2->with('@public')->andReturn($publicPath);
        return $aliases;
    }

    public function rewritesWindowsStyleAbsoluteLogoPathToWebRelativeUrl(): void
    {
        $builder = new PlaywrightDocumentHtmlBuilder(
            $this->makeAliases('C:/wamp64/www/invoice/public'),
        );
        $html = '<head></head><body><img src="C:/wamp64/www/invoice/public\site\logo.png" width="150"></body>';

        $result = $builder->build($html);

        Assert::true(str_contains($result, 'src="/site/logo.png"'));
    }

    public function rewritesUnixStyleAbsoluteLogoPathToWebRelativeUrl(): void
    {
        $builder = new PlaywrightDocumentHtmlBuilder(
            $this->makeAliases('/var/www/invoice/public'),
        );
        $html = '<head></head><body><img src="/var/www/invoice/public/logo/company.png" width="150"></body>';

        $result = $builder->build($html);

        Assert::true(str_contains($result, 'src="/logo/company.png"'));
    }

    public function leavesHtmlUnchangedWhenNoMatchingLogoSrcPresent(): void
    {
        $builder = new PlaywrightDocumentHtmlBuilder(
            $this->makeAliases('/var/www/invoice/public'),
        );
        $html = '<head></head><body><div>No image here</div></body>';

        Assert::true(str_contains($builder->build($html), '<div>No image here</div>'));
    }

    public function injectsCssBeforeClosingHeadTag(): void
    {
        $builder = new PlaywrightDocumentHtmlBuilder(
            $this->makeAliases('/var/www/invoice/public'),
        );

        $result = $builder->build('<head></head><body></body>');

        Assert::true(str_contains($result, '<style>'));
        Assert::true((bool) preg_match('#<style>.*</style></head>#s', $result));
    }
}
