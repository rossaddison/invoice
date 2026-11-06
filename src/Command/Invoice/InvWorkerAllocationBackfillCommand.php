<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Inv\InvRepository;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * One-off carry-over of every invoice's legacy single `worker_id` into the
 * `inv_worker_allocation` table, as a whole-house ("both") allocation that
 * keeps the original allocation time. Idempotent: an invoice that already
 * has any allocation row is skipped, so it is safe to run more than once.
 * Run after the table exists (BUILD_DATABASE schema sync).
 */
final class InvWorkerAllocationBackfillCommand extends Command
{
    protected static string $defaultName = 'homecare/backfill-allocations';

    public function __construct(
        private readonly InvRepository $invRepository,
        private readonly InvWorkerAllocationService $service,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this->setDescription(
            'Copy each invoice\'s legacy worker_id into inv_worker_allocation as a whole-house allocation'
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;
        $skipped = 0;
        foreach ($this->invRepository->repoWithWorkerAllocated() as $inv) {
            $workerId = $inv->getWorkerId();
            if (null === $workerId) {
                continue;
            }
            $this->service->backfillLegacy($inv->reqId(), $workerId, $inv->getWorkerAllocatedAt())
                ? $created++
                : $skipped++;
        }
        $io->success(sprintf('%d allocation(s) created, %d already present.', $created, $skipped));
        return ExitCode::OK;
    }
}
