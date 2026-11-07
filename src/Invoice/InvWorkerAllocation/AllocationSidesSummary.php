<?php

declare(strict_types=1);

namespace App\Invoice\InvWorkerAllocation;

use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Worker\WorkerRepository;

/**
 * Reads a house's per-side allocations for the places that list a run: the run sheet PDF, the
 * run sheet CSV and the offline snapshot. Before this they showed nothing about sides: a split
 * house has no single legacy worker, so its "assigned worker" came out blank.
 */
final readonly class AllocationSidesSummary
{
    public function __construct(
        private InvWorkerAllocationService $allocations,
        private WorkerRepository $workers,
    ) {
    }

    /**
     * Active workers by id, loaded once and passed to every {@see self::forInv()} call so a
     * long run sheet does not query the workers per row.
     *
     * @return array<int, string> worker id => "Firstname Lastname"
     */
    public function workerNames(): array
    {
        $names = [];
        foreach ($this->workers->findAllActive() as $worker) {
            $names[$worker->reqId()] = trim($worker->getFirstname() . ' ' . $worker->getLastname());
        }
        return $names;
    }

    /**
     * @param array<int, string> $workerNames from {@see self::workerNames()}
     * @return list<AllocationSideSummary> the house's allocated sides, oldest first (empty: none)
     */
    public function forInv(int $invId, array $workerNames): array
    {
        return array_map(
            static fn ($allocation): AllocationSideSummary => new AllocationSideSummary(
                $allocation->getSide(),
                $allocation->reqWorkerId(),
                $workerNames[$allocation->reqWorkerId()] ?? ('#' . $allocation->reqWorkerId()),
                $allocation->getStatus(),
                $allocation->isCarded(),
            ),
            $this->allocations->forInv($invId)
        );
    }

    /**
     * One line for a printed or exported row, for example "Front: Dave Smith; Back: Sam Jones".
     * Empty when the house has no allocation.
     *
     * @param array<int, string> $workerNames
     * @param callable(AllocationSide): string $sideLabel
     * @param callable(AllocationStatus): string $statusLabel
     */
    public function describe(int $invId, array $workerNames, callable $sideLabel, callable $statusLabel): string
    {
        return implode('; ', array_map(
            static fn (AllocationSideSummary $side): string => $side->text($sideLabel, $statusLabel),
            $this->forInv($invId, $workerNames)
        ));
    }
}
