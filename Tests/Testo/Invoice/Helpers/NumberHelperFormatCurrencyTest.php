<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Setting\SettingRepository;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;

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

    public function formatCurrencyRoundsHalfUpOnExactDecimalBoundary(): void
    {
        // BigDecimal::toScale(2, RoundingMode::HalfUp) against the string
        // '1.005' -- routed through Brick\Math since the NumberHelper ->
        // Brick\Money swap -- rounds up, same as number_format() already
        // did on this PHP build. Locks in the rounding mode explicitly
        // rather than relying on it matching PHP-engine behaviour by
        // coincidence.
        Assert::same($this->nh->formatCurrency(1.005), '£1.01');
    }

    public function formatCurrencyWithNegativeAmount(): void
    {
        Assert::same($this->nh->formatCurrency(-1234.56), '£-1,234.56');
    }

    public function formatCurrencyWithStringAmount(): void
    {
        // Amounts arrive as float in practice (InvAmount::getTotal(): float)
        // but the parameter is `mixed` -- a numeric string must round the
        // same way a float does.
        Assert::same($this->nh->formatCurrency('1234.56'), '£1,234.56');
    }

    public function formatCurrencyWithNonNumericStringFallsBackToZero(): void
    {
        Assert::same($this->nh->formatCurrency('not-a-number'), '£0.00');
    }
}
