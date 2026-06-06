<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\TaxRate\TaxRateForm;
use PHPUnit\Framework\TestCase;

class TaxRateFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new TaxRateForm();

        $this->assertSame('', $form->getTaxRateName());
        $this->assertSame(0.00, $form->getTaxRatePercent());
        $this->assertFalse($form->getTaxRateDefault());
        $this->assertSame('', $form->getTaxRateCode());
        $this->assertSame('', $form->getPeppolTaxRateCode());
        $this->assertSame('', $form->getStorecoveTaxType());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new TaxRateForm())->getFormName());
    }

    public function testShowPopulatesStandardVatRate(): void
    {
        $entity = new TaxRate();
        $entity->setTaxRateName('Standard');
        $entity->setTaxRatePercent(20.00);
        $entity->setTaxRateDefault(true);
        $entity->setTaxRateCode('S');
        $entity->setPeppolTaxRateCode('S');
        $entity->setStorecoveTaxType('standard');

        $form = TaxRateForm::show($entity);

        $this->assertSame('Standard', $form->getTaxRateName());
        $this->assertSame(20.00, $form->getTaxRatePercent());
        $this->assertTrue($form->getTaxRateDefault());
        $this->assertSame('S', $form->getTaxRateCode());
        $this->assertSame('S', $form->getPeppolTaxRateCode());
        $this->assertSame('standard', $form->getStorecoveTaxType());
    }

    public function testShowPopulatesReducedRate(): void
    {
        $entity = new TaxRate();
        $entity->setTaxRateName('Reduced');
        $entity->setTaxRatePercent(5.00);
        $entity->setTaxRateDefault(false);
        $entity->setTaxRateCode('R');
        $entity->setPeppolTaxRateCode('AA');
        $entity->setStorecoveTaxType('reduced');

        $form = TaxRateForm::show($entity);

        $this->assertSame('Reduced', $form->getTaxRateName());
        $this->assertSame(5.00, $form->getTaxRatePercent());
        $this->assertFalse($form->getTaxRateDefault());
    }

    public function testShowWithZeroRate(): void
    {
        $entity = new TaxRate();
        $entity->setTaxRateName('Zero');
        $entity->setTaxRatePercent(0.00);
        $entity->setTaxRateDefault(false);
        $entity->setTaxRateCode('Z');
        $entity->setPeppolTaxRateCode('Z');
        $entity->setStorecoveTaxType('zero');

        $form = TaxRateForm::show($entity);

        $this->assertSame(0.00, $form->getTaxRatePercent());
        $this->assertSame('Zero', $form->getTaxRateName());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new TaxRate();
        $entity->setTaxRateName('VAT');
        $entity->setTaxRatePercent(20.00);
        $entity->setTaxRateDefault(true);
        $entity->setTaxRateCode('S');
        $entity->setPeppolTaxRateCode('S');
        $entity->setStorecoveTaxType('standard');

        $this->assertNotSame(
            TaxRateForm::show($entity),
            TaxRateForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new TaxRate();
        $entity->setTaxRateName('VAT');
        $entity->setTaxRatePercent(20.00);
        $entity->setTaxRateDefault(true);
        $entity->setTaxRateCode('S');
        $entity->setPeppolTaxRateCode('S');
        $entity->setStorecoveTaxType('standard');

        $form = TaxRateForm::show($entity);

        $this->assertIsString($form->getTaxRateName());
        $this->assertIsFloat($form->getTaxRatePercent());
        $this->assertIsBool($form->getTaxRateDefault());
        $this->assertIsString($form->getTaxRateCode());
    }
}
