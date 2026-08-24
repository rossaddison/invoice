<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Currency;

use App\Webshop\Currency\CurrencySymbols;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CurrencySymbols — the display-only symbol/flag/name lookups
 * this app's own currency setup (SettingRepository, see
 * CurrencyInfoProvider) never supplies itself (see that class's own
 * docblock).
 */
#[Test]
final class CurrencySymbolsTest
{
    public function formatPrependsTheMappedSymbol(): void
    {
        Assert::same('£19.99', CurrencySymbols::format(19.99, 'GBP'));
        Assert::same('€19.99', CurrencySymbols::format(19.99, 'EUR'));
    }

    public function formatFallsBackToTheBareCodeWhenUnmapped(): void
    {
        Assert::same('XYZ 19.99', CurrencySymbols::format(19.99, 'XYZ'));
    }

    public function formatWithNoCodeAtAllOmitsAnyPrefix(): void
    {
        Assert::same('19.99', CurrencySymbols::format(19.99, ''));
    }

    public function flagCountryCodeMapsKnownCurrenciesToFlagcdnCodes(): void
    {
        Assert::same('gb', CurrencySymbols::flagCountryCode('GBP'));
        Assert::same('eu', CurrencySymbols::flagCountryCode('EUR'));
    }

    public function flagCountryCodeIsNullForAnUnmappedCurrency(): void
    {
        Assert::null(CurrencySymbols::flagCountryCode('XYZ'));
    }

    public function pickerLabelCombinesSymbolCodeAndName(): void
    {
        Assert::same('£ - GBP - British Pound', CurrencySymbols::pickerLabel('GBP'));
        Assert::same('€ - EUR - Euro', CurrencySymbols::pickerLabel('EUR'));
    }

    public function pickerLabelFallsBackToTheBareCodeWhenUnmapped(): void
    {
        Assert::same('XYZ', CurrencySymbols::pickerLabel('XYZ'));
    }
}
