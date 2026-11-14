<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\CategoryPrimary\CategoryPrimaryService;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Enum\BusinessType;
use App\Invoice\Enum\ServiceType;
use App\Invoice\Family\FamilyService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\WindowCleaning\CompanySeoDescription;
use App\Invoice\WindowCleaning\ServiceProducts;
use App\Invoice\WindowCleaning\WindowCleaningPreset;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * The window-cleaning preset: the settings it writes, and the placeholder areas, runs and streets it
 * creates only when the install has no run yet, and the default service it asks for last.
 */
#[Test]
final class WindowCleaningPresetTest
{
    /** @var array<string, string> setting key => value, as saved */
    private array $saved = [];

    /** @var list<string> the names of the areas created */
    private array $areas = [];

    /** @var list<array{category_primary_id: string, name: string}> the runs created */
    private array $runs = [];

    /** @var list<array{string, int}> the streets created: name and the id of the run they are under */
    private array $streets = [];

    /** @var list<string> what happened, in order */
    private array $order = [];

    /** @var list<ServiceType> the services the products were asked for */
    private array $ensured = [];

    /** @var array{0?: list<ServiceType>, 1?: list<ServiceType>} the services now and before, as the SEO description was asked */
    private array $seoFilledWith = [];

    /**
     * @param array<array-key, string> $existingRuns the runs the install already has (id => name)
     */
    private function preset(
        array $existingRuns,
        bool $productEnsured = true,
        bool $seoWritten = true,
        string $previousServices = '',
    ): WindowCleaningPreset {
        $this->seoFilledWith = [];
        $this->saved = [];
        $this->areas = [];
        $this->runs = [];
        $this->streets = [];
        $this->order = [];
        $this->ensured = [];

        /** @var SettingRepository<Setting>&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->andReturn(null);
        $sR->shouldReceive('save')->andReturnUsing(function (Setting $setting): void {
            $this->saved[$setting->getSettingKey()] = $setting->getSettingValue();
        });
        $sR->shouldReceive('setSetting');
        $sR->shouldReceive('getSetting')->with('services_offered')->andReturn($previousServices);

        /** @var CategorySecondaryRepository<CategorySecondary>&m\MockInterface $csR */
        $csR = m::mock(CategorySecondaryRepository::class);
        $csR->shouldReceive('optionsDataCategorySecondaries')->andReturn($existingRuns);

        /** @var CategoryPrimaryService&m\MockInterface $cpS */
        $cpS = m::mock(CategoryPrimaryService::class);
        $cpS->shouldReceive('saveCategoryPrimary')->andReturnUsing(function (CategoryPrimary $area, array $array): void {
            $this->areas[] = (string) $array['name'];
            $area->setId(count($this->areas));
        });
        /** @var CategorySecondaryService&m\MockInterface $csS */
        $csS = m::mock(CategorySecondaryService::class);
        $csS->shouldReceive('saveCategorySecondary')->andReturnUsing(function (CategorySecondary $run, array $array): void {
            /** @var array{category_primary_id: string, name: string} $array */
            $this->runs[] = $array;
            $run->setId(count($this->runs));
        });
        /** @var FamilyService&m\MockInterface $fS */
        $fS = m::mock(FamilyService::class);
        $fS->shouldReceive('findOrCreateByStreetName')->andReturnUsing(function (string $name, int $runId): Family {
            $this->streets[] = [$name, $runId];
            $this->order[] = 'street';
            return new Family();
        });
        /** @var ServiceProducts&m\MockInterface $serviceProducts */
        $serviceProducts = m::mock(ServiceProducts::class);
        $serviceProducts->shouldReceive('ensure')->andReturnUsing(function (array $services) use ($productEnsured): bool {
            $this->order[] = 'product';
            /** @var list<ServiceType> $services */
            $this->ensured = $services;
            return $productEnsured;
        });

        /** @var CompanySeoDescription&m\MockInterface $companySeo */
        $companySeo = m::mock(CompanySeoDescription::class);
        $companySeo->shouldReceive('fill')->andReturnUsing(
            /**
             * @param list<ServiceType> $services
             * @param list<ServiceType> $previous
             */
            function (array $services, array $previous) use ($seoWritten): bool {
                $this->seoFilledWith = [$services, $previous];
                return $seoWritten;
            },
        );

        return new WindowCleaningPreset($sR, $cpS, $csR, $csS, $fS, $serviceProducts, $companySeo);
    }

    public function itFillsTheCompanySeoDescriptionWithTheServicesAndTheOnesOfferedBefore(): void
    {
        $done = $this->preset([], previousServices: 'window_cleaning')
            ->apply(1, [ServiceType::WindowCleaning, ServiceType::BinCleaning]);

        Assert::true($done['seo']);
        Assert::same($this->seoFilledWith, [
            [ServiceType::WindowCleaning, ServiceType::BinCleaning],
            [ServiceType::WindowCleaning],
        ]);
    }

