<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderCustom
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderCustomRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select,
        private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get client sales order customs  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('custom_field')
                      ->load('sales_order');
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
     * @param array|SalesOrderCustom|null $salesordercustom
     * @throws Throwable
     */
    public function save(array|SalesOrderCustom|null $salesordercustom): void
    {
        $this->entityWriter->write([$salesordercustom]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderCustom|null $sales_order_custom
     * @throws Throwable
     */
    public function delete(array|SalesOrderCustom|null $sales_order_custom): void
    {
        $this->entityWriter->delete([$sales_order_custom]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoSalesOrderCustomquery(string $id): ?SalesOrderCustom
    {
        $query = $this->select()
                      ->load('custom_field')
                      ->load('sales_order')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    public function repoFormValuequery(string $sales_order_id,
            string $custom_field_id): ?SalesOrderCustom
    {
        $query = $this->select()
                      ->where(['sales_order_id' => $sales_order_id])
                      ->andWhere(['custom_field_id' => $custom_field_id]);
        return  $query->fetchOne();
    }

    public function repoSalesOrderCustomCount(string $sales_order_id,
            string $custom_field_id): int
    {
        $query = $this->select()
                      ->where(['sales_order_id' => $sales_order_id])
                      ->andWhere(['custom_field_id' => $custom_field_id]);
        return $query->count();
    }

    public function repoSalesOrderCount(string $sales_order_id): int
    {
        $query = $this->select()
                      ->where(['sales_order_id' => $sales_order_id]);
        return $query->count();
    }

    /**
     * Get all fields that have been setup for a particular sales order
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $sales_order_id): EntityReader
    {
        $query = $this->select()
                      ->where(['sales_order_id' => $sales_order_id]);
        return $this->prepareDataReader($query);
    }
}
