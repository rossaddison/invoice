<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Setting\SettingRepository;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

#[Test]
final class NumberHelperRecurFrequenciesTest
{
    private readonly NumberHelper $nh;

    public function __construct()
    {
        $sRepo = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
        $sRepo->settingsArray = [
            'currency_symbol'           => '£',
            'currency_symbol_placement' => 'before',
            'thousands_separator'       => ',',
            'decimal_point'             => '.',
        ];
        $this->nh = new NumberHelper($sRepo);
    }

    public function recurFrequenciesReturnsTwentyEightEntries(): void
    {
        Assert::count($this->nh->recurFrequencies(), 28);
    }

    public function recurFrequenciesContainsDailyKeys(): void
    {
        $freq = $this->nh->recurFrequencies();

        Assert::true(isset($freq['1D']));
        Assert::true(isset($freq['7D']));
        Assert::true(isset($freq['30D']));
    }

    public function recurFrequenciesContainsMonthlyKeys(): void
    {
        $freq = $this->nh->recurFrequencies();

        Assert::true(isset($freq['1M']));
        Assert::true(isset($freq['6M']));
        Assert::true(isset($freq['11M']));
    }

    public function recurFrequenciesContainsYearlyKeys(): void
    {
        $freq = $this->nh->recurFrequencies();

        Assert::true(isset($freq['1Y']));
        Assert::true(isset($freq['5Y']));
    }

    public function recurFrequenciesMapsOneDayToTranslationKey(): void
    {
        Assert::same($this->nh->recurFrequencies()['1D'], 'calendar.day.1');
    }

    public function recurFrequenciesMapsOneWeekToTranslationKey(): void
    {
        Assert::same($this->nh->recurFrequencies()['7D'], 'calendar.week.1');
    }

    public function recurFrequenciesMapsOneMonthToTranslationKey(): void
    {
        Assert::same($this->nh->recurFrequencies()['1M'], 'calendar.month.1');
    }

    public function recurFrequenciesMapsOneYearToTranslationKey(): void
    {
        Assert::same($this->nh->recurFrequencies()['1Y'], 'calendar.year.1');
    }
}
