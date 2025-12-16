<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\TaxRate;
use Codeception\Test\Unit;

class TaxRateEntityTest extends Unit
{
    private TaxRate $taxRate;
    
    public string $vatStandard = 'VAT Standard';

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxRate = new TaxRate();
    }

    public function testConstructorWithDefaults(): void
    {
        $defaultTaxRate = new TaxRate();
        
        $this->assertEquals('', $defaultTaxRate->getTaxRateCode());
        $this->assertEquals('', $defaultTaxRate->getPeppolTaxRateCode());
        $this->assertEquals('', $defaultTaxRate->getStorecoveTaxType());
        $this->assertEquals('', $defaultTaxRate->getTaxRateName());
        $this->assertEquals(0.00, $defaultTaxRate->getTaxRatePercent());
        $this->assertFalse($defaultTaxRate->getTaxRateDefault());
        $this->assertNull($defaultTaxRate->getTaxRateId());
    }

    public function testConstructorWithAllParameters(): void
    {
        $taxRate = new TaxRate(
            tax_rate_code: 'VT',
            peppol_tax_rate_code: 'S',
            storecove_tax_type: 'standard',
            tax_rate_name: $this->vatStandard,
            tax_rate_percent: 20.00,
            tax_rate_default: true
        );

        $this->assertEquals('VT', $taxRate->getTaxRateCode());
        $this->assertEquals('S', $taxRate->getPeppolTaxRateCode());
        $this->assertEquals('standard', $taxRate->getStorecoveTaxType());
        $this->assertEquals($this->vatStandard, $taxRate->getTaxRateName());
        $this->assertEquals(20.00, $taxRate->getTaxRatePercent());
        $this->assertTrue($taxRate->getTaxRateDefault());
        $this->assertNull($taxRate->getTaxRateId());
    }

    public function testTaxRateIdSetterAndGetter(): void
    {
        $this->assertNull($this->taxRate->getTaxRateId());
        
        $this->taxRate->setTaxRateId(42);
        $this->assertEquals(42, $this->taxRate->getTaxRateId());
        
        $this->taxRate->setTaxRateId(0);
        $this->assertEquals(0, $this->taxRate->getTaxRateId());
    }

    public function testTaxRateCodeSetterAndGetter(): void
    {
        $this->assertEquals('', $this->taxRate->getTaxRateCode());
        
        $this->taxRate->setTaxRateCode('VT');
        $this->assertEquals('VT', $this->taxRate->getTaxRateCode());
        
        $this->taxRate->setTaxRateCode('');
        $this->assertEquals('', $this->taxRate->getTaxRateCode());
    }

    public function testPeppolTaxRateCodeSetterAndGetter(): void
    {
        $this->assertEquals('', $this->taxRate->getPeppolTaxRateCode());
        
        $this->taxRate->setPeppolTaxRateCode('S');
        $this->assertEquals('S', $this->taxRate->getPeppolTaxRateCode());
        
        $this->taxRate->setPeppolTaxRateCode('Z');
        $this->assertEquals('Z', $this->taxRate->getPeppolTaxRateCode());
    }

    public function testStorecoveTaxTypeSetterAndGetter(): void
    {
        $this->assertEquals('', $this->taxRate->getStorecoveTaxType());
        
        $this->taxRate->setStorecoveTaxType('standard');
        $this->assertEquals('standard', $this->taxRate->getStorecoveTaxType());
        
        $this->taxRate->setStorecoveTaxType('reduced');
        $this->assertEquals('reduced', $this->taxRate->getStorecoveTaxType());
    }

    public function testTaxRateNameSetterAndGetter(): void
    {
        $this->assertEquals('', $this->taxRate->getTaxRateName());
        
        $this->taxRate->setTaxRateName($this->vatStandard);
        $this->assertEquals($this->vatStandard, $this->taxRate->getTaxRateName());
        
        $this->taxRate->setTaxRateName('Zero Rate');
        $this->assertEquals('Zero Rate', $this->taxRate->getTaxRateName());
    }

    public function testTaxRatePercentSetterAndGetter(): void
    {
        $this->assertEquals(0.00, $this->taxRate->getTaxRatePercent());
        
        $this->taxRate->setTaxRatePercent(20.00);
        $this->assertEquals(20.00, $this->taxRate->getTaxRatePercent());
        
        $this->taxRate->setTaxRatePercent(5.5);
        $this->assertEquals(5.5, $this->taxRate->getTaxRatePercent());
        
        $this->taxRate->setTaxRatePercent(0.0);
        $this->assertEquals(0.0, $this->taxRate->getTaxRatePercent());
    }

    public function testTaxRateDefaultSetterAndGetter(): void
    {
        $this->assertFalse($this->taxRate->getTaxRateDefault());
        
        $this->taxRate->setTaxRateDefault(true);
        $this->assertTrue($this->taxRate->getTaxRateDefault());
        
        $this->taxRate->setTaxRateDefault(false);
        $this->assertFalse($this->taxRate->getTaxRateDefault());
    }

    public function testHighPrecisionTaxRates(): void
    {
        // Test with high precision decimal values
        $this->taxRate->setTaxRatePercent(19.99);
        $this->assertEquals(19.99, $this->taxRate->getTaxRatePercent());
        
        $this->taxRate->setTaxRatePercent(0.01);
        $this->assertEquals(0.01, $this->taxRate->getTaxRatePercent());
        
        $this->taxRate->setTaxRatePercent(99.99);
        $this->assertEquals(99.99, $this->taxRate->getTaxRatePercent());
    }

    public function testTaxRateCodesWithEmptyValues(): void
    {
        // Test setting empty string values
        $this->taxRate->setTaxRateCode('');
        $this->assertEquals('', $this->taxRate->getTaxRateCode());
        
        $this->taxRate->setPeppolTaxRateCode('');
        $this->assertEquals('', $this->taxRate->getPeppolTaxRateCode());
    }

    public function testCompleteEntitySetup(): void
    {
        // Test setting up a complete tax rate entity
        $this->taxRate->setTaxRateId(1);
        $this->taxRate->setTaxRateCode('VT');
        $this->taxRate->setPeppolTaxRateCode('S');
        $this->taxRate->setStorecoveTaxType('standard');
        $this->taxRate->setTaxRateName('VAT Standard Rate');
        $this->taxRate->setTaxRatePercent(20.00);
        $this->taxRate->setTaxRateDefault(true);

        $this->assertEquals(1, $this->taxRate->getTaxRateId());
        $this->assertEquals('VT', $this->taxRate->getTaxRateCode());
        $this->assertEquals('S', $this->taxRate->getPeppolTaxRateCode());
        $this->assertEquals('standard', $this->taxRate->getStorecoveTaxType());
        $this->assertEquals('VAT Standard Rate', $this->taxRate->getTaxRateName());
        $this->assertEquals(20.00, $this->taxRate->getTaxRatePercent());
        $this->assertTrue($this->taxRate->getTaxRateDefault());
    }

    public function testChainedSetterCalls(): void
    {
        // Test that setters work in a chained manner
        $this->taxRate->setTaxRateCode('VT');
        $this->taxRate->setTaxRateName('VAT');
        $this->taxRate->setTaxRatePercent(15.0);
        
        $this->assertEquals('VT', $this->taxRate->getTaxRateCode());
        $this->assertEquals('VAT', $this->taxRate->getTaxRateName());
        $this->assertEquals(15.0, $this->taxRate->getTaxRatePercent());
    }
}
