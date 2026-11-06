<?php

declare(strict_types=1);

namespace App\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of InvWorkerAllocation
 * @extends Select\Repository<TEntity>
 */
final class InvWorkerAllocationRepository extends Select\Repository implements InvWorkerAllocationRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @param array|InvWorkerAllocation|null $allocation
     * @throws Throwable
     */
    #[\Override]
    public function save(array|InvWorkerAllocation|null $allocation): void
    {
        $this->entityWriter->write([$allocation]);
    }

    /**
     * @param array|InvWorkerAllocation|null $allocation
     * @throws Throwable
     */
    #[\Override]
    public function delete(array|InvWorkerAllocation|null $allocation): void
    {
        $this->entityWriter->delete([$allocation]);
    }

    #[\Override]
    public function repoForInvquery(int $invId): array
    {
        return $this->select()
            ->where(['inv_id' => $invId])
            ->orderBy('id', 'ASC')
            ->fetchAll();
    }

    #[\Override]
    public function repoForInvSidequery(int $invId, AllocationSide $side): ?InvWorkerAllocation
    {
        return $this->select()
            ->where(['inv_id' => $invId, 'side' => $side->value])
            ->fetchOne() ?: null;
    }
}
