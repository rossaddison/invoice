<?php

declare(strict_types=1);

namespace App\Command\Bookkeeping;

use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupSyncService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Refreshes the cached FrontAccounting tax groups, stock/service items,
 * bank accounts and payment terms that the Online Bookkeeping settings
 * tab's FrontAccounting dropdowns are built from.
 *
 * Usage:
 *   php yii bookkeeping/frontaccounting-sync-lookups
 */
final class FrontAccountingLookupSyncCommand extends Command
{
    protected static string $defaultName = 'bookkeeping/frontaccounting-sync-lookups';

    public function __construct(
        private readonly FrontAccountingLookupSyncService $syncService,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Sync FrontAccounting tax groups, stock items, bank accounts and payment terms for the settings dropdowns.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('FrontAccounting lookup sync');

        $summary = $this->syncService->sync();

        $io->writeln(sprintf('Synced: %d', $summary->syncedCount));
        foreach ($summary->messages as $message) {
            $io->warning($message);
        }

        return $summary->messages === [] ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
