<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategoryPrimary\CategoryPrimaryService;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Enum\BusinessType;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\WindowCleaning\WindowCleaningPreset;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * The window-cleaning preset: the settings it writes, and the placeholder areas and runs it creates
 * only when the install has no run yet.
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

    /**
     * @param array<array-key, string> $existingRuns the runs the install already has (id => name)
     */
    private function preset(array $existingRuns): WindowCleaningPreset
    {
        $this->saved = [];
        $this->areas = [];
        $this->runs = [];

        /** @var SettingRepository<Setting>&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->andReturn(null);
        $sR->shouldReceive('save')->andReturnUsing(function (Setting $setting): void {
            $this->saved[$setting->getSettingKey()] = $setting->getSettingValue();
        });
        $sR->shouldReceive('setSetting');

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
        });
        /** @var CategoryPrimaryRepository<CategoryPrimary>&m\MockInterface $cpR */
        $cpR = m::mock(CategoryPrimaryRepository::class);

        return new WindowCleaningPreset($sR, $cpR, $cpS, $csR, $csS);
    }

    public function itSetsTheBusinessTypeAndHidesTheFourFrontPageLinks(): void
    {
        $this->preset([])->apply();

        Assert::same($this->saved[BusinessType::SETTING_KEY], 'window_cleaning');
        foreach (['no_front_webshop_page', 'no_front_gateway_status_page', 'no_front_peppol_status_page', 'no_front_hmrc_api_status_page'] as $key) {
            Assert::same($this->saved[$key], '1');
        }
        Assert::count($this->saved, 5);
    }

    public function itLeavesSignUpHomeCareSignUpAndLoginAlone(): void
    {
        $this->preset([])->apply();

        foreach (['stop_signing_up', 'stop_homecare_signing_up', 'stop_logging_in'] as $key) {
            Assert::false(array_key_exists($key, $this->saved));
        }
    }

    public function itCreatesSixteenAreasEachWithOneRunWhenThereIsNoRunYet(): void
    {
        $done = $this->preset([])->apply();

        Assert::same($done, ['settings' => 5, 'areas' => 16, 'runs' => 16]);
        Assert::count($this->areas, 16);
        Assert::count($this->runs, 16);
        Assert::same($this->areas[0], 'Example Area 01');
        Assert::same($this->runs[0], ['category_primary_id' => '1', 'name' => 'Example Run 01']);
        Assert::same($this->runs[15], ['category_primary_id' => '16', 'name' => 'Example Run 16']);
    }

    public function itAddsNothingToAnInstallThatAlreadyHasARun(): void
    {
        $done = $this->preset([7 => 'A real run'])->apply();

        Assert::same($done, ['settings' => 5, 'areas' => 0, 'runs' => 0]);
        Assert::count($this->areas, 0);
        Assert::count($this->runs, 0);
        // the settings are still applied
        Assert::same($this->saved[BusinessType::SETTING_KEY], 'window_cleaning');
    }

    public function theNumberOfRunsCanBeChosen(): void
    {
        $done = $this->preset([])->apply(4);

        Assert::same($done['runs'], 4);
        Assert::count($this->runs, 4);
    }
}
