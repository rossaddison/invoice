<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\RecordOwnerRepair;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Quotes, sales orders and invoices that were moved to another client before November 2026 kept the old client's
 * user, so neither client's guest can open them.
 *
 *   php yii invoice/repair-record-owners          list them, and say which can be repaired (nothing is changed)
 *   php yii invoice/repair-record-owners --force  give each repairable record to its client's user
 *
 * Back up first: php yii setting/backup-database. See RecordOwnerRepair for exactly which records qualify.
 */
final class RepairRecordOwnersCommand extends Command
{
    protected static string $defaultName = 'invoice/repair-record-owners';

    private const string OPTION_FORCE = 'force';

    public function __construct(private readonly RecordOwnerRepair $repair)
    {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription(
                'Give quotes, sales orders and invoices moved to another client before the fix to that client\'s user'
                . ' (dry run unless --force)',
            )
            ->addOption(self::OPTION_FORCE, null, InputOption::VALUE_NONE, 'Actually change them; without it only lists');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mismatches = $this->repair->findMismatches();

        if ([] === $mismatches) {
            $io->success('Every quote, sales order and invoice belongs to the user of its client. Nothing to repair.');
            return ExitCode::OK;
        }

        $io->table(
            ['Type', 'Id', 'Number', 'Client', 'Current user', 'Client\'s user', 'Action'],
            array_map(
                static fn (array $m): array => [
                    $m['table'], $m['id'], $m['number'], $m['clientId'], $m['userId'], $m['ownerId'],
                    $m['repairable'] ? 'repair' : 'skip: the client\'s user is inactive',
                ],
                $mismatches,
            ),
        );
        $repairable = count(array_filter($mismatches, static fn (array $m): bool => $m['repairable']));

        if (!(bool) $input->getOption(self::OPTION_FORCE)) {
            $io->note(
                $repairable . ' of ' . count($mismatches) . ' record(s) would be given to their client\'s user.'
                . ' Back up first (php yii setting/backup-database), then run again with --force.',
            );
            return ExitCode::OK;
        }

        $changed = $this->repair->repair($mismatches);
        $io->success(
            [] === $changed
                ? 'Nothing was changed.'
                : 'Repaired: ' . implode(', ', array_map(
                    static fn (string $table, int $count): string => $count . ' ' . $table,
                    array_keys($changed),
                    $changed,
                )) . '.',
        );
        if ($repairable < count($mismatches)) {
            $io->warning(
                (count($mismatches) - $repairable) . ' record(s) were left as they are because their client\'s user'
                . ' is inactive. Activate that user, or move the record, and run this again.',
            );
        }

        return ExitCode::OK;
    }
}
