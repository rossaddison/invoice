<?php

declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use App\Invoice\Entity\InvAllowanceCharge;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of InvAllowanceCharge
 *
 * @extends Select\Repository<TEntity>
 */
final class InvAllowanceChargeRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get invallowancecharges  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('allowance_charge');

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @psalm-param TEntity $invallowancecharge
     *
     * @throws \Throwable
     */
    public function save(array|InvAllowanceCharge|null $invallowancecharge): void
    {
        $this->entityWriter->write([$invallowancecharge]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|InvAllowanceCharge|null $invallowancecharge): void
    {
        $this->entityWriter->delete([$invallowancecharge]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoInvAllowanceChargeLoadedquery(string $id): ?InvAllowanceCharge
    {
        $query = $this->select()
            ->load('allowance_charge')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoACIquery(string $inv_id): EntityReader
    {
        $query = $this->select()
            ->load('allowance_charge')
            ->where(['inv_id' => $inv_id]);

        return $this->prepareDataReader($query);
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }

    public function repoACICount(string $inv_id): int
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

        return $query->count();
    }
}
