<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Product\Trait\ProductLegacyFilterTrait;
use App\Invoice\Product\Trait\ProductWebshopQueryTrait;
use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Product
 * @extends Select\Repository<TEntity>
 */
final class ProductRepository extends Select\Repository implements ProductRepositoryInterface
{
    use ProductLegacyFilterTrait;
    use ProductWebshopQueryTrait;

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
     * Get products without filter
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
     * Get products with non-zero prices for invoice/quote selection
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloadedWithPrice(): EntityReader
    {
        $query = $this->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit')
            ->where(['product_price' => ['>' => 0]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort(Sort::only(['id'])->withOrder(['id' => 'desc']));
    }

    public function withFiltering(?string $product_sku): EntityReader
    {
        if (null !== $product_sku) {
            return (new EntityReader($this->select))
                ->withFilter($this->getFilter($product_sku));
        }
        return $this->prepareDataReader($this->select());
    }

    private function getFilter(string $product_sku): AndX
    {
        return new AndX(
            new Like('product_sku', $product_sku),
        );
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Product|null $product
     * @throws Throwable
     */
    public function save(array|Product|null $product): void
    {
        $this->entityWriter->write([$product]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Product|null $product
     * @throws Throwable
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
                    'id' => 'desc',
                    'product_description' => 'desc',
                ]),
        );
    }

    /**
     * Combines every product/index filter into a single Cycle query — the
     * same fix already applied to inv/index and quote/index (see
     * project_inv_index_filter_combining_fix memory). Before this,
     * ProductController::index() reassigned $products to whatever
     * filterFamilyId()/filterProductSku()/filterProductPrice()/
     * filterProductSkuPrice() returned, and each of those calls
     * $this->select() to start a completely fresh query — so filtering by
     * family_id and then sku (or price) silently discarded the family_id
     * condition. The existing filterProductSkuPrice() two-way special case
     * was the same tell it was for Inv and Quote: someone had already
     * half-noticed. filterFamilyId()/filterProductSku()/filterProductPrice()/
     * filterProductSkuPrice() are left in place (still exercised directly by
     * their own callers if any exist today) rather than removed, matching
     * the same non-destructive precedent as InvFilterTrait/QuoteFilterTrait.
     *
     * Also fixes a byproduct of the same bug: none of those individual
     * filterXxx() methods preloaded family/tax_rate/unit the way
     * findAllPreloaded() (the old no-filter path) did, so applying any
     * filter silently dropped eager-loading too. This always preloads them.
     *
     * @param array<array-key, mixed> $queryParams
     */
    public function filterCombined(array $queryParams): EntityReader
    {
        $query = $this->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit');
        if (!empty($queryParams['family_id'])) {
            $query = $query->andWhere(['family_id' => (int) $queryParams['family_id']]);
        }
        if (!empty($queryParams['product_sku'])) {
            $query = $query->andWhere(['product_sku' => trim((string) $queryParams['product_sku'])]);
        }
        if (!empty($queryParams['product_price'])) {
            $query = $query->andWhere(['product_price' => trim((string) $queryParams['product_price'])]);
        }
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $product_id
     *
     * @return Product|null
     *
     * @psalm-return TEntity|null
     */
    #[\Override]
    public function repoProductquery(int $product_id): ?Product
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit')
            ->where(['id' => $product_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $product_name
     * @return Product|null
     */
    public function withName(string $product_name): ?Product
    {
        $query = $this
            ->select()
            ->where(['product_name' => $product_name]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * Assist in checking for existing products when generating from family
     * @psalm-return EntityReader
     */
    public function repoProductWithFamilyIdQuery(
        string $product_name, int $family_id): EntityReader
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit');

        if (!empty($product_name) && ($family_id > 0)) {
            $query = $query
                    ->andWhere(['family_id' => $family_id])
                    ->andWhere(['product_name' => ltrim(rtrim($product_name))]);
        }

        return $this->prepareDataReader($query);
    }

    /**
     * Get products with filter using views/invoice/product/modal_product_lookups_inv or ...quote
     * Excludes zero-valued products for invoice/quote selection
     *
     * @psalm-return EntityReader
     */
    public function repoProductwithfamilyquery(string $product_name, string $family_id): EntityReader
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit')
            ->where(['product_price' => ['>' => 0]]);

        $family_id_int = (int) $family_id;

        if (!empty($product_name) && empty($family_id)) {
            $query = $query->andWhere(['product_name' => ltrim(rtrim($product_name))]);
        }

        if (!empty($product_name) && $family_id_int > 0) {
            $query = $query->andWhere(['family_id' => $family_id_int])
                           ->andWhere(['product_name' => ltrim(rtrim($product_name))]);
        }

        if (empty($product_name) && $family_id_int > 0) {
            $query = $query->andWhere(['family_id' => $family_id_int]);
        }

        return $this->prepareDataReader($query);
    }

    /**
     * Get selection of products from all products
     *
     * @param array $product_ids
     * @return EntityReader
     */
    public function findinProducts(array $product_ids): EntityReader
    {
        $query = $this
        ->select()
        ->where(['id' => ['in' => new Parameter($product_ids)]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $product_id
     * @return int
     */
    public function repoCount(int $product_id): int
    {
        return $this->select()
                      ->where(['id' => $product_id])
                      ->count();
    }

    /**
     * @return int
     */
    public function repoTestDataCount(): int
    {
        return $this->select()
                      ->count();
    }
}
