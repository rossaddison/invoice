<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Asset\BootstrapCssOnlyAsset;
use App\Invoice\Asset\NodeModulesBootstrapIconsAsset;
use App\Invoice\Helpers\PublicDocumentAssetHelper;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;

/**
 * The three public "Web" document templates (Quote_Web.php/Invoice_Web.php/
 * SalesOrder_Web.php) render a complete, standalone <html> document on
 * their own via renderPartialAsString() -- not through the normal layout
 * pipeline -- so they cannot rely on $this->addCssFiles() to pull in
 * Bootstrap/Bootstrap Icons or the already-written custom-pdf.css the way
 * resources/views/layout/invoice.php does. PublicDocumentAssetHelper
 * resolves exactly what those templates need to actually be styled; this
 * replaced a hardcoded link to a CSS file ('/assets/css/invoice-documents.css')
 * that never existed in the repo (see project_public_document_css_fix
 * memory). This test asserts the helper asks AssetManager/Aliases for
 * precisely the right bundle classes, filenames and alias path -- a real
 * wiring mistake here (wrong bundle class, wrong filename) would otherwise
 * only surface as an unstyled/still-404ing public document in production.
 */
#[Test]
final class PublicDocumentAssetHelperTest
{
    public function resolveAsksForTheExactBootstrapAndIconsAssetsAndInlinesCustomPdfCss(): void
    {
        /** @var AssetManager&m\MockInterface $assetManager */
        $assetManager = m::mock(AssetManager::class);
        $assetManager->shouldReceive('getUrl')->once()
            ->with(BootstrapCssOnlyAsset::class, 'bootstrap.min.css')
            ->andReturn('https://example.test/assets/abc123/bootstrap.min.css');
        $assetManager->shouldReceive('getUrl')->once()
            ->with(NodeModulesBootstrapIconsAsset::class, 'bootstrap-icons.min.css')
            ->andReturn('https://example.test/assets/def456/bootstrap-icons.min.css');

        $tmpName = tempnam(sys_get_temp_dir(), 'custom-pdf-');
        if ($tmpName === false) {
            Assert::fail('tempnam() failed to allocate a temp file');
        }
        $cssPath = $tmpName . '.css';
        file_put_contents($cssPath, '.amount { text-align: right; }');

        /** @var Aliases&m\MockInterface $aliases */
        $aliases = m::mock(Aliases::class);
        $aliases->shouldReceive('get')->once()
            ->with('@invoice/Asset/core/css/custom-pdf.css')
            ->andReturn($cssPath);

        try {
            $result = PublicDocumentAssetHelper::resolve($assetManager, $aliases);

            Assert::same($result, [
                'bootstrapCssUrl' => 'https://example.test/assets/abc123/bootstrap.min.css',
                'bootstrapIconsCssUrl' => 'https://example.test/assets/def456/bootstrap-icons.min.css',
                'customPdfCss' => '.amount { text-align: right; }',
            ]);
        } finally {
            unlink($cssPath);
        }
    }
}
