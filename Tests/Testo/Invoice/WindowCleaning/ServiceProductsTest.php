<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Enum\ServiceType;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Family\FamilyService;
use App\Invoice\Product\ProductNameTypeRepository;
use App\Invoice\Product\ProductService;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use App\Invoice\WindowCleaning\ServiceProducts;
use Generator;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * The service products of a home-services business: the shared "HomeCare Service" product and one for
 * each service offered, added to the catalogue with the first tax rate and unit and any street (a
 * placeholder under the first run when there is none), and never added twice.
 */
#[Test]
final class ServiceProductsTest
{
    /** @var list<array{string, int, int, int}> name, family, tax rate and unit ids of each product made */
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
     * @param list<string> $existingProducts the names of the service products already in the catalogue
     * @param list<Family> $families the streets the install already has
     * @param array<array-key, string> $runs the runs the install already has (id => name)
     */
    private function service(
        array $existingProducts,
        array $families,
        array $runs,
        bool $hasTaxRate = true,
        bool $hasUnit = true,
    ): ServiceProducts {
        $this->made = [];
        $this->streets = [];

        /** @var ProductService&m\MockInterface $productService */
        $productService = m::mock(ProductService::class);
        $productService->shouldReceive('findOrCreateServiceProduct')->andReturnUsing(
            function (string $name, int $familyId, int $taxRateId, int $unitId): Product {
                $this->made[] = [$name, $familyId, $taxRateId, $unitId];
                return new Product();
            },
        );
        /** @var ProductNameTypeRepository<Product>&m\MockInterface $nameType */
        $nameType = m::mock(ProductNameTypeRepository::class);
        $nameType->shouldReceive('repoProductByNameAndTypeQuery')->andReturnUsing(
            static fn (string $name): ?Product => in_array($name, $existingProducts, true) ? new Product() : null,
        );

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

        return new ServiceProducts($productService, $nameType, $fR, $fS, $csR, $trR, $uR);
    }

    public function itDoesNothingWhenEveryProductIsAlreadyInTheCatalogue(): void
    {
        $service = $this->service(['HomeCare Service', 'Bin Cleaning'], [], []);

        Assert::true($service->ensure([ServiceType::BinCleaning]));
        Assert::count($this->made, 0);
    }

    public function itAddsTheHomeCareServiceWhenNoServiceIsGiven(): void
    {
        $service = $this->service([], [$this->family(5)], [1 => 'Run']);

        Assert::true($service->ensure());
        Assert::same($this->made, [['HomeCare Service', 5, 3, 4]]);
    }

    public function itAddsAProductForEachServiceOfferedAndLeavesTheOnesThatExist(): void
    {
        $service = $this->service(['HomeCare Service', 'Hedge Cutting'], [$this->family(5)], [1 => 'Run']);

        Assert::true($service->ensure([ServiceType::WindowCleaning, ServiceType::HedgeCutting, ServiceType::GrassMowing]));
        Assert::same($this->made, [
            ['Window Cleaning', 5, 3, 4],
            ['Grass Mowing', 5, 3, 4],
        ]);
    }

    public function itPutsAPlaceholderStreetUnderTheFirstRunWhenThereIsNoStreet(): void
    {
        $service = $this->service([], [], [8 => 'First run', 9 => 'Second run']);

        Assert::true($service->ensure([ServiceType::BinCleaning]));
        Assert::same($this->streets, [['Example Street', 8]]);
        Assert::same($this->made, [['HomeCare Service', 99, 3, 4], ['Bin Cleaning', 99, 3, 4]]);
    }

    public function itSaysNoWhenThereIsNeitherAStreetNorARun(): void
    {
        $service = $this->service([], [], []);

        Assert::false($service->ensure([ServiceType::BinCleaning]));
        Assert::count($this->made, 0);
    }

    public function itSaysNoWhenThereIsNoTaxRateOrNoUnit(): void
    {
        Assert::false($this->service([], [$this->family(5)], [], hasTaxRate: false)->ensure());
        Assert::false($this->service([], [$this->family(5)], [], hasUnit: false)->ensure());
        Assert::count($this->made, 0);
    }
}
