<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItemAmount;

use App\Invoice\Entity\SalesOrderItemAmount;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderItemAmount
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderItemAmountRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get salesorderitemamounts  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('sales_order_item');
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderItemAmount|null $salesorderitemamount
     * @throws Throwable
     */
    public function save(array|SalesOrderItemAmount|null $salesorderitemamount): void
    {
        $this->entityWriter->write([$salesorderitemamount]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderItemAmount|null $salesorderitemamount
     * @throws Throwable
     */
    public function delete(array|SalesOrderItemAmount|null $salesorderitemamount): void
    {
        $this->entityWriter->delete([$salesorderitemamount]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @return SalesOrderItemAmount|null
     *
     * @psalm-return TEntity|null
     */
    public function repoSalesOrderItemAmountquery(string $sales_order_item_id): ?SalesOrderItemAmount
    {
        $query = $this->select()
                      ->load(['sales_order_item'])
                      ->where(['sales_order_item_id' => $sales_order_item_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $sales_order_item_id
     * @return int
     */
    public function repoCount(string $sales_order_item_id): int
    {
        $query = $this->select()
                      ->where(['sales_order_item_id' => $sales_order_item_id]);
        return $query->count();
    }
}
