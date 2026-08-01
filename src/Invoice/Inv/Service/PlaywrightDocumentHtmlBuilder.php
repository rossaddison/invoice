<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Service;

use Yiisoft\Aliases\Aliases;

/**
 * Adapts InvPdfService::generateHtml()'s output (built for mPDF) so a real
 * browser can render it identically, for PdfTrait::pdfPlaywrightDocument().
 */
final readonly class PlaywrightDocumentHtmlBuilder
{
    public function __construct(private Aliases $aliases)
    {
    }

    public function build(string $html): string
    {
        $html = str_replace('</head>', '<style>' . $this->loadCss() . '</style></head>', $html);
        return $this->rewriteLogoSrcForBrowser($html);
    }

    /**
     * MpdfHelper reads these same 2 files via a locally-constructed
     * '@invoice' alias (src/Invoice/) — that alias isn't registered on the
     * DI-provided Aliases instance, only '@root' is.
     */
    private function loadCss(): string
    {
        $invoiceDir = $this->aliases->get('@root') . '/src/Invoice';
        $templates = file_get_contents($invoiceDir . '/Asset/invoice/css/templates.css');
        $custom = file_get_contents($invoiceDir . '/Asset/core/css/custom-pdf.css');
        return (false !== $templates ? $templates : '') . "\n" . (false !== $custom ? $custom : '');
    }

    /**
     * The company logo's <img src> is an absolute filesystem path
     * (company_logo_and_address.php builds it from the '@public' alias) —
     * correct for mPDF, which reads image files straight off disk, but a
     * real browser can't load a bare filesystem path as an image URL.
     * '@public' is the webroot itself, so stripping it leaves the correct
     * web-relative path.
     */
    private function rewriteLogoSrcForBrowser(string $html): string
    {
        $publicPath = $this->aliases->get('@public');
        $pattern = '#src="' . preg_quote($publicPath, '#') . '([^"]*)"#i';
        return preg_replace_callback(
            $pattern,
            static fn (array $m): string => 'src="/' . ltrim(str_replace('\\', '/', $m[1]), '/') . '"',
            $html,
        ) ?? $html;
    }
}
