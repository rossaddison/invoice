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

#[Test]
final class NumberHelperFormatCurrencyTest
{
    private readonly NumberHelper $nh;
    private readonly SettingRepository $sRepo;

    private const AMOUNT = 1234.56;
    private const FORMATTED = '1,234.56';

    public function __construct()
    {
        $sRepo = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
        $sRepo->settingsArray = [
            'currency_symbol'           => '£',
            'currency_symbol_placement' => 'before',
            'thousands_separator'       => ',',
            'decimal_point'             => '.',
        ];
        $this->sRepo = $sRepo;
        $this->nh    = new NumberHelper($sRepo);
    }

    public function formatCurrencyPlacesSymbolBefore(): void
    {
        $this->sRepo->settingsArray['currency_symbol_placement'] = 'before';

        Assert::same($this->nh->formatCurrency(self::AMOUNT), '£' . self::FORMATTED);
    }

    public function formatCurrencyPlacesSymbolAfterWithNbsp(): void
    {
        $this->sRepo->settingsArray['currency_symbol_placement'] = 'afterspace';

        Assert::same($this->nh->formatCurrency(self::AMOUNT), self::FORMATTED . '&nbsp;£');
    }

    public function formatCurrencyPlacesSymbolAfterNoSpace(): void
    {
        $this->sRepo->settingsArray['currency_symbol_placement'] = 'after';

        Assert::same($this->nh->formatCurrency(self::AMOUNT), self::FORMATTED . '£');
    }

    public function formatCurrencyWithZeroAmount(): void
    {
        $this->sRepo->settingsArray['currency_symbol_placement'] = 'before';

        Assert::same($this->nh->formatCurrency(0), '£0.00');
    }

    public function formatCurrencyWithNullAmountTreatedAsZero(): void
    {
        $this->sRepo->settingsArray['currency_symbol_placement'] = 'before';

        Assert::same($this->nh->formatCurrency(null), '£0.00');
    }

    public function formatCurrencyWithNoDecimalPointSettingUsesZeroDecimals(): void
    {
        $this->sRepo->settingsArray['currency_symbol_placement'] = 'before';
        $this->sRepo->settingsArray['decimal_point']             = '';

        Assert::same($this->nh->formatCurrency(self::AMOUNT), '£1,235');

        $this->sRepo->settingsArray['decimal_point'] = '.';
    }
}

#[Test]
final class NumberHelperFormatAmountTest
{
    private readonly NumberHelper $nh;
    private readonly SettingRepository $sRepo;

    public function __construct()
    {
        $sRepo = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
        $sRepo->settingsArray = [
            'currency_symbol'           => '£',
            'currency_symbol_placement' => 'before',
            'thousands_separator'       => ',',
            'decimal_point'             => '.',
        ];
        $this->sRepo = $sRepo;
        $this->nh    = new NumberHelper($sRepo);
    }

    public function formatAmountFormatsWithSeparators(): void
    {
        Assert::same($this->nh->formatAmount(1234.56), '1,234.56');
    }

    public function formatAmountWithLargeNumber(): void
    {
        Assert::same($this->nh->formatAmount(1000000.00), '1,000,000.00');
    }

    public function formatAmountReturnsNullForNull(): void
    {
        Assert::null($this->nh->formatAmount(null));
    }

    public function formatAmountWithZero(): void
    {
        Assert::same($this->nh->formatAmount(0), '0.00');
    }

    public function standardizeAmountStripsThousandsSeparator(): void
    {
        Assert::same($this->nh->standardizeAmount('1,234.56'), '1234.56');
    }

    public function standardizeAmountReplacesDecimalPointWithDot(): void
    {
        Assert::same($this->nh->standardizeAmount('1234.56'), '1234.56');
    }

    public function standardizeAmountHandlesEuropeanFormat(): void
    {
        $this->sRepo->settingsArray['thousands_separator'] = '.';
        $this->sRepo->settingsArray['decimal_point']       = ',';

        Assert::same($this->nh->standardizeAmount('1.234,56'), '1234.56');

        $this->sRepo->settingsArray['thousands_separator'] = ',';
        $this->sRepo->settingsArray['decimal_point']       = '.';
    }

    public function standardizeAmountWithNoSeparators(): void
    {
        Assert::same($this->nh->standardizeAmount('1234'), '1234');
    }
}
