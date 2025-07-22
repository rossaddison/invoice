<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of SalesOrderCustom
 *
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderCustomRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get client sales order customs  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('custom_field')
            ->load('quote');

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
    public function save(array|SalesOrderCustom|null $quotecustom): void
    {
        $this->entityWriter->write([$quotecustom]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|SalesOrderCustom|null $so_custom): void
    {
        $this->entityWriter->delete([$so_custom]);
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
            ->load('customsalesorder')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoFormValuequery(string $so_id, string $custom_field_id): ?SalesOrderCustom
    {
        $query = $this->select()
            ->where(['so_id' => $so_id])
            ->andWhere(['custom_field_id' => $custom_field_id]);

        return $query->fetchOne();
    }

    public function repoSalesOrderCustomCount(string $so_id, string $custom_field_id): int
    {
        $query = $this->select()
            ->where(['so_id' => $so_id])
            ->andWhere(['custom_field_id' => $custom_field_id]);

        return $query->count();
    }

    public function repoSalesOrderCount(string $so_id): int
    {
        $query = $this->select()
            ->where(['so_id' => $so_id]);

        return $query->count();
    }

    /**
     * Get all fields that have been setup for a particular sales order.
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $so_id): EntityReader
    {
        $query = $this->select()
            ->where(['so_id' => $so_id]);

        return $this->prepareDataReader($query);
    }
}
