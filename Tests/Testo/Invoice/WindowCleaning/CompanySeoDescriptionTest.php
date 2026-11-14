<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\Company\Company;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Enum\ServiceType;
use App\Invoice\WindowCleaning\CompanySeoDescription;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * The company's SEO description names the services the business offers, but never replaces wording the
 * business wrote itself.
 */
#[Test]
final class CompanySeoDescriptionTest
{
    /** @var list<Company> the companies saved */
    private array $saved = [];

    private function service(?Company $company): CompanySeoDescription
    {
        $this->saved = [];

        /** @var CompanyRepository<Company>&m\MockInterface $cR */
        $cR = m::mock(CompanyRepository::class);
        $cR->shouldReceive('repoCompanyActivequery')->andReturn($company);
        $cR->shouldReceive('save')->andReturnUsing(function (Company $saved): void {
            $this->saved[] = $saved;
        });

        return new CompanySeoDescription($cR);
    }

    public function oneServiceIsJustItsName(): void
    {
        Assert::same(CompanySeoDescription::describe([ServiceType::WindowCleaning]), 'Window Cleaning');
    }

    public function twoServicesAreJoinedWithAnd(): void
    {
        Assert::same(
            CompanySeoDescription::describe([ServiceType::WindowCleaning, ServiceType::BinCleaning]),
            'Window Cleaning and Bin Cleaning',
        );
    }

    public function severalServicesAreCommaSeparatedWithAndBeforeTheLast(): void
    {
        Assert::same(
            CompanySeoDescription::describe([ServiceType::WindowCleaning, ServiceType::BinCleaning, ServiceType::HedgeCutting]),
            'Window Cleaning, Bin Cleaning and Hedge Cutting',
        );
    }

    public function noServicesIsEmpty(): void
    {
        Assert::same(CompanySeoDescription::describe([]), '');
    }

    public function anEmptyDescriptionIsFilled(): void
    {
        $company = new Company(seo_description: '');

        Assert::true($this->service($company)->fill([ServiceType::WindowCleaning, ServiceType::GrassMowing], []));
        Assert::same($company->getSeoDescription(), 'Window Cleaning and Grass Mowing');
        Assert::same($this->saved, [$company]);
    }

    public function aMissingDescriptionIsFilledToo(): void
    {
        $company = new Company(seo_description: null);

        Assert::true($this->service($company)->fill([ServiceType::TreeTrimming], []));
        Assert::same($company->getSeoDescription(), 'Tree Trimming');
    }

    public function theTextWrittenForTheServicesOfferedBeforeIsUpdated(): void
    {
        $company = new Company(seo_description: 'Window Cleaning');

        Assert::true($this->service($company)->fill(
            [ServiceType::WindowCleaning, ServiceType::HousePainting],
            [ServiceType::WindowCleaning],
        ));
        Assert::same($company->getSeoDescription(), 'Window Cleaning and House Painting');
    }

    public function theBusinessesOwnWordingIsNeverReplaced(): void
    {
        $company = new Company(seo_description: 'Family run window cleaners since 1998');

        Assert::false($this->service($company)->fill([ServiceType::WindowCleaning, ServiceType::BinCleaning], [ServiceType::WindowCleaning]));
        Assert::same($company->getSeoDescription(), 'Family run window cleaners since 1998');
        Assert::count($this->saved, 0);
    }

    public function nothingHappensWithoutACurrentCompanyOrWithoutServices(): void
    {
        Assert::false($this->service(null)->fill([ServiceType::WindowCleaning], []));
        Assert::false($this->service(new Company(seo_description: ''))->fill([], []));
        Assert::count($this->saved, 0);
    }
}
