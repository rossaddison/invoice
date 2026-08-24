<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Catalog;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as cpR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as csR;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\ProductImage\ProductImageRepository as piR;
use App\Webshop\Catalog\CatalogQueryService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Covers CatalogQueryService — the in-process replacement for the
 * standalone webshop app's `ProductCatalogClient` (a Guzzle client
 * against invoice's own `GET /api/products`; see
 * docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md). This is a near-direct
 * port of the now-decommissioned `App\Api\ProductsController`'s own
 * mapping logic — same non-zero-price filter, same static-file image
 * path convention, now exercised as a plain in-process mapper instead of
 * an HTTP response builder.
 */
#[Test]
final class CatalogQueryServiceTest
{
    /** For a test that never touches category/family mapping (no product has a Family). */
    private function serviceWithoutCategories(pR $pR, piR $piR): CatalogQueryService
    {
        /** @var cpR&m\MockInterface $cpR */
        $cpR = m::mock(cpR::class);
        /** @var csR&m\MockInterface $csR */
        $csR = m::mock(csR::class);

        return new CatalogQueryService($pR, $piR, $cpR, $csR);
    }

    /** @param list<mixed> $rows */
    private function reader(array $rows): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturn((static function () use ($rows) {
            yield from $rows;
        })());
        return $reader;
    }

    /**
     * A fresh EntityReader (backed by a fresh Generator) per call — a
     * Generator can only be traversed once, and `listAll()`'s own
     * `firstImagePath()` lookup runs once per product, so a single
     * shared instance would fail on the second product.
     */
    private function emptyImages(): piR
    {
        /** @var piR&m\MockInterface $piR */
        $piR = m::mock(piR::class);
        $piR->shouldReceive('repoProductImageProductquery')->andReturnUsing(fn (): EntityReader => $this->reader([]));
        return $piR;
    }

    public function findMapsAFullyPopulatedProductWithFamilyAndCategories(): void
    {
        /** @var Family&m\MockInterface $family */
        $family = m::mock(Family::class);
        $family->shouldReceive('getFamilyName')->andReturn('Input Devices');
        $family->shouldReceive('getCategoryPrimaryId')->andReturn(10);
        $family->shouldReceive('getCategorySecondaryId')->andReturn(20);

        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        $product->shouldReceive('reqId')->andReturn(7);
        $product->shouldReceive('getProductSku')->andReturn('WS-MOUSE-01');
        $product->shouldReceive('getProductName')->andReturn('Wireless Mouse');
        $product->shouldReceive('getProductDescription')->andReturn('Ergonomic wireless mouse.');
        $product->shouldReceive('getProductPrice')->andReturn(19.99);
        $product->shouldReceive('getUnit')->andReturn(null);
        $product->shouldReceive('getFamily')->andReturn($family);

        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        $pR->shouldReceive('repoProductquery')->once()->with(7)->andReturn($product);

        /** @var ProductImage&m\MockInterface $image */
        $image = m::mock(ProductImage::class);
        $image->shouldReceive('getFileNameNew')->andReturn('mouse photo.jpg');
        /** @var piR&m\MockInterface $piR */
        $piR = m::mock(piR::class);
        $piR->shouldReceive('repoProductImageProductquery')->once()->with(7)->andReturn($this->reader([$image]));

        /** @var CategoryPrimary&m\MockInterface $categoryPrimary */
        $categoryPrimary = m::mock(CategoryPrimary::class);
        $categoryPrimary->shouldReceive('getName')->andReturn('Computing');
        /** @var cpR&m\MockInterface $cpR */
        $cpR = m::mock(cpR::class);
        $cpR->shouldReceive('repoCategoryPrimaryQuery')->once()->with(10)->andReturn($categoryPrimary);

        /** @var CategorySecondary&m\MockInterface $categorySecondary */
        $categorySecondary = m::mock(CategorySecondary::class);
        $categorySecondary->shouldReceive('getName')->andReturn('Input Devices');
        /** @var csR&m\MockInterface $csR */
        $csR = m::mock(csR::class);
        $csR->shouldReceive('repoCategorySecondaryQuery')->once()->with(20)->andReturn($categorySecondary);

        $listing = new CatalogQueryService($pR, $piR, $cpR, $csR)->find(7);

        Assert::notNull($listing);
        Assert::same(7, $listing->id);
        Assert::same('WS-MOUSE-01', $listing->sku);
        Assert::same('Wireless Mouse', $listing->name);
        Assert::same(19.99, $listing->price);
        Assert::same('Input Devices', $listing->family);
        Assert::same('Computing', $listing->category);
        Assert::same('Input Devices', $listing->subcategory);
        // Same-origin relative path — no base-URL prefixing needed once
        // this runs in-process (see the class's own docblock); the
        // filename is URL-encoded since it may contain spaces.
        Assert::same('/products/mouse%20photo.jpg', $listing->imageUrl);
    }

    public function findReturnsNullForAZeroPricedProductEvenIfItExists(): void
    {
        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        $product->shouldReceive('getProductPrice')->andReturn(0.00);

        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        $pR->shouldReceive('repoProductquery')->once()->with(7)->andReturn($product);

        Assert::null($this->serviceWithoutCategories($pR, $this->emptyImages())->find(7));
    }

    public function findReturnsNullWhenNoProductMatches(): void
    {
        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        $pR->shouldReceive('repoProductquery')->once()->with(999)->andReturn(null);

        Assert::null($this->serviceWithoutCategories($pR, $this->emptyImages())->find(999));
    }

    public function findWithANonPositiveIdNeverQueriesTheRepository(): void
    {
        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        $pR->shouldNotReceive('repoProductquery');

        Assert::null($this->serviceWithoutCategories($pR, $this->emptyImages())->find(0));
    }

    public function findWithNoFamilyLeavesFacetsNull(): void
    {
        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        $product->shouldReceive('reqId')->andReturn(8);
        $product->shouldReceive('getProductSku')->andReturn(null);
        $product->shouldReceive('getProductName')->andReturn('Unfiled Product');
        $product->shouldReceive('getProductDescription')->andReturn(null);
        $product->shouldReceive('getProductPrice')->andReturn(5.00);
        $product->shouldReceive('getUnit')->andReturn(null);
        $product->shouldReceive('getFamily')->andReturn(null);

        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        $pR->shouldReceive('repoProductquery')->once()->with(8)->andReturn($product);

        $listing = $this->serviceWithoutCategories($pR, $this->emptyImages())->find(8);

        Assert::notNull($listing);
        Assert::null($listing->family);
        Assert::null($listing->category);
        Assert::null($listing->subcategory);
        Assert::null($listing->imageUrl);
    }

    public function listAllMapsEveryPricedProductFromTheRepository(): void
    {
        /** @var Product&m\MockInterface $a */
        $a = m::mock(Product::class);
        $a->shouldReceive('reqId')->andReturn(1);
        $a->shouldReceive('getProductSku')->andReturn(null);
        $a->shouldReceive('getProductName')->andReturn('A');
        $a->shouldReceive('getProductDescription')->andReturn(null);
        $a->shouldReceive('getProductPrice')->andReturn(1.00);
        $a->shouldReceive('getUnit')->andReturn(null);
        $a->shouldReceive('getFamily')->andReturn(null);

        /** @var Product&m\MockInterface $b */
        $b = m::mock(Product::class);
        $b->shouldReceive('reqId')->andReturn(2);
        $b->shouldReceive('getProductSku')->andReturn(null);
        $b->shouldReceive('getProductName')->andReturn('B');
        $b->shouldReceive('getProductDescription')->andReturn(null);
        $b->shouldReceive('getProductPrice')->andReturn(2.00);
        $b->shouldReceive('getUnit')->andReturn(null);
        $b->shouldReceive('getFamily')->andReturn(null);

        /** @var pR&m\MockInterface $pR */
        $pR = m::mock(pR::class);
        $pR->shouldReceive('findAllPreloadedWithPrice')->once()->andReturn($this->reader([$a, $b]));

        $listings = $this->serviceWithoutCategories($pR, $this->emptyImages())->listAll();

        Assert::count($listings, 2);
        Assert::same(1, $listings[0]->id);
        Assert::same(2, $listings[1]->id);
    }
}
