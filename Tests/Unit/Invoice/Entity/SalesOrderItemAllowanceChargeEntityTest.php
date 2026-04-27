<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceCharge;
use PHPUnit\Framework\TestCase;

class SalesOrderItemAllowanceChargeEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $this->assertFalse($soiac->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $soiac->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $soiac->setId(20);
        $this->assertTrue($soiac->isPersisted());
        $this->assertSame(20, $soiac->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $soiac->setId(1);
        $this->assertIsInt($soiac->reqId());
    }

    public function testSalesOrderIdIsNullByDefault(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $this->assertNull($soiac->getSalesOrderId());
    }

    public function testSalesOrderIdSetterAndGetter(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $soiac->setSalesOrderId(5);
        $this->assertSame(5, $soiac->getSalesOrderId());
    }

    public function testSalesOrderItemIdIsNullByDefault(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $this->assertNull($soiac->getSalesOrderItemId());
    }

    public function testSalesOrderItemIdSetterAndGetter(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $soiac->setSalesOrderItemId(12);
        $this->assertSame(12, $soiac->getSalesOrderItemId());
    }

    public function testAllowanceChargeIdIsNullByDefault(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $this->assertNull($soiac->getAllowanceChargeId());
    }

    public function testAllowanceChargeIdSetterAndGetter(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $soiac->setAllowanceChargeId(3);
        $this->assertSame(3, $soiac->getAllowanceChargeId());
    }

    public function testAmountIsNullByDefault(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $this->assertNull($soiac->getAmount());
    }

    public function testAmountSetterAndGetter(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $soiac->setAmount(75.50);
        $this->assertSame(75.50, $soiac->getAmount());
    }

    public function testVatOrTaxIsNullByDefault(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $this->assertNull($soiac->getVatOrTax());
    }

    public function testVatOrTaxSetterAndGetter(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $soiac->setVatOrTax(15.00);
        $this->assertSame(15.00, $soiac->getVatOrTax());
    }

    public function testAllowanceChargeRelationSetterAndGetter(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $ac = $this->createMock(AllowanceCharge::class);
        $soiac->setAllowanceCharge($ac);
        $this->assertSame($ac, $soiac->getAllowanceCharge());
        $soiac->setAllowanceCharge(null);
        $this->assertNull($soiac->getAllowanceCharge());
    }

    public function testSalesOrderRelationSetterAndGetter(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $so = $this->createMock(SalesOrder::class);
        $soiac->setSalesOrder($so);
        $this->assertSame($so, $soiac->getSalesOrder());
        $soiac->setSalesOrder(null);
        $this->assertNull($soiac->getSalesOrder());
    }

    public function testSalesOrderItemRelationSetterAndGetter(): void
    {
        $soiac = new SalesOrderItemAllowanceCharge();
        $item = $this->createMock(SalesOrderItem::class);
        $soiac->setSalesOrderItem($item);
        $this->assertSame($item, $soiac->getSalesOrderItem());
        $soiac->setSalesOrderItem(null);
        $this->assertNull($soiac->getSalesOrderItem());
    }
}
