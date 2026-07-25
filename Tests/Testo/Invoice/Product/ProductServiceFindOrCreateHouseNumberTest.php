<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Product;

use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Enum\ProductType;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Product\ProductService;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Covers ProductService::findOrCreateHouseNumberProduct() — the HomeCare
 * signup flow's house-number-to-Product resolution: reuse an existing
 * Service-type Product for that address under the street's Family, or
 * create one at the submitted price. An existing Product's price is never
 * overwritten by a later signup at the same address.
 */
#[Test]
final class ProductServiceFindOrCreateHouseNumberTest
{
    /** @param list<Product> $items */
    private function readerYielding(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        /** @var \Mockery\Expectation $e */
        $e = $reader->shouldReceive('getIterator');
        $e->andReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    public function existingProductIsReusedAndNotCreated(): void
    {
        $existing = m::mock(Product::class);

        /** @var ProductRepository&m\MockInterface $productR */
        $productR = m::mock(ProductRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $productR->expects('repoProductWithFamilyIdQuery');
        $e->once()->with('12', 5)->andReturn($this->readerYielding([$existing]));
        $productR->shouldNotReceive('save');

        $fR = m::mock(FamilyRepository::class);
        $trR = m::mock(TaxRateRepository::class);
        $unR = m::mock(UnitRepository::class);

        $service = new ProductService($productR, $fR, $trR, $unR);

        Assert::same(
            $existing,
            $service->findOrCreateHouseNumberProduct(5, '12', 25.00, 1, 1)
        );
    }

    public function noMatchCreatesServiceTypeProductAtGivenPrice(): void
    {
        /** @var ProductRepository&m\MockInterface $productR */
        $productR = m::mock(ProductRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $productR->expects('repoProductWithFamilyIdQuery');
        $e->once()->with('14', 5)->andReturn($this->readerYielding([]));
        /** @var \Mockery\Expectation $e2 */
        $e2 = $productR->expects('save');
        $e2->once();

        /** @var FamilyRepository&m\MockInterface $fR */
        $fR = m::mock(FamilyRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $fR->shouldReceive('repoFamilyquery');
        $e3->andReturn(m::mock(Family::class));

        /** @var TaxRateRepository&m\MockInterface $trR */
        $trR = m::mock(TaxRateRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $trR->shouldReceive('repoTaxRatequery');
        $e4->andReturn(m::mock(TaxRate::class));

        /** @var UnitRepository&m\MockInterface $unR */
        $unR = m::mock(UnitRepository::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $unR->shouldReceive('repoUnitquery');
        $e5->andReturn(m::mock(Unit::class));

        $service = new ProductService($productR, $fR, $trR, $unR);
        $product = $service->findOrCreateHouseNumberProduct(5, '14', 35.00, 2, 3);

        Assert::same('14', $product->getProductName());
        Assert::same(ProductType::Service->value, $product->getProductType());
        Assert::same(35.00, $product->getProductPrice());
    }
}
