<?php

declare(strict_types=1);

namespace App\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;

interface InvWorkerAllocationRepositoryInterface
{
    public function save(array|InvWorkerAllocation|null $allocation): void;

    public function delete(array|InvWorkerAllocation|null $allocation): void;

    /**
     * Every allocation on one house, whichever side.
     *
     * @return list<InvWorkerAllocation>
     */
    public function repoForInvquery(int $invId): array;

    public function repoAllocationquery(int $id): ?InvWorkerAllocation;

    public function repoForInvSidequery(int $invId, AllocationSide $side): ?InvWorkerAllocation;
}
