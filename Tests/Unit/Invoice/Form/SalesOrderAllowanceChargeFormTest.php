<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\SalesOrderAllowanceCharge\SalesOrderAllowanceCharge;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeForm;
use PHPUnit\Framework\TestCase;

class SalesOrderAllowanceChargeFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new SalesOrderAllowanceChargeForm();

        $this->assertNull($form->getAllowanceChargeId());
        $this->assertNull($form->getSalesorderId());
        $this->assertNull($form->getAmount());
        $this->assertNull($form->getVatOrTax());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new SalesOrderAllowanceChargeForm())->getFormName());
    }

    public function testShowNullEntityAmountsAreZero(): void
    {
        // getAllowanceChargeId()/getAmount()/getVatOrTax() return ?int/?float — null casts to 0
        $entity = new SalesOrderAllowanceCharge();

        $form = SalesOrderAllowanceChargeForm::show($entity, 5);

        $this->assertSame(0, $form->getAllowanceChargeId());
        $this->assertSame(0, $form->getAmount());
        $this->assertSame(0, $form->getVatOrTax());
        $this->assertSame(5, $form->getSalesorderId());
    }

    public function testShowPopulatesFromEntity(): void
    {
        $entity = new SalesOrderAllowanceCharge();
        $entity->setAllowanceChargeId(3);
        $entity->setAmount(200.00);
        $entity->setVatOrTax(40.00);

        $form = SalesOrderAllowanceChargeForm::show($entity, 7);

        $this->assertSame(3, $form->getAllowanceChargeId());
        $this->assertSame(200, $form->getAmount());
        $this->assertSame(40, $form->getVatOrTax());
    }

    public function testShowWithNullSalesOrderId(): void
    {
        $entity = new SalesOrderAllowanceCharge();

        $form = SalesOrderAllowanceChargeForm::show($entity, null);

        $this->assertNull($form->getSalesorderId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new SalesOrderAllowanceCharge();

        $this->assertNotSame(
            SalesOrderAllowanceChargeForm::show($entity, 1),
            SalesOrderAllowanceChargeForm::show($entity, 1)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new SalesOrderAllowanceCharge();
        $entity->setAllowanceChargeId(1);
        $entity->setAmount(50.00);

        $form = SalesOrderAllowanceChargeForm::show($entity, 1);

        $this->assertIsInt($form->getAllowanceChargeId());
        $this->assertIsInt($form->getAmount());
        $this->assertIsInt($form->getSalesorderId());
    }
}
