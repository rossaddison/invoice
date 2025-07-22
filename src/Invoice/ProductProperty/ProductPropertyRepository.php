<?php

declare(strict_types=1);

namespace App\Invoice\ProductProperty;

use App\Invoice\Entity\ProductProperty;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of ProductProperty
 *
 * @extends Select\Repository<TEntity>
 */
final class ProductPropertyRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get productpropertys  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('product');

        return $this->prepareDataReader($query);
    }

    public function findAllProduct(string $product_id): EntityReader
    {
        $query = $this->select()
            ->where(['product_id' => $product_id]);

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
     * @psalm-param TEntity $productproperty
     *
     * @throws \Throwable
     */
    public function save(array|ProductProperty|null $productproperty): void
    {
        $this->entityWriter->write([$productproperty]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|ProductProperty|null $productproperty): void
    {
        $this->entityWriter->delete([$productproperty]);
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
    public function repoProductPropertyLoadedquery(string $id): ?ProductProperty
    {
        $query = $this->select()
            ->load('product')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }
}
