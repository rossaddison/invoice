<?php

declare(strict_types=1);

namespace App\Invoice\As4\Console;

use App\Invoice\As4\As4RetryEngine;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Processes pending AS4 retry queue and checks for missing receipts.
 *
 * Usage:
 *   php yii as4/retry
 *
 * Run via cron every few minutes for automatic recovery:
 *   *\/5 * * * * php /var/www/invoice/yii as4/retry >> /var/log/as4-retry.log 2>&1
 */
final class As4RetryCommand extends Command
{
    protected static string $defaultName = 'as4/retry';

    public function __construct(
        private readonly As4RetryEngine $retryEngine,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            'Process pending AS4 retries and detect EBMS:0301 missing-receipt timeouts.'
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('AS4 Retry Engine');

        try {
            $stats = $this->retryEngine->processRetries();

            $io->table(
                ['Processed', 'Succeeded', 'Failed'],
                [[$stats['processed'], $stats['succeeded'], $stats['failed']]],
            );

            $timedOut = $this->retryEngine->detectMissingReceipts();
            if ($timedOut > 0) {
                $io->warning("EBMS:0301 raised for {$timedOut} message(s) that exceeded receipt timeout.");
            }
        } catch (\Throwable $e) {
            $this->logger->error('as4/retry command failed', ['error' => $e->getMessage()]);
            $io->error($e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
