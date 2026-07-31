<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Service;

use App\Invoice\Inv\Exception\PlaywrightRenderFailedException;
use Symfony\Component\Process\Process;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Shells out to playwright/render-invoice.js, which renders an invoice's
 * inv/view page to PDF via headless Chromium instead of mPDF — mPDF does
 * not reliably support Bootstrap5 (flexbox/grid, modern CSS). Shared by
 * RenderInvoicePdfCommand (CLI) and PdfTrait::pdfPlaywright() (web button).
 */
final readonly class PlaywrightPdfRenderService
{
    public function __construct(private Aliases $aliases)
    {
    }

    public function isBuilt(): bool
    {
        return is_file($this->scriptPath());
    }

    public function scriptPath(): string
    {
        return $this->aliases->get('@root') . '/playwright/render-invoice.js';
    }

    /**
     * @return string Combined stdout/stderr — the console command prints
     * this (it can contain a one-time 2FA setup secret to save); the web
     * button ignores it.
     *
     * @throws PlaywrightRenderFailedException
     */
    public function render(int $invoiceId, string $outputPath, TranslatorInterface $translator): string
    {
        $root = $this->aliases->get('@root');
        $args = [
            // Apache's service account doesn't inherit the interactive
            // user's PATH (where nvm/Node normally lives), so 'node' alone
            // resolves fine from the console but not from a web request —
            // NODE_BINARY lets ops point at an absolute path when needed.
            $_ENV['NODE_BINARY'] ?? 'node',
            '--env-file=' . $root . '/.env',
            $this->scriptPath(),
            (string) $invoiceId,
            $outputPath,
        ];
        // Points at node_modules/playwright-core/.local-browsers (installed
        // via `PLAYWRIGHT_BROWSERS_PATH=0 npx playwright install chromium`)
        // instead of the interactive user's profile — Apache's service
        // account has its own separate profile and can't see the latter.
        $process = new Process($args, $root, ['PLAYWRIGHT_BROWSERS_PATH' => '0']);
        $process->setTimeout(120);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new PlaywrightRenderFailedException($translator, $process->getErrorOutput());
        }
        return $process->getOutput() . $process->getErrorOutput();
    }
}
