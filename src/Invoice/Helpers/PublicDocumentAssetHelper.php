<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Asset\BootstrapCssOnlyAsset;
use App\Invoice\Asset\NodeModulesBootstrapIconsAsset;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;

/**
 * Resolves the CSS needed by the standalone public document templates
 * (Quote_Web.php / Invoice_Web.php / SalesOrder_Web.php). These templates
 * echo a complete, self-contained <html> document via
 * webViewRenderer->renderPartialAsString() rather than going through the
 * normal layout, so they cannot rely on $this->addCssFiles(...) the way
 * resources/views/layout/invoice.php does -- there is no surrounding
 * WebView head to merge into.
 *
 * They previously linked to a hardcoded '/assets/css/invoice-documents.css'
 * that was never actually created (a dead reference since it was first
 * added, 10 Jan 2026 -- see project_public_document_css_fix memory). This
 * resolves the two already-published bundles these templates' Bootstrap
 * classes (btn, container, table-condensed, bi bi-check-lg, ...) need, plus
 * the hand-written rules that were already written for exactly these
 * templates but only ever wired up for PDF generation -- see the
 * "Invoice_Web.php public template" comment inside custom-pdf.css itself,
 * and how MpdfHelper/PlaywrightDocumentHtmlBuilder already inline the same
 * file for the PDF versions of these documents.
 */
final readonly class PublicDocumentAssetHelper
{
    /**
     * @return array{bootstrapCssUrl: string, bootstrapIconsCssUrl: string, customPdfCss: string}
     */
    public static function resolve(AssetManager $assetManager, Aliases $aliases): array
    {
        $bootstrapCssUrl = $assetManager->getUrl(BootstrapCssOnlyAsset::class, 'bootstrap.min.css');
        $bootstrapIconsCssUrl = $assetManager->getUrl(NodeModulesBootstrapIconsAsset::class, 'bootstrap-icons.min.css');
        $customPdfCssPath = $aliases->get('@invoice/Asset/core/css/custom-pdf.css');
        $customPdfCss = (string) file_get_contents($customPdfCssPath);
        return ['bootstrapCssUrl' => $bootstrapCssUrl, 'bootstrapIconsCssUrl' => $bootstrapIconsCssUrl, 'customPdfCss' => $customPdfCss];
    }
}
