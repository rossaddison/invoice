<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Product;

use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Enum\ProductType;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Product\ProductNameTypeRepository;
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
    /**
     * @return Family&m\MockInterface
     */
    private function makeFamilyMock(): Family
    {
        /** @var Family&m\MockInterface $mock */
        $mock = m::mock(Family::class);
        return $mock;
    }

    /**
     * @return TaxRate&m\MockInterface
     */
    private function makeTaxRateMock(): TaxRate
    {
        /** @var TaxRate&m\MockInterface $mock */
        $mock = m::mock(TaxRate::class);
        return $mock;
    }

    /**
     * @return Unit&m\MockInterface
     */
    private function makeUnitMock(): Unit
    {
        /** @var Unit&m\MockInterface $mock */
        $mock = m::mock(Unit::class);
        return $mock;
    }
    /** @param list<Product> $items */
    private function readerYielding(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $e = $reader->shouldReceive('getIterator');
        $e->andReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    public function existingProductIsReusedAndNotCreated(): void
    {
        /** @var Product&m\MockInterface $existing */
        $existing = m::mock(Product::class);

        /** @var ProductRepository&m\MockInterface $productR */
        $productR = m::mock(ProductRepository::class);
        $e = $productR->expects('repoProductWithFamilyIdQuery');
        $e->once()->with('12', 5)->andReturn($this->readerYielding([$existing]));
        $productR->shouldNotReceive('save');

        /** @var ProductNameTypeRepository&m\MockInterface $nameTypeR */
        $nameTypeR = m::mock(ProductNameTypeRepository::class);
        /** @var FamilyRepository&m\MockInterface $fR */
        $fR = m::mock(FamilyRepository::class);
        /** @var TaxRateRepository&m\MockInterface $trR */
        $trR = m::mock(TaxRateRepository::class);
        /** @var UnitRepository&m\MockInterface $unR */
        $unR = m::mock(UnitRepository::class);

        $service = new ProductService($productR, $nameTypeR, $fR, $trR, $unR);

        Assert::same(
            $existing,
            $service->findOrCreateHouseNumberProduct(5, '12', 25.00, 1, 1)
        );
    }

    public function noMatchCreatesServiceTypeProductAtGivenPrice(): void
    {
        /** @var ProductRepository&m\MockInterface $productR */
        $productR = m::mock(ProductRepository::class);
        $e = $productR->expects('repoProductWithFamilyIdQuery');
        $e->once()->with('14', 5)->andReturn($this->readerYielding([]));
        $e2 = $productR->expects('save');
        $e2->once();

        /** @var FamilyRepository&m\MockInterface $fR */
        $fR = m::mock(FamilyRepository::class);
        $e3 = $fR->shouldReceive('repoFamilyquery');
        $e3->andReturn($this->makeFamilyMock());

        /** @var TaxRateRepository&m\MockInterface $trR */
        $trR = m::mock(TaxRateRepository::class);
        $e4 = $trR->shouldReceive('repoTaxRatequery');
        $e4->andReturn($this->makeTaxRateMock());

        /** @var UnitRepository&m\MockInterface $unR */
        $unR = m::mock(UnitRepository::class);
        $e5 = $unR->shouldReceive('repoUnitquery');
        $e5->andReturn($this->makeUnitMock());

        /** @var ProductNameTypeRepository&m\MockInterface $nameTypeR */
        $nameTypeR = m::mock(ProductNameTypeRepository::class);
        $service = new ProductService($productR, $nameTypeR, $fR, $trR, $unR);
        $product = $service->findOrCreateHouseNumberProduct(5, '14', 35.00, 2, 3);

        Assert::same('14', $product->getProductName());
        Assert::same(ProductType::Service->value, $product->getProductType());
        Assert::same(35.00, $product->getProductPrice());
    }

    public function deleteProductCallsRepositoryDelete(): void
    {
        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);

        /** @var ProductRepository&m\MockInterface $productR */
        $productR = m::mock(ProductRepository::class);
        $e = $productR->expects('delete');
        $e->once()->with($product);

        /** @var ProductNameTypeRepository&m\MockInterface $nameTypeR */
        $nameTypeR = m::mock(ProductNameTypeRepository::class);
        /** @var FamilyRepository&m\MockInterface $fR */
        $fR = m::mock(FamilyRepository::class);
        /** @var TaxRateRepository&m\MockInterface $trR */
        $trR = m::mock(TaxRateRepository::class);
        /** @var UnitRepository&m\MockInterface $unR */
        $unR = m::mock(UnitRepository::class);

        $service = new ProductService($productR, $nameTypeR, $fR, $trR, $unR);
        $service->deleteProduct($product);
    }
}
