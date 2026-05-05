<?php

declare(strict_types=1);

namespace App\Invoice\ProductCustom;

use App\Infrastructure\Persistence\ProductCustom\ProductCustom;
use Cycle\ORM\Select;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of ProductCustom
 * @extends Select\Repository<TEntity>
 */
final class ProductCustomRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     *
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get productcustoms  without filter
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

    /**
     * @param array|ProductCustom|null $productcustom
     */
    public function save(array|ProductCustom|null $productcustom): void
    {
        $this->entityWriter->write([$productcustom]);
    }

    /**
     * @param array|ProductCustom|null $productcustom
     */
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

    /**
     * @param int $id
     * @return ProductCustom|null
     */
    public function repoProductCustomquery(int $id): ?ProductCustom
    {
        $query = $this->select()->load('custom_field')
                                ->load('product')
                                ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $product_id
     * @param int $custom_field_id
     * @return ProductCustom|null
     */
    public function repoFormValuequery(int $product_id, int $custom_field_id): ?ProductCustom
    {
        $query = $this->select()->where(['product_id' => $product_id])
                                ->andWhere(['custom_field_id' => $custom_field_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $product_id
     * @param int $custom_field_id
     * @return int
     */
    public function repoProductCustomCount(int $product_id, int $custom_field_id): int
    {
        $query = $this->select()->where(['product_id' => $product_id])
                                ->andWhere(['custom_field_id' => $custom_field_id]);
        return $query->count();
    }

    /**
     * @param int $product_id
     * @return int
     */
    public function repoProductCount(int $product_id): int
    {
        $query = $this->select()->where(['product_id' => $product_id]);
        return $query->count();
    }

    /**
     * Get all fields that have been setup for a particular inv
     *
     * @psalm-return EntityReader
     */
    public function repoFields(int $product_id): EntityReader
    {
        $query = $this->select()->where(['product_id' => $product_id]);
        return $this->prepareDataReader($query);
    }
}
