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
 * wiring mistake here (wrong bundle class, wrong path) would otherwise
 * only surface as an unstyled/still-broken public document in production --
 * which is exactly what happened once already: the first version of this
 * helper resolved the CSS via a nonexistent '@invoice' alias (copied from
 * MpdfHelper's own unexercised, equally-broken usage of it), throwing
 * InvalidArgumentException live on yii3i.online the moment an observer
 * actually approved a quote. Only '@root' is a real registered alias
 * (config/common/params.php's 'yiisoft/aliases' block) -- this test builds
 * a real nested src/Invoice/Asset/core/css/custom-pdf.css under a fake
 * '@root' so a regression back to '@invoice' (or any other wrong alias)
 * fails loudly here instead of only in production.
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

        // A fake '@root' with the real nested path the helper actually
        // reads from -- not just a bare file handed straight to Aliases::get()
        // -- so this test would have caught the '@invoice' mistake.
        $fakeRoot = sys_get_temp_dir() . '/public-document-asset-helper-' . uniqid();
        $cssDir = $fakeRoot . '/src/Invoice/Asset/core/css';
        Assert::true(mkdir($cssDir, 0777, true));
        $cssPath = $cssDir . '/custom-pdf.css';
        file_put_contents($cssPath, '.amount { text-align: right; }');

        /** @var Aliases&m\MockInterface $aliases */
        $aliases = m::mock(Aliases::class);
        $aliases->shouldReceive('get')->once()->with('@root')->andReturn($fakeRoot);

        try {
            $result = PublicDocumentAssetHelper::resolve($assetManager, $aliases);

            Assert::same($result, [
                'bootstrapCssUrl' => 'https://example.test/assets/abc123/bootstrap.min.css',
                'bootstrapIconsCssUrl' => 'https://example.test/assets/def456/bootstrap-icons.min.css',
                'customPdfCss' => '.amount { text-align: right; }',
            ]);
        } finally {
            unlink($cssPath);
            rmdir($cssDir);
            rmdir($fakeRoot . '/src/Invoice/Asset/core');
            rmdir($fakeRoot . '/src/Invoice/Asset');
            rmdir($fakeRoot . '/src/Invoice');
            rmdir($fakeRoot . '/src');
            rmdir($fakeRoot);
        }
    }
}