    public function itSaysWhenTheCompanySeoDescriptionWasLeftAlone(): void
    {
        $done = $this->preset([], seoWritten: false)->apply(1);

        Assert::false($done['seo']);
    }

    public function itSavesTheServicesOfferedAndAsksForTheirProducts(): void
    {
        $this->preset([])->apply(1, [ServiceType::BinCleaning, ServiceType::GrassMowing]);

        Assert::same($this->saved[ServiceType::SETTING_KEY], 'bin_cleaning,grass_mowing');
        Assert::same($this->ensured, [ServiceType::BinCleaning, ServiceType::GrassMowing]);
    }

    public function noServicesGivenMeansWindowCleaning(): void
    {
        $this->preset([])->apply();

        Assert::same($this->saved[ServiceType::SETTING_KEY], 'window_cleaning');
        Assert::same($this->ensured, [ServiceType::WindowCleaning]);
    }

    public function itSetsTheBusinessTypeAndHidesTheFourFrontPageLinks(): void
    {
        $this->preset([])->apply();

        Assert::same($this->saved[BusinessType::SETTING_KEY], 'window_cleaning');
        foreach (['no_front_webshop_page', 'no_front_gateway_status_page', 'no_front_peppol_status_page', 'no_front_hmrc_api_status_page'] as $key) {
            Assert::same($this->saved[$key], '1');
        }
        Assert::count($this->saved, 8);
    }

    public function itSwitchesOnHomeCareQrAutoInvoice(): void
    {
        $this->preset([])->apply();

        Assert::same($this->saved['homecare_auto_invoice_enabled'], '1');
    }

    public function itClosesTheGeneralSignUp(): void
    {
        $this->preset([])->apply();

        Assert::same($this->saved['stop_signing_up'], '1');
    }

    public function itLeavesHomeCareSignUpLoginAndTheBusinessOwnedHomeCareSettingsAlone(): void
    {
        $this->preset([])->apply();

        $untouched = [
            'stop_homecare_signing_up', 'stop_logging_in',
            'homecare_lead_worker_id', 'homecare_current_run_category_secondary_id',
            'homecare_current_run_last_run_date', 'homecare_vision_api_key',
            'homecare_hidden_inv_columns', 'homecare_hidden_inv_guest_columns',
        ];
        foreach ($untouched as $key) {
            Assert::false(array_key_exists($key, $this->saved));
        }
    }

    public function itCreatesSixteenAreasEachWithOneRunWhenThereIsNoRunYet(): void
    {
        $done = $this->preset([])->apply();

        Assert::same($done, ['settings' => 8, 'areas' => 16, 'runs' => 16, 'streets' => 480, 'product' => true, 'seo' => true]);
        Assert::count($this->areas, 16);
        Assert::count($this->runs, 16);
        Assert::same($this->areas[0], 'Example Area 01');
        Assert::same($this->runs[0], ['category_primary_id' => '1', 'name' => 'Example Run 01']);
        Assert::same($this->runs[15], ['category_primary_id' => '16', 'name' => 'Example Run 16']);
    }

    public function itCreatesThirtyStreetsUnderEachRun(): void
    {
        $this->preset([])->apply();

        Assert::count($this->streets, 480);
        Assert::same($this->streets[0], ['Street 1 on Run 1', 1]);
        Assert::same($this->streets[29], ['Street 30 on Run 1', 1]);
        Assert::same($this->streets[30], ['Street 1 on Run 2', 2]);
        Assert::same($this->streets[479], ['Street 30 on Run 16', 16]);
    }

    public function itAddsNothingToAnInstallThatAlreadyHasARun(): void
    {
        $done = $this->preset([7 => 'A real run'])->apply();

        Assert::same($done, ['settings' => 8, 'areas' => 0, 'runs' => 0, 'streets' => 0, 'product' => true, 'seo' => true]);
        Assert::count($this->areas, 0);
        Assert::count($this->runs, 0);
        Assert::count($this->streets, 0);
        // the settings are still applied
        Assert::same($this->saved[BusinessType::SETTING_KEY], 'window_cleaning');
    }

    public function theNumberOfRunsCanBeChosen(): void
    {
        $done = $this->preset([])->apply(4);

        Assert::same($done['runs'], 4);
        Assert::same($done['streets'], 120);
        Assert::count($this->runs, 4);
    }

    public function theDefaultServiceIsAskedForAfterTheStreetsExist(): void
    {
        $this->preset([])->apply(1);

        Assert::same(end($this->order), 'product');
        Assert::same(reset($this->order), 'street');
    }

    public function itSaysWhenTheDefaultServiceCouldNotBeAdded(): void
    {
        $done = $this->preset([], false)->apply();

        Assert::false($done['product']);
    }
}
