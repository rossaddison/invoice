<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Enum\BusinessType;
use App\Invoice\Enum\ServiceType;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\WindowCleaning\CompanySeoDescription;
use App\Invoice\WindowCleaning\PlaceholderRuns;
use App\Invoice\WindowCleaning\ServiceProducts;
use App\Invoice\WindowCleaning\WindowCleaningPreset;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * The window-cleaning preset: the settings it writes, the placeholder areas, runs and streets it asks
 * for only when the install has no run yet, the service products it asks for last, and the company SEO
 * description it fills.
 */
#[Test]
final class WindowCleaningPresetTest
{
    /** @var array<string, string> setting key => value, as saved */
    private array $saved = [];

    /** @var list<int> how many runs each call for placeholders asked for */
    private array $placeholdersAskedFor = [];

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
        $this->placeholdersAskedFor = [];
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

        /** @var PlaceholderRuns&m\MockInterface $placeholderRuns */
        $placeholderRuns = m::mock(PlaceholderRuns::class);
        $placeholderRuns->shouldReceive('create')->andReturnUsing(function (int $runs): array {
            $this->placeholdersAskedFor[] = $runs;
            $this->order[] = 'placeholders';
            return ['areas' => $runs, 'runs' => $runs, 'streets' => $runs * PlaceholderRuns::STREETS_PER_RUN];
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

        return new WindowCleaningPreset($sR, $csR, $placeholderRuns, $serviceProducts, $companySeo);
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

    public function itAsksForSixteenRunsWithTheirStreetsWhenThereIsNoRunYet(): void
    {
        $done = $this->preset([])->apply();

        Assert::same($done, ['settings' => 8, 'areas' => 16, 'runs' => 16, 'streets' => 480, 'product' => true, 'seo' => true]);
        Assert::same($this->placeholdersAskedFor, [16]);
    }

    public function itAddsNothingToAnInstallThatAlreadyHasARun(): void
    {
        $done = $this->preset([7 => 'A real run'])->apply();

        Assert::same($done, ['settings' => 8, 'areas' => 0, 'runs' => 0, 'streets' => 0, 'product' => true, 'seo' => true]);
        Assert::same($this->placeholdersAskedFor, []);
        // the settings are still applied
        Assert::same($this->saved[BusinessType::SETTING_KEY], 'window_cleaning');
    }

    public function theNumberOfRunsCanBeChosen(): void
    {
        $done = $this->preset([])->apply(4);

        Assert::same($done['runs'], 4);
        Assert::same($done['streets'], 120);
        Assert::same($this->placeholdersAskedFor, [4]);
    }

    public function theServiceProductsAreAskedForAfterThePlaceholders(): void
    {
        $this->preset([])->apply(1);

        Assert::same($this->order, ['placeholders', 'product']);
    }

    public function itSaysWhenTheServiceProductsCouldNotBeAdded(): void
    {
        $done = $this->preset([], false)->apply();

        Assert::false($done['product']);
    }
}
