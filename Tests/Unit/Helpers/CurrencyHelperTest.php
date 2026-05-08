<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Invoice\Helpers\CurrencyHelper;
use Codeception\Test\Unit;
use ReflectionClass;

class CurrencyHelperTest extends Unit
{
    private function createCurrencyHelper(): CurrencyHelper
    {
        $reflection = new ReflectionClass(CurrencyHelper::class);
        return $reflection->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
    }

    public function testFindExistingCurrencyUSD(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('USD');
        
        $this->assertNotNull($currency);
        $this->assertSame('USD', $currency->getCode());
        $this->assertSame('840', $currency->getNumeric());
        $this->assertSame(2, $currency->getDecimals());
    }

    public function testFindExistingCurrencyEUR(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('EUR');
        
        $this->assertNotNull($currency);
        $this->assertSame('EUR', $currency->getCode());
        $this->assertSame('978', $currency->getNumeric());
        $this->assertSame(2, $currency->getDecimals());
    }

    public function testFindExistingCurrencyGBP(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('GBP');
        
        $this->assertNotNull($currency);
        $this->assertSame('GBP', $currency->getCode());
        $this->assertSame('826', $currency->getNumeric());
        $this->assertSame(2, $currency->getDecimals());
    }

    public function testFindExistingCurrencyJPY(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('JPY');
        
        $this->assertNotNull($currency);
        $this->assertSame('JPY', $currency->getCode());
        $this->assertSame('392', $currency->getNumeric());
        $this->assertSame(0, $currency->getDecimals());
    }

    public function testFindExistingCurrencyBHD(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('BHD');
        
        $this->assertNotNull($currency);
        $this->assertSame('BHD', $currency->getCode());
        $this->assertSame('048', $currency->getNumeric());
        $this->assertSame(3, $currency->getDecimals());
    }

    public function testFindWithLowercaseCode(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('usd');
        
        $this->assertNotNull($currency);
        $this->assertSame('USD', $currency->getCode());
    }

    public function testFindWithMixedCaseCode(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('EuR');
        
        $this->assertNotNull($currency);
        $this->assertSame('EUR', $currency->getCode());
    }

    public function testFindNonExistentCurrency(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('XXX');
        
        $this->assertNull($currency);
    }

    public function testFindEmptyCode(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('');
        
        $this->assertNull($currency);
    }

    public function testAllCurrenciesReturnsArray(): void
    {
        $currencies = CurrencyHelper::all();
        
        $this->assertIsArray($currencies);
        $this->assertNotEmpty($currencies);
    }

    public function testAllCurrenciesContainsCommonCurrencies(): void
    {
        $currencies = CurrencyHelper::all();
        
        $this->assertArrayHasKey('USD', $currencies);
        $this->assertArrayHasKey('EUR', $currencies);
        $this->assertArrayHasKey('GBP', $currencies);
        $this->assertArrayHasKey('JPY', $currencies);
    }

    public function testCurrencyStructure(): void
    {
        $currencies = CurrencyHelper::all();
        $usd = $currencies['USD'];
        
        $this->assertArrayHasKey('numeric', $usd);
        $this->assertArrayHasKey('decimals', $usd);
        $this->assertArrayHasKey('stripe_v10', $usd);
        
        $this->assertSame('840', $usd['numeric']);
        $this->assertSame(2, $usd['decimals']);
        $this->assertSame(1, $usd['stripe_v10']);
    }

    public function testCurrencyWithZeroDecimals(): void
    {
        $currencies = CurrencyHelper::all();
        $jpy = $currencies['JPY'];
        
        $this->assertSame(0, $jpy['decimals']);
    }

    public function testCurrencyWithThreeDecimals(): void
    {
        $currencies = CurrencyHelper::all();
        $bhd = $currencies['BHD'];
        
        $this->assertSame(3, $bhd['decimals']);
    }

    public function testCurrencyWithFourDecimals(): void
    {
        $currencies = CurrencyHelper::all();
        $clf = $currencies['CLF'];
        
        $this->assertSame(4, $clf['decimals']);
    }

    public function testCurrencyStripeSupport(): void
    {
        $currencies = CurrencyHelper::all();
        
        // Test supported currency
        $usd = $currencies['USD'];
        $this->assertSame(1, $usd['stripe_v10']);
        
        // Test unsupported currency
        $irr = $currencies['IRR'];
        $this->assertSame(0, $irr['stripe_v10']);
    }

    public function testGetCodeMethod(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('USD');
        
        $this->assertSame('USD', $currency->getCode());
    }

    public function testGetNumericMethod(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('USD');
        
        $this->assertSame('840', $currency->getNumeric());
    }

    public function testGetDecimalsMethod(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('USD');
        
        $this->assertSame(2, $currency->getDecimals());
    }

    public function testGetDecimalsReturnsInt(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $currency = $currencyHelper->find('USD');
        
        $this->assertIsInt($currency->getDecimals());
    }

    public function testMultipleCurrenciesWithDifferentProperties(): void
    {
        $currencyHelper = $this->createCurrencyHelper();
        $usd = $currencyHelper->find('USD');
        $jpy = $currencyHelper->find('JPY');
        $bhd = $currencyHelper->find('BHD');
        
        // Different decimal places
        $this->assertSame(2, $usd->getDecimals());
        $this->assertSame(0, $jpy->getDecimals());
        $this->assertSame(3, $bhd->getDecimals());
        
        // Different numeric codes
        $this->assertSame('840', $usd->getNumeric());
        $this->assertSame('392', $jpy->getNumeric());
        $this->assertSame('048', $bhd->getNumeric());
    }

    public function testCurrenciesCount(): void
    {
        $currencies = CurrencyHelper::all();
        
        // Should have a reasonable number of currencies (ISO 4217 compliant)
        $this->assertGreaterThan(100, count($currencies));
        $this->assertLessThan(200, count($currencies));
    }
}
