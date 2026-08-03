<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring\Console;

use App\Invoice\InvRecurring\InvCronUserDeps;
use App\Invoice\InvRecurring\InvItemDeps;
use App\Invoice\InvRecurring\InvRecurringCronService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Creates due recurring invoices and sends Telegram balance reminders.
 * Replaces the curl+cron_key HTTP trigger (InvRecurringController::cron(),
 * still kept for existing setups) with a real console entry point suitable
 * for a server crontab.
 *
 * Usage (cron, daily):
 *   php yii invrecurring/process
 */
final class ProcessRecurringInvoicesCommand extends Command
{
    protected static string $defaultName = 'invrecurring/process';

    public function __construct(
        private readonly InvRecurringCronService $cronService,
        private readonly InvItemDeps $itemDeps,
        private readonly InvCronUserDeps $cronDeps,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Create due recurring invoices and send Telegram balance reminders.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Process Recurring Invoices');

        $user = $this->cronService->resolveAdminUser($this->cronDeps);
        if (null === $user) {
            $io->error('No admin user found — cannot attribute generated invoices.');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $result = $this->cronService->process($user, $this->itemDeps, $this->cronDeps);

        $io->writeln("Invoices created:    {$result['created']}");
        $io->writeln("Reminders sent:      {$result['reminded']}");
        $io->success('Recurring invoice processing complete.');

        return ExitCode::OK;
    }
}
