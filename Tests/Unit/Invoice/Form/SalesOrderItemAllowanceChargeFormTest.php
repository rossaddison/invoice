<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceCharge;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeForm;
use PHPUnit\Framework\TestCase;

class SalesOrderItemAllowanceChargeFormTest extends TestCase
{
    public function testGetFormNameReturnsEmptyString(): void
    {
        $entity = new SalesOrderItemAllowanceCharge();
        $form = new SalesOrderItemAllowanceChargeForm($entity, null);

        $this->assertSame('', $form->getFormName());
    }

    public function testNullEntityValuesAreZero(): void
    {
        // getSalesOrderId()/getAllowanceChargeId() return ?int = null → (int)null = 0
        // getAmount()/getVatOrTax() return ?float = null → (float)null = 0.0
        $entity = new SalesOrderItemAllowanceCharge();
        $form = new SalesOrderItemAllowanceChargeForm($entity, null);

        $this->assertSame(0, $form->getSalesOrderId());
        $this->assertSame(0, $form->getAllowanceChargeId());
        $this->assertSame(0.0, $form->getAmount());
        $this->assertSame(0.0, $form->getVatOrTax());
        $this->assertNull($form->getSalesOrderItemId());
    }

    public function testConstructorUsesPassedItemId(): void
    {
        $entity = new SalesOrderItemAllowanceCharge();
        $form = new SalesOrderItemAllowanceChargeForm($entity, 12);

        $this->assertSame(12, $form->getSalesOrderItemId());
    }

    public function testConstructorPopulatesFromEntity(): void
    {
        $entity = new SalesOrderItemAllowanceCharge();
        $entity->setSalesOrderId(4);
        $entity->setAllowanceChargeId(7);
        $entity->setAmount(300.00);
        $entity->setVatOrTax(60.00);

        $form = new SalesOrderItemAllowanceChargeForm($entity, 5);

        $this->assertSame(4, $form->getSalesOrderId());
        $this->assertSame(7, $form->getAllowanceChargeId());
        $this->assertSame(300.0, $form->getAmount());
        $this->assertSame(60.0, $form->getVatOrTax());
        $this->assertSame(5, $form->getSalesOrderItemId());
    }

    public function testConstructorWithNullItemId(): void
    {
        $entity = new SalesOrderItemAllowanceCharge();
        $form = new SalesOrderItemAllowanceChargeForm($entity, null);

        $this->assertNull($form->getSalesOrderItemId());
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new SalesOrderItemAllowanceCharge();
        $entity->setSalesOrderId(1);
        $entity->setAllowanceChargeId(1);
        $entity->setAmount(100.00);

        $form = new SalesOrderItemAllowanceChargeForm($entity, 3);

        $this->assertIsInt($form->getSalesOrderId());
        $this->assertIsInt($form->getAllowanceChargeId());
        $this->assertIsFloat($form->getAmount());
        $this->assertIsInt($form->getSalesOrderItemId());
    }
}
