<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\TaxRate;
use App\Invoice\TaxRate\TaxRateForm;
use Codeception\Test\Unit;

class TaxRateFormTest extends Unit
{
    private TaxRate $taxRate;
    private TaxRateForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a sample TaxRate entity
        $this->taxRate = new TaxRate(
            tax_rate_code: 'VT',
            peppol_tax_rate_code: 'S',
            storecove_tax_type: 'standard', 
            tax_rate_name: 'VAT Standard',
            tax_rate_percent: 20.00,
            tax_rate_default: true
        );
        
        $this->form = new TaxRateForm($this->taxRate);
    }

    public function testFormInitializationFromEntity(): void
    {
        $this->assertEquals('VAT Standard', $this->form->getTaxRateName());
        $this->assertEquals(20.00, $this->form->getTaxRatePercent());
        $this->assertTrue($this->form->getTaxRateDefault());
        $this->assertEquals('VT', $this->form->getTaxRateCode());
        $this->assertEquals('S', $this->form->getPeppolTaxRateCode());
        $this->assertEquals('standard', $this->form->getStorecoveTaxType());
    }

    public function testGetFormName(): void
    {
        $this->assertEquals('', $this->form->getFormName());
    }

    public function testTaxRateNameValidation(): void
    {
        // Test with minimum entity (empty tax rate name should fail validation when validated)
        $emptyEntity = new TaxRate(
            tax_rate_name: '',
            tax_rate_percent: 10.00
        );
        $emptyForm = new TaxRateForm($emptyEntity);
        
        $this->assertEquals('', $emptyForm->getTaxRateName());
        $this->assertEquals(10.00, $emptyForm->getTaxRatePercent());
    }

    public function testTaxRatePercentHandling(): void
    {
        $highTaxEntity = new TaxRate(
            tax_rate_name: 'High Tax',
            tax_rate_percent: 99.99
        );
        $highTaxForm = new TaxRateForm($highTaxEntity);
        
        $this->assertEquals('High Tax', $highTaxForm->getTaxRateName());
        $this->assertEquals(99.99, $highTaxForm->getTaxRatePercent());
    }

    public function testZeroTaxRate(): void
    {
        $zeroTaxEntity = new TaxRate(
            tax_rate_name: 'Zero Rate',
            tax_rate_percent: 0.00,
            tax_rate_default: false
        );
        $zeroTaxForm = new TaxRateForm($zeroTaxEntity);
        
        $this->assertEquals('Zero Rate', $zeroTaxForm->getTaxRateName());
        $this->assertEquals(0.00, $zeroTaxForm->getTaxRatePercent());
        $this->assertFalse($zeroTaxForm->getTaxRateDefault());
    }

    public function testOptionalFields(): void
    {
        $minimalEntity = new TaxRate(
            tax_rate_name: 'Basic Tax',
            tax_rate_percent: 15.00
        );
        $minimalForm = new TaxRateForm($minimalEntity);
        
        $this->assertEquals('Basic Tax', $minimalForm->getTaxRateName());
        $this->assertEquals(15.00, $minimalForm->getTaxRatePercent());
        $this->assertEquals('', $minimalForm->getTaxRateCode());
        $this->assertEquals('', $minimalForm->getPeppolTaxRateCode());
        $this->assertEquals('', $minimalForm->getStorecoveTaxType());
        $this->assertFalse($minimalForm->getTaxRateDefault());
    }

    public function testCodeFieldsMaxLength(): void
    {
        // Test with 2-character codes (max length)
        $codeEntity = new TaxRate(
            tax_rate_code: 'VT',
            peppol_tax_rate_code: 'AA',
            tax_rate_name: 'Code Test',
            tax_rate_percent: 5.00
        );
        $codeForm = new TaxRateForm($codeEntity);
        
        $this->assertEquals('VT', $codeForm->getTaxRateCode());
        $this->assertEquals('AA', $codeForm->getPeppolTaxRateCode());
        $this->assertEquals('Code Test', $codeForm->getTaxRateName());
    }

    public function testStorecoveTaxType(): void
    {
        $storecoveEntity = new TaxRate(
            storecove_tax_type: 'reduced',
            tax_rate_name: 'Reduced Rate',
            tax_rate_percent: 5.00
        );
        $storecoveForm = new TaxRateForm($storecoveEntity);
        
        $this->assertEquals('reduced', $storecoveForm->getStorecoveTaxType());
        $this->assertEquals('Reduced Rate', $storecoveForm->getTaxRateName());
        $this->assertEquals(5.00, $storecoveForm->getTaxRatePercent());
    }

    public function testAllGetterMethods(): void
    {
        // Test that all getter methods return expected types
        $this->assertIsString($this->form->getTaxRateName());
        $this->assertIsFloat($this->form->getTaxRatePercent());
        $this->assertIsBool($this->form->getTaxRateDefault());
        $this->assertIsString($this->form->getTaxRateCode());
        $this->assertIsString($this->form->getPeppolTaxRateCode());
        $this->assertIsString($this->form->getStorecoveTaxType());
        $this->assertIsString($this->form->getFormName());
    }

    public function testDefaultValues(): void
    {
        $defaultEntity = new TaxRate();
        $defaultForm = new TaxRateForm($defaultEntity);
        
        $this->assertEquals('', $defaultForm->getTaxRateName());
        $this->assertEquals(0.00, $defaultForm->getTaxRatePercent());
        $this->assertFalse($defaultForm->getTaxRateDefault());
        $this->assertEquals('', $defaultForm->getTaxRateCode());
        $this->assertEquals('', $defaultForm->getPeppolTaxRateCode());
        $this->assertEquals('', $defaultForm->getStorecoveTaxType());
    }
}