<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Family\FamilyService;
use App\Invoice\Product\ProductNameTypeRepository;
use App\Invoice\Product\ProductService;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use App\Invoice\WindowCleaning\WindowCleaningServiceProduct;
use Generator;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * The default window-cleaning service: the shared "HomeCare Service" product, added to the catalogue
 * with the first tax rate and unit and any street (a placeholder under the first run when there is
 * none), and never added twice.
 */
#[Test]
final class WindowCleaningServiceProductTest
{
    /** @var list<array{int, int, int}> family, tax rate and unit ids of each product made */
    private array $made = [];

    /** @var list<array{string, int}> the placeholder streets made: name and run id */
    private array $streets = [];

    private function family(int $id): Family
    {
        $family = new Family();
        $family->setId($id);
        return $family;
    }

    /**
     * @param list<Family> $families the streets the install already has
     * @param array<array-key, string> $runs the runs the install already has (id => name)
     */
    private function service(
        bool $productExists,
        array $families,
        array $runs,
        bool $hasTaxRate = true,
        bool $hasUnit = true,
    ): WindowCleaningServiceProduct {
        $this->made = [];
        $this->streets = [];

        /** @var ProductService&m\MockInterface $productService */
        $productService = m::mock(ProductService::class);
        $productService->shouldReceive('findOrCreateHomeCareServiceProduct')->andReturnUsing(
            function (int $familyId, int $taxRateId, int $unitId): Product {
                $this->made[] = [$familyId, $taxRateId, $unitId];
                return new Product();
            },
        );
        /** @var ProductNameTypeRepository<Product>&m\MockInterface $nameType */
        $nameType = m::mock(ProductNameTypeRepository::class);
        $nameType->shouldReceive('repoProductByNameAndTypeQuery')->andReturn($productExists ? new Product() : null);

        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(static function () use ($families): Generator {
            yield from $families;
        });
        /** @var FamilyRepository<Family>&m\MockInterface $fR */
        $fR = m::mock(FamilyRepository::class);
        $fR->shouldReceive('findAllPreloaded')->andReturn($reader);
        /** @var FamilyService&m\MockInterface $fS */
        $fS = m::mock(FamilyService::class);
        $fS->shouldReceive('findOrCreateByStreetName')->andReturnUsing(function (string $name, int $runId): Family {
            $this->streets[] = [$name, $runId];
            return $this->family(99);
        });
        /** @var CategorySecondaryRepository<CategorySecondary>&m\MockInterface $csR */
        $csR = m::mock(CategorySecondaryRepository::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn($runs);

        $taxRate = new TaxRate();
        $taxRate->setTaxRateId(3);
        $unit = new Unit();
        $unit->setId(4);
        /** @var TaxRateRepository<TaxRate>&m\MockInterface $trR */
        $trR = m::mock(TaxRateRepository::class);
        $trR->shouldReceive('repoFirstByIdQuery')->andReturn($hasTaxRate ? $taxRate : null);
        /** @var UnitRepository<Unit>&m\MockInterface $uR */
        $uR = m::mock(UnitRepository::class);
        $uR->shouldReceive('repoFirstByIdQuery')->andReturn($hasUnit ? $unit : null);

        return new WindowCleaningServiceProduct($productService, $nameType, $fR, $fS, $csR, $trR, $uR);
    }

    public function itDoesNothingWhenTheServiceIsAlreadyInTheCatalogue(): void
    {
        $service = $this->service(true, [], []);

        Assert::true($service->ensure());
        Assert::count($this->made, 0);
    }

    public function itUsesTheFirstTaxRateUnitAndStreet(): void
    {
        $service = $this->service(false, [$this->family(5), $this->family(6)], [1 => 'Run']);

        Assert::true($service->ensure());
        Assert::same($this->made, [[5, 3, 4]]);
        Assert::count($this->streets, 0);
    }

    public function itPutsAPlaceholderStreetUnderTheFirstRunWhenThereIsNoStreet(): void
    {
        $service = $this->service(false, [], [8 => 'First run', 9 => 'Second run']);

        Assert::true($service->ensure());
        Assert::same($this->streets, [['Example Street', 8]]);
        Assert::same($this->made, [[99, 3, 4]]);
    }

    public function itSaysNoWhenThereIsNeitherAStreetNorARun(): void
    {
        $service = $this->service(false, [], []);

        Assert::false($service->ensure());
        Assert::count($this->made, 0);
    }

    public function itSaysNoWhenThereIsNoTaxRateOrNoUnit(): void
    {
        Assert::false($this->service(false, [$this->family(5)], [], hasTaxRate: false)->ensure());
        Assert::false($this->service(false, [$this->family(5)], [], hasUnit: false)->ensure());
        Assert::count($this->made, 0);
    }
}
