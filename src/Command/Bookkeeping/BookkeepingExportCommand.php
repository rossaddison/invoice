<?php

declare(strict_types=1);

namespace App\Command\Bookkeeping;

use App\Bookkeeping\Application\BookkeepingService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Exports every not-yet-exported BookkeepingTransaction to the configured
 * bookkeeping provider (currently QuickBooks).
 *
 * Usage:
 *   php yii bookkeeping/export
 */
final class BookkeepingExportCommand extends Command
{
    protected static string $defaultName = 'bookkeeping/export';

    public function __construct(
        private readonly BookkeepingService $bookkeepingService,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Export queued bookkeeping transactions to the configured provider.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Bookkeeping export');

        $summary = $this->bookkeepingService->exportDueTransactions();

        $io->writeln(sprintf('Exported: %d', $summary->exportedCount));
        $io->writeln(sprintf('Failed:   %d', $summary->failedCount));
        foreach ($summary->messages as $message) {
            $io->warning($message);
        }

        return $summary->failedCount === 0 ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
