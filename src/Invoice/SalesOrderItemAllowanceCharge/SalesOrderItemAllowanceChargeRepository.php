<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItemAllowanceCharge;

use App\Invoice\Entity\SalesOrderItemAllowanceCharge;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderItemAllowanceCharge
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderItemAllowanceChargeRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(
        Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get acsois  without filter
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

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderItemAllowanceCharge|null $acsoi
     * @psalm-param TEntity $acsoi
     * @throws Throwable
     */
    public function save(array|SalesOrderItemAllowanceCharge|null $acsoi): void
    {
        $this->entityWriter->write([$acsoi]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderItemAllowanceCharge|null $acsoi

     * @throws Throwable
     */
    public function delete(array|SalesOrderItemAllowanceCharge|null $acsoi): void
    {
        $this->entityWriter->delete([$acsoi]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * All item allowances or charges for this invoice
     * @param string $sales_order_id
     * @return EntityReader
     */
    public function repoACSOquery(string $sales_order_id): EntityReader
    {
        $query = $this->select()
                      ->where(['sales_order_id' => $sales_order_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return SalesOrderItemAllowanceCharge|null
     */
    public function repoSalesOrderItemAllowanceChargequery(string $id):
        ?SalesOrderItemAllowanceCharge
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id): int
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }

    /**
     * @param string $sales_order_id
     * @return int
     */
    public function repoSalesOrderCount(string $sales_order_id): int
    {
        $query = $this->select()
                      ->where(['sales_order_id' => $sales_order_id]);
        return $query->count();
    }

    /**
     * @param string $sales_order_item_id
     * @return int
     */
    public function repoSalesOrderItemCount(string $sales_order_item_id): int
    {
        $query = $this->select()
                      ->where(['sales_order_item_id' => $sales_order_item_id]);
        return $query->count();
    }

    /**
     * All allowances and charges for this sales order item
     * @param string $sales_order_item_id
     * @return EntityReader
     */
    public function repoSalesOrderItemquery(string $sales_order_item_id): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['sales_order_item_id' => $sales_order_item_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * Used in SalesOrderController function multiplecopy
     * @return SalesOrderItemAllowanceCharge|null
     *
     * @psalm-return TEntity|null
     */
    public function repoSalesOrderItemOriginalquery(string $sales_order_item_id):
        ?SalesOrderItemAllowanceCharge
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['sales_order_item_id' => $sales_order_item_id]);
        return  $query->fetchOne() ?: null;
    }
}
