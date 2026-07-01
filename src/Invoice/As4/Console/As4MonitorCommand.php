<?php

declare(strict_types=1);

namespace App\Invoice\As4\Console;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Invoice\As4\As4MessageState;
use App\Invoice\As4\As4RetryEngine;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Health-check for the AS4 access point.
 *
 * Checks:
 *   1. Peppol signing certificate expiry (if --signing-cert is given)
 *   2. Peppol encryption certificate expiry (if --encrypt-cert is given)
 *   3. Messages stuck in FAILED state
 *   4. EBMS:0301 missing-receipt detection
 *
 * Exit codes:
 *   0 — all green
 *   1 — one or more warnings or errors found
 *
 * Usage (cron, every hour):
 *   php yii as4/monitor \
 *     --signing-cert=/path/to/signing.pem \
 *     --encrypt-cert=/path/to/encrypt.pem \
 *     --warn-days=30
 */
final class As4MonitorCommand extends Command
{
    protected static string $defaultName = 'as4/monitor';

    private const int DEFAULT_WARN_DAYS = 30;

    public function __construct(
        private readonly CycleOrmAs4MessageRepository $repository,
        private readonly As4RetryEngine $retryEngine,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription(
                'Health-check: certificate expiry, FAILED messages, EBMS:0301 missing receipts.'
            )
            ->addOption(
                'signing-cert',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to the Peppol signing certificate (PEM/DER).',
            )
            ->addOption(
                'encrypt-cert',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to the Peppol encryption certificate (PEM/DER).',
            )
            ->addOption(
                'warn-days',
                null,
                InputOption::VALUE_REQUIRED,
                'Warn when a certificate expires within this many days.',
                self::DEFAULT_WARN_DAYS,
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $io->title('AS4 Access Point Monitor');
        $warnDays = (int) ($input->getOption('warn-days') ?? self::DEFAULT_WARN_DAYS);
        $issues   = 0;

        // ── 1. Certificate expiry ─────────────────────────────────────────────
        foreach (['signing-cert' => 'Signing', 'encrypt-cert' => 'Encryption'] as $opt => $label) {
            $path = (string) ($input->getOption($opt) ?? '');
            if ($path === '') {
                continue;
            }
            $result = $this->checkCert($path, $label, $warnDays, $io);
            $issues += $result;
        }

        // ── 2. Stuck FAILED messages ──────────────────────────────────────────
        $failedIds = [];
        /** @var As4Message $message */
        foreach ($this->repository->findAllMessages() as $message) {
            if ($message->getState() === As4MessageState::failed) {
                $failedIds[] = $message->reqId();
            }
        }

        if ($failedIds !== []) {
            $io->warning(
                count($failedIds) . ' message(s) in FAILED state: IDs '
                . implode(', ', array_slice($failedIds, 0, 10))
                . (count($failedIds) > 10 ? ' …' : '')
                . '. Run: php yii as4/retry'
            );
            $issues++;
        } else {
            $io->writeln('<info>✓ No messages in FAILED state.</info>');
        }

        // ── 3. EBMS:0301 missing-receipt detection ────────────────────────────
        try {
            $timedOut = $this->retryEngine->detectMissingReceipts();
            if ($timedOut > 0) {
                $io->warning(
                    "EBMS:0301 raised for {$timedOut} message(s) that exceeded the receipt timeout."
                );
                $issues++;
            } else {
                $io->writeln('<info>✓ No receipt timeouts detected.</info>');
            }
        } catch (\Throwable $e) {
            $io->error("Missing-receipt detection failed: {$e->getMessage()}");
            $issues++;
        }

        // ── Summary ───────────────────────────────────────────────────────────
        $io->newLine();
        if ($issues === 0) {
            $io->success('All checks passed.');
            return ExitCode::OK;
        }

        $io->error("{$issues} issue(s) found. Review the warnings above.");
        return ExitCode::UNSPECIFIED_ERROR;
    }

    private function checkCert(
        string $path,
        string $label,
        int $warnDays,
        SymfonyStyle $io,
    ): int {
        if (!is_readable($path)) {
            $io->error("{$label} certificate not readable: {$path}");
            return 1;
        }

        $pem  = (string) file_get_contents($path);
        $cert = openssl_x509_read($pem);
        if ($cert === false) {
            $io->error("{$label} certificate cannot be parsed: {$path}");
            return 1;
        }

        /** @var array{validTo_time_t: int, subject: array<string,string>} $info */
        $info     = openssl_x509_parse($cert);
        $expiresAt = (new DateTime())->setTimestamp($info['validTo_time_t']);
        $now       = new DateTime();
        $diff      = (int) $now->diff($expiresAt)->days;
        $expired   = $expiresAt < $now;
        $cn        = $info['subject']['CN'] ?? $path;

        if ($expired) {
            $io->error("{$label} certificate EXPIRED on {$expiresAt->format('Y-m-d')} (CN={$cn})");
            return 1;
        }

        if ($diff <= $warnDays) {
            $io->warning(
                "{$label} certificate expires in {$diff} day(s) on "
                . $expiresAt->format('Y-m-d') . " (CN={$cn})"
            );
            return 1;
        }

        $io->writeln(
            "<info>✓ {$label} certificate valid until "
            . $expiresAt->format('Y-m-d') . " ({$diff} days, CN={$cn})</info>"
        );
        return 0;
    }
}
