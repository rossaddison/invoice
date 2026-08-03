<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Console;

use App\Invoice\Setting\DatabaseBackupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Writes a timestamped, gzip-compressed database dump to a persistent
 * directory (default @root/backups) and prunes old dumps, keeping the most
 * recent N. Unlike Setting > Backup's on-demand browser download, this is
 * meant for a server crontab so a local copy accumulates automatically.
 *
 * Usage (cron, daily):
 *   php yii setting/backup-database
 *   php yii setting/backup-database --keep=30
 */
final class BackupDatabaseCommand extends Command
{
    protected static string $defaultName = 'setting/backup-database';

    private const DEFAULT_KEEP = 14;

    public function __construct(
        private readonly Aliases $aliases,
        private readonly DatabaseBackupService $backupService,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Write a gzipped database dump to disk and prune old backups.')
            ->addOption(
                'keep',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of most recent backups to retain',
                (string) self::DEFAULT_KEEP,
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Database Backup');

        $keep = max(1, (int) $input->getOption('keep'));
        $directory = $this->aliases->get('@root') . '/backups';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $io->error("Unable to create backup directory: {$directory}");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $filePath = $directory . '/invoice_backup_' . date('Ymd_His') . '.sql.gz';

        try {
            $this->backupService->writeGzippedDump($filePath);
        } catch (\RuntimeException $e) {
            $io->error('Backup failed: ' . $e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $io->writeln('Backup written: ' . $filePath);

        $removed = $this->pruneOldBackups($directory, $keep);
        if ($removed > 0) {
            $io->writeln("Pruned {$removed} old backup(s), keeping the most recent {$keep}.");
        }

        $io->success('Database backup complete.');
        return ExitCode::OK;
    }

    private function pruneOldBackups(string $directory, int $keep): int
    {
        $files = glob($directory . '/invoice_backup_*.sql.gz');
        if ($files === false || count($files) <= $keep) {
            return 0;
        }
        usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        $removed = 0;
        foreach (array_slice($files, $keep) as $stale) {
            if (unlink($stale)) {
                $removed++;
            }
        }
        return $removed;
    }
}
