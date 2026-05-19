<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderCustom\SalesOrderCustom;
use App\Infrastructure\Persistence\CustomField\CustomField;
use PHPUnit\Framework\TestCase;

class SalesOrderCustomEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $soCustom = new SalesOrderCustom();
        $this->assertFalse($soCustom->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $soCustom = new SalesOrderCustom();
        $this->expectException(\LogicException::class);
        $soCustom->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $soCustom = new SalesOrderCustom();
        $soCustom->setId(20);
        $this->assertTrue($soCustom->hasIdentity());
        $this->assertSame(20, $soCustom->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $soCustom = new SalesOrderCustom();
        $soCustom->setId(1);
        $this->assertIsInt($soCustom->reqId());
    }

    public function testConstructorWithDefaults(): void
    {
        $soCustom = new SalesOrderCustom();
        $this->assertSame('', $soCustom->getValue());
        $this->assertNull($soCustom->getSalesOrder());
        $this->assertNull($soCustom->getCustomField());
    }

    public function testConstructorWithAllParameters(): void
    {
        $soCustom = new SalesOrderCustom(
            sales_order_id: 4,
            custom_field_id: 2,
            value: 'Approved',
        );

        $this->assertSame(4, $soCustom->reqSalesOrderId());
        $this->assertSame(2, $soCustom->reqCustomFieldId());
        $this->assertSame('Approved', $soCustom->getValue());
    }

    public function testSalesOrderIdSetterAndGetter(): void
    {
        $soCustom = new SalesOrderCustom();
        $soCustom->setSalesOrderId(30);
        $this->assertSame(30, $soCustom->reqSalesOrderId());
    }

    public function testSalesOrderIdIsReturnedAsInt(): void
    {
        $soCustom = new SalesOrderCustom(sales_order_id: 9);
        $this->assertIsInt($soCustom->reqSalesOrderId());
        $this->assertSame(9, $soCustom->reqSalesOrderId());
    }

    public function testCustomFieldIdSetterAndGetter(): void
    {
        $soCustom = new SalesOrderCustom();
        $soCustom->setCustomFieldId(6);
        $this->assertSame(6, $soCustom->reqCustomFieldId());
    }

    public function testCustomFieldIdIsReturnedAsInt(): void
    {
        $soCustom = new SalesOrderCustom(custom_field_id: 11);
        $this->assertIsInt($soCustom->reqCustomFieldId());
        $this->assertSame(11, $soCustom->reqCustomFieldId());
    }

    public function testValueSetterAndGetter(): void
    {
        $soCustom = new SalesOrderCustom();
        $soCustom->setValue('Pending review');
        $this->assertSame('Pending review', $soCustom->getValue());
    }

    public function testEmptyValue(): void
    {
        $soCustom = new SalesOrderCustom();
        $soCustom->setValue('');
        $this->assertSame('', $soCustom->getValue());
    }

    public function testSalesOrderRelationSetterAndGetter(): void
    {
        $soCustom = new SalesOrderCustom();
        $salesOrder = $this->createStub(SalesOrder::class);

        $soCustom->setSalesOrder($salesOrder);
        $this->assertSame($salesOrder, $soCustom->getSalesOrder());

        $soCustom->setSalesOrder(null);
        $this->assertNull($soCustom->getSalesOrder());
    }

    public function testCustomFieldRelationSetterAndGetter(): void
    {
        $soCustom = new SalesOrderCustom();
        $customField = $this->createStub(CustomField::class);

        $soCustom->setCustomField($customField);
        $this->assertSame($customField, $soCustom->getCustomField());

        $soCustom->setCustomField(null);
        $this->assertNull($soCustom->getCustomField());
    }

    public function testLongValue(): void
    {
        $soCustom = new SalesOrderCustom();
        $long = str_repeat('z', 1500);
        $soCustom->setValue($long);
        $this->assertSame($long, $soCustom->getValue());
    }
}
