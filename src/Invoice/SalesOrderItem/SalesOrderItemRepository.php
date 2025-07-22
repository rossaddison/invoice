<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Entity\SalesOrderItem;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of SalesOrderItem
 *
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderItemRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get salesorderitems  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load(['tax_rate', 'product', 'sales_order']);

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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function save(array|SalesOrderItem|null $salesorderitem): void
    {
        $this->entityWriter->write([$salesorderitem]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|SalesOrderItem|null $salesorderitem): void
    {
        $this->entityWriter->delete([$salesorderitem]);
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
    public function repoSalesOrderItemquery(string $id): ?SalesOrderItem
    {
        $query = $this->select()->load(['tax_rate', 'product', 'sales_order'])
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Get all items id's that belong to a specific salesorder.
     *
     * @psalm-return EntityReader
     */
    public function repoSalesOrderItemIdquery(string $salesorder_id): EntityReader
    {
        $query = $this->select()
            ->load(['tax_rate', 'product', 'sales_order'])
            ->where(['sales_order_id' => $salesorder_id]);

        return $this->prepareDataReader($query);
    }

    /**
     * Get all items belonging to salesorder.
     *
     * @psalm-return EntityReader
     */
    public function repoSalesOrderquery(string $salesorder_id): EntityReader
    {
        $query = $this->select()
            ->load(['tax_rate', 'product', 'sales_order'])
            ->where(['sales_order_id' => $salesorder_id]);

        return $this->prepareDataReader($query);
    }

    public function repoCount(string $salesorder_id): int
    {
        return $this->select()
            ->where(['sales_order_id' => $salesorder_id])
            ->count();
    }

    public function repoSalesOrderItemCount(string $id): int
    {
        return $this->select()
            ->where(['id' => $id])
            ->count();
    }

    /**
     * Get selection of salesorder items from all salesorder_items.
     */
    public function findinSalesOrderItems(array $item_ids): EntityReader
    {
        $query = $this->select()->where(['id' => ['in' => new Parameter($item_ids)]]);

        return $this->prepareDataReader($query);
    }
}
