<?php

declare(strict_types=1);

namespace App\Invoice\ProductCustom;

use App\Invoice\Entity\ProductCustom;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of ProductCustom
 *
 * @extends Select\Repository<TEntity>
 */
final class ProductCustomRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get productcustoms  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('custom_field')->load('product');

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

    public function save(array|ProductCustom|null $productcustom): void
    {
        $this->entityWriter->write([$productcustom]);
    }

    public function delete(array|ProductCustom|null $productcustom): void
    {
        $this->entityWriter->delete([$productcustom]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoProductCustomquery(string $id): ?ProductCustom
    {
        $query = $this->select()->load('custom_field')
            ->load('product')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoFormValuequery(string $product_id, string $custom_field_id): ?ProductCustom
    {
        $query = $this->select()->where(['product_id' => $product_id])
            ->andWhere(['custom_field_id' => $custom_field_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoProductCustomCount(string $product_id, string $custom_field_id): int
    {
        $query = $this->select()->where(['product_id' => $product_id])
            ->andWhere(['custom_field_id' => $custom_field_id]);

        return $query->count();
    }

    public function repoProductCount(string $product_id): int
    {
        $query = $this->select()->where(['product_id' => $product_id]);

        return $query->count();
    }

    /**
     * Get all fields that have been setup for a particular inv.
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $product_id): EntityReader
    {
        $query = $this->select()->where(['product_id' => $product_id]);

        return $this->prepareDataReader($query);
    }
}
