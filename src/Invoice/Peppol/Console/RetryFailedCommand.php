<?php

declare(strict_types=1);

namespace App\Invoice\Peppol\Console;

use App\Invoice\Peppol\PeppolMessageRepositoryInterface;
use App\Invoice\Peppol\PeppolSendService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Retries all FAILED Peppol messages that have stored UBL XML and have not
 * exceeded the retry limit.
 *
 * Usage:
 *   php yii peppol/retry-failed
 *   php yii peppol/retry-failed --max-retries=5
 *
 * Run via cron every few minutes for automatic recovery:
 *   * / 5 * * * * php /var/www/invoice/yii peppol/retry-failed >> /var/log/peppol-retry.log 2>&1
 */
final class RetryFailedCommand extends Command
{
    protected static string $defaultName = 'peppol/retry-failed';

    private const int DEFAULT_MAX_RETRIES = 3;

    public function __construct(
        private readonly PeppolMessageRepositoryInterface $pmR,
        private readonly PeppolSendService $sendService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription(
                'Retry all FAILED Peppol messages that have stored UBL XML '
                . 'and have not exceeded the retry limit.'
            )
            ->addOption(
                'max-retries',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum number of send attempts before a message is abandoned.',
                self::DEFAULT_MAX_RETRIES,
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maxRetries = (int) ($input->getOption('max-retries') ?? self::DEFAULT_MAX_RETRIES);

        $attempted = 0;
        $succeeded = 0;
        $skipped   = 0;

        foreach ($this->pmR->repoByStatus('FAILED') as $message) {
            if ($message->getRetryCount() >= $maxRetries) {
                $skipped++;
                continue;
            }

            $ublXml = $message->getUblXml();

            if ($ublXml === null || $ublXml === '') {
                $skipped++;
                $io->warning(
                    "Message #{$message->reqId()} skipped — no stored UBL XML."
                );
                continue;
            }

            $attempted++;

            try {
                $result = $this->sendService->retry($message, $ublXml);

                if ($result->getStatus() === 'SENT') {
                    $succeeded++;
                    $io->writeln(
                        "<info>Message #{$message->reqId()} → SENT"
                        . " ({$result->getMessageId()})</info>"
                    );
                } else {
                    $io->writeln(
                        "<comment>Message #{$message->reqId()} → {$result->getStatus()}"
                        . ": {$result->getErrorMessage()}</comment>"
                    );
                }
            } catch (\Throwable $e) {
                $this->logger->error('Peppol retry threw an exception', [
                    'peppol_message_id' => $message->reqId(),
                    'error'             => $e->getMessage(),
                ]);
                $io->error(
                    "Message #{$message->reqId()} exception: {$e->getMessage()}"
                );
            }
        }

        $io->table(
            ['FAILED found', 'Attempted', 'Succeeded', 'Skipped'],
            [[$attempted + $skipped, $attempted, $succeeded, $skipped]],
        );

        return ExitCode::OK;
    }
}
