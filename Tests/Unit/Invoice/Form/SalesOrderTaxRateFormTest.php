<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateForm;
use PHPUnit\Framework\TestCase;

class SalesOrderTaxRateFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new SalesOrderTaxRateForm();

        $this->assertNull($form->getSalesOrderId());
        $this->assertNull($form->getTaxRateId());
        $this->assertNull($form->getIncludeItemTax());
        $this->assertSame(0.0, $form->getSalesOrderTaxRateAmount());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new SalesOrderTaxRateForm())->getFormName());
    }

    public function testShowPopulatesIds(): void
    {
        $entity = new SalesOrderTaxRate();
        // reqSalesOrderId() reads $this->id (known entity behaviour); setId() satisfies it
        $entity->setId(3);
        $entity->setTaxRateId(5);

        $form = SalesOrderTaxRateForm::show($entity);

        $this->assertSame(3, $form->getSalesOrderId());
        $this->assertSame(5, $form->getTaxRateId());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new SalesOrderTaxRate();
        $entity->setId(2);
        $entity->setTaxRateId(4);
        $entity->setIncludeItemTax(1);
        $entity->setSalesOrderTaxRateAmount(88.50);

        $form = SalesOrderTaxRateForm::show($entity);

        $this->assertSame(2, $form->getSalesOrderId());
        $this->assertSame(4, $form->getTaxRateId());
        $this->assertSame(1, $form->getIncludeItemTax());
        $this->assertSame(88.50, $form->getSalesOrderTaxRateAmount());
    }

    public function testGetSalesOrderTaxRateAmountFallsBackToZero(): void
    {
        // SalesOrderTaxRate entity defaults sales_order_tax_rate_amount to null
        // Form getter uses ?? 0.00
        $entity = new SalesOrderTaxRate();
        $entity->setId(1);
        $entity->setTaxRateId(1);

        $form = SalesOrderTaxRateForm::show($entity);

        $this->assertSame(0.0, $form->getSalesOrderTaxRateAmount());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new SalesOrderTaxRate();
        $entity->setId(1);
        $entity->setTaxRateId(2);

        $this->assertNotSame(
            SalesOrderTaxRateForm::show($entity),
            SalesOrderTaxRateForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new SalesOrderTaxRate();
        $entity->setId(5);
        $entity->setTaxRateId(3);

        $form = SalesOrderTaxRateForm::show($entity);

        $this->assertIsInt($form->getSalesOrderId());
        $this->assertIsInt($form->getTaxRateId());
        $this->assertIsFloat($form->getSalesOrderTaxRateAmount());
    }
}
