<?php

declare(strict_types=1);

namespace App\Invoice\As4\Console;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Invoice\As4\As4MessageState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Prints a summary of the AS4 message queue grouped by state.
 *
 * Usage:
 *   php yii as4/status
 */
final class As4StatusCommand extends Command
{
    protected static string $defaultName = 'as4/status';

    public function __construct(
        private readonly CycleOrmAs4MessageRepository $repository,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Show a summary of all AS4 messages grouped by state.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('AS4 Message Status');

        /** @var array<string,int> $counts */
        $counts = [];
        $total  = 0;

        /** @var As4Message $message */
        foreach ($this->repository->findAllMessages() as $message) {
            $state = $message->getState()->value;
            $counts[$state] = ($counts[$state] ?? 0) + 1;
            $total++;
        }

        if ($total === 0) {
            $io->info('No AS4 messages found in the database.');
            return ExitCode::OK;
        }

        $rows = [];
        foreach (As4MessageState::cases() as $case) {
            $count = $counts[$case->value] ?? 0;
            $rows[] = [$case->value, $count];
        }
        $rows[] = ['<comment>TOTAL</comment>', "<comment>{$total}</comment>"];

        $io->table(['State', 'Count'], $rows);

        $failedCount = $counts[As4MessageState::failed->value] ?? 0;
        if ($failedCount > 0) {
            $io->warning(
                "{$failedCount} message(s) in FAILED state. "
                . "Run 'php yii as4/retry' or 'php yii as4/resend --message-id=<id>' to recover."
            );
        }

        return ExitCode::OK;
    }
}
