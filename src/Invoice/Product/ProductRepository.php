<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Product
 *
 * @extends Select\Repository<TEntity>
 */
final class ProductRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get products without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit');

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
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }

    public function withFiltering(?string $product_sku): EntityReader
    {
        if (null !== $product_sku) {
            return (new EntityReader($this->select))
                ->withFilter($this->getFilter($product_sku));
        }

        return $this->prepareDataReader($this->select());
    }

    private function getFilter(string $product_sku): All
    {
        return new All(
            new Like('product_sku', $product_sku),
        );
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|Product|null $product): void
    {
        $this->entityWriter->write([$product]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|Product|null $product): void
    {
        $this->entityWriter->delete([$product]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'product_description'])
                ->withOrder([
                    'id'                  => 'desc',
                    'product_description' => 'desc',
                ]),
        );
    }

    public function filter_family_id(string $family_id): EntityReader
    {
        $select = $this->select();
        $query  = $select->where(['family_id' => ltrim(rtrim($family_id))]);

        return $this->prepareDataReader($query);
    }

    public function filter_product_sku(string $product_sku): EntityReader
    {
        $select = $this->select();
        $query  = $select->where(['product_sku' => ltrim(rtrim($product_sku))]);

        return $this->prepareDataReader($query);
    }

    public function filter_product_price(string $product_price): EntityReader
    {
        $select = $this->select();
        $query  = $select->where(['product_price' => ltrim(rtrim($product_price))]);

        return $this->prepareDataReader($query);
    }

    public function filter_product_sku_price(string $product_price, string $product_sku): EntityReader
    {
        $select = $this->select();
        $query  = $select->where(['product_price' => ltrim(rtrim($product_price))])
            ->andWhere(['product_sku' => ltrim(rtrim($product_sku))]);

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoProductquery(?string $product_id): ?Product
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit')
            ->where(['id' => $product_id]);

        return $query->fetchOne() ?: null;
    }

    public function withName(string $product_name): ?Product
    {
        $query = $this
            ->select()
            ->where(['product_name' => $product_name]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Get products with filter using views/invoice/product/modal_product_lookups_inv or ...quote.
     *
     * @psalm-return EntityReader
     */
    public function repoProductwithfamilyquery(string $product_name, string $family_id): EntityReader
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit');

        // lookup without filters eg. product/lookup
        if (empty($product_name) && (empty($family_id))) {
        }

        // eg. product/lookup?fp=Cleaning%20Services
        if ((!empty($product_name)) && (empty($family_id))) {
            $query = $query->where(['product_name' => ltrim(rtrim($product_name))]);
        }

        // eg. product/lookup?Cleaning%20Services&ff=4
        if (!empty($product_name) && ($family_id > (string) 0)) {
            $query = $query->where(['family_id' => $family_id])->andWhere(['product_name' => ltrim(rtrim($product_name))]);
        }

        // eg. product/lookup?ff=4
        if (empty($product_name) && ($family_id > (string) 0)) {
            $query = $query->where(['family_id' => $family_id]);
        }

        return $this->prepareDataReader($query);
    }

    /**
     * Get selection of products from all products.
     */
    public function findinProducts(array $product_ids): EntityReader
    {
        $query = $this
            ->select()
            ->where(['id' => ['in' => new Parameter($product_ids)]]);

        return $this->prepareDataReader($query);
    }

    public function repoCount(string $product_id): int
    {
        return $this->select()
            ->where(['id' => $product_id])
            ->count();
    }

    public function repoTestDataCount(): int
    {
        return $this->select()
            ->count();
    }
}
