<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Setting\SettingRepository;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

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
