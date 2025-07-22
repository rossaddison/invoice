<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\Entity\InvItemAllowanceCharge;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of InvItemAllowanceCharge
 *
 * @extends Select\Repository<TEntity>
 */
final class InvItemAllowanceChargeRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get aciis  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

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
     * @psalm-param TEntity $acii
     *
     * @throws \Throwable
     */
    public function save(array|InvItemAllowanceCharge|null $acii): void
    {
        $this->entityWriter->write([$acii]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|InvItemAllowanceCharge|null $acii): void
    {
        $this->entityWriter->delete([$acii]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * All item allowances or charges for this invoice.
     */
    public function repoACIquery(string $inv_id): EntityReader
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoInvItemAllowanceChargequery(string $id): ?InvItemAllowanceCharge
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }

    public function repoInvCount(string $inv_id): int
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

        return $query->count();
    }

    public function repoInvItemCount(string $inv_item_id): int
    {
        $query = $this->select()
            ->where(['inv_item_id' => $inv_item_id]);

        return $query->count();
    }

    /**
     * All allowances and charges for this invoice item.
     */
    public function repoInvItemquery(string $inv_item_id): EntityReader
    {
        $query = $this->select()
            ->load('allowance_charge')
            ->where(['inv_item_id' => $inv_item_id]);

        return $this->prepareDataReader($query);
    }

    /**
     * Used in InvController function multiplecopy.
     *
     * @psalm-return TEntity|null
     */
    public function repoInvItemOriginalquery(string $inv_item_id): ?InvItemAllowanceCharge
    {
        $query = $this->select()
            ->load('allowance_charge')
            ->where(['inv_item_id' => $inv_item_id]);

        return $query->fetchOne() ?: null;
    }
}
