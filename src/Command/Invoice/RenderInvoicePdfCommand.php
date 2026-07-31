<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Inv\Exception\PlaywrightRenderFailedException;
use App\Invoice\Inv\Service\PlaywrightPdfRenderService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Shells out to playwright/render-invoice.js, which renders the invoice
 * document (the same one mPDF converts, via inv/pdfPlaywrightDocument) to
 * PDF via headless Chromium instead of mPDF — mPDF does not reliably
 * support Bootstrap5 (flexbox/grid, modern CSS), so this exists to produce
 * the same professional-looking document with better rendering fidelity.
 */
final class RenderInvoicePdfCommand extends Command
{
    protected static string $defaultName = 'invoice/render-pdf';

    public function __construct(
        private readonly Aliases $aliases,
        private readonly PlaywrightPdfRenderService $renderService,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription(
                'Renders the invoice document to PDF via headless Chromium '
                . '(Playwright), for comparing against mPDF\'s output.'
            )
            ->setHelp(
                'Requires `npm run build:playwright` to have been run at least once, '
                . 'and PLAYWRIGHT_TEST_EMAIL / PLAYWRIGHT_TEST_PASSWORD set in .env to '
                . 'a real, working account (see .env.example). Once that account has '
                . '2FA enabled, PLAYWRIGHT_TEST_TOTP_SECRET must also be set — the '
                . 'script prints the secret to save on its first run.'
            )
            ->addArgument('invoiceId', InputArgument::REQUIRED, 'Invoice id to render')
            ->addArgument(
                'outputPath',
                InputArgument::OPTIONAL,
                'Where to write the PDF (defaults to playwright/output/invoice-{id}.pdf)',
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $invoiceId */
        $invoiceId = $input->getArgument('invoiceId');
        $validationError = $this->validate($invoiceId);
        if (null !== $validationError) {
            $io->error($validationError);
            return ExitCode::DATAERR;
        }

        /** @var string|null $outputPathArg */
        $outputPathArg = $input->getArgument('outputPath');
        $outputPath = (null !== $outputPathArg && $outputPathArg !== '')
            ? $outputPathArg
            : $this->aliases->get('@root') . '/playwright/output/invoice-' . $invoiceId . '.pdf';

        try {
            $renderOutput = $this->renderService->render((int) $invoiceId, $outputPath, $this->translator);
        } catch (PlaywrightRenderFailedException $e) {
            $io->error($e->getDetail());
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (trim($renderOutput) !== '') {
            $output->writeln($renderOutput);
        }
        $io->success('PDF rendered successfully: ' . $outputPath);
        return ExitCode::OK;
    }

    private function validate(string $invoiceId): ?string
    {
        if (!preg_match('/^\d+$/', $invoiceId)) {
            return 'invoiceId must be a positive integer.';
        }
        if (!$this->renderService->isBuilt()) {
            return 'Compiled script not found: ' . $this->renderService->scriptPath()
                . ' — run `npm run build:playwright` first.';
        }
        return null;
    }
}
