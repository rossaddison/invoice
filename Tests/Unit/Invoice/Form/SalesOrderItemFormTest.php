<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Invoice\SalesOrderItem\SalesOrderItemForm;
use PHPUnit\Framework\TestCase;

class SalesOrderItemFormTest extends TestCase
{
    public function testDefaultsAreEmptyOrNull(): void
    {
        $form = new SalesOrderItemForm();

        $this->assertSame('', $form->getPeppolPoItemid());
        $this->assertSame('', $form->getPeppolPoLineid());
        $this->assertSame('', $form->getTaxRateId());
        $this->assertSame('', $form->getProductId());
        $this->assertSame('', $form->getTaskId());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getDescription());
        $this->assertNull($form->getQuantity());
        $this->assertNull($form->getPrice());
        $this->assertNull($form->getDiscountAmount());
        $this->assertNull($form->getOrder());
        $this->assertSame('', $form->getProductUnit());
        $this->assertNull($form->getProductUnitId());
        $this->assertNull($form->getSoId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new SalesOrderItemForm())->getFormName());
    }

    public function testShowUsesPassedSoId(): void
    {
        $entity = new SalesOrderItem();

        $form = SalesOrderItemForm::show($entity, 'SO-001');

        $this->assertSame('SO-001', $form->getSoId());
    }

    public function testShowEntityDefaultQuantityAndPrice(): void
    {
        // SalesOrderItem constructor: quantity=1.00, price=0.00, discount_amount=0.00
        $entity = new SalesOrderItem();

        $form = SalesOrderItemForm::show($entity, null);

        $this->assertSame(1.0, $form->getQuantity());
        $this->assertSame(0.0, $form->getPrice());
        $this->assertSame(0.0, $form->getDiscountAmount());
    }

    public function testShowProductTaskAndTaxRateAreEmptyWhenNotLoaded(): void
    {
        // Unloaded relations: (string) null-safe null = ''; getTaxRateId() = ?int null → ''
        $entity = new SalesOrderItem();

        $form = SalesOrderItemForm::show($entity, null);

        $this->assertSame('', $form->getProductId());
        $this->assertSame('', $form->getTaskId());
        $this->assertSame('', $form->getTaxRateId());
    }

    public function testShowPopulatesStringFields(): void
    {
        $entity = new SalesOrderItem();
        $entity->setPeppolPoItemid('PO-ITEM-001');
        $entity->setPeppolPoLineid('PO-LINE-001');
        $entity->setName('Test Item');
        $entity->setDescription('Test description');

        $form = SalesOrderItemForm::show($entity, 'SO-002');

        $this->assertSame('PO-ITEM-001', $form->getPeppolPoItemid());
        $this->assertSame('PO-LINE-001', $form->getPeppolPoLineid());
        $this->assertSame('Test Item', $form->getName());
        $this->assertSame('Test description', $form->getDescription());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new SalesOrderItem();

        $this->assertNotSame(
            SalesOrderItemForm::show($entity, null),
            SalesOrderItemForm::show($entity, null)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new SalesOrderItem();

        $form = SalesOrderItemForm::show($entity, 'SO-003');

        $this->assertIsString($form->getSoId());
        $this->assertIsString($form->getTaxRateId());
        $this->assertIsFloat($form->getQuantity());
    }
}
