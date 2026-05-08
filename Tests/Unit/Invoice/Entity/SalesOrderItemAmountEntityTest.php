<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\SalesOrderItemAmount\SalesOrderItemAmount;
use PHPUnit\Framework\TestCase;

class SalesOrderItemAmountEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $amount = new SalesOrderItemAmount();
        $this->assertFalse($amount->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $amount = new SalesOrderItemAmount();
        $this->expectException(\LogicException::class);
        $amount->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setId(15);
        $this->assertTrue($amount->hasIdentity());
        $this->assertSame(15, $amount->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setId(1);
        $this->assertIsInt($amount->reqId());
    }

    public function testConstructorWithDefaults(): void
    {
        $amount = new SalesOrderItemAmount();
        $this->assertSame(0.00, $amount->getSubtotal());
        $this->assertSame(0.00, $amount->getTaxTotal());
        $this->assertSame(0.00, $amount->getDiscount());
        $this->assertSame(0.00, $amount->getCharge());
        $this->assertSame(0.00, $amount->getAllowance());
        $this->assertSame(0.00, $amount->getTotal());
        $this->assertNull($amount->getSalesOrderItem());
    }

    public function testConstructorWithAllParameters(): void
    {
        $amount = new SalesOrderItemAmount(
            sales_order_item_id: 8,
            subtotal: 200.00,
            tax_total: 40.00,
            discount: 10.00,
            charge: 5.00,
            allowance: 2.00,
            total: 233.00,
        );

        $this->assertSame(8, $amount->reqSalesOrderItemId());
        $this->assertSame(200.00, $amount->getSubtotal());
        $this->assertSame(40.00, $amount->getTaxTotal());
        $this->assertSame(10.00, $amount->getDiscount());
        $this->assertSame(5.00, $amount->getCharge());
        $this->assertSame(2.00, $amount->getAllowance());
        $this->assertSame(233.00, $amount->getTotal());
    }

    public function testSalesOrderItemIdSetterAndGetter(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setSalesOrderItemId(77);
        $this->assertSame(77, $amount->reqSalesOrderItemId());
    }

    public function testSalesOrderItemIdIsReturnedAsString(): void
    {
        $amount = new SalesOrderItemAmount(sales_order_item_id: 33);
        $this->assertIsInt($amount->reqSalesOrderItemId());
        $this->assertSame(33, $amount->reqSalesOrderItemId());
    }

    public function testSubtotalSetterAndGetter(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setSubtotal(500.50);
        $this->assertSame(500.50, $amount->getSubtotal());
    }

    public function testTaxTotalSetterAndGetter(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setTaxTotal(100.00);
        $this->assertSame(100.00, $amount->getTaxTotal());
    }

    public function testDiscountSetterAndGetter(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setDiscount(15.00);
        $this->assertSame(15.00, $amount->getDiscount());
    }

    public function testChargeSetterAndGetter(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setCharge(7.50);
        $this->assertSame(7.50, $amount->getCharge());
    }

    public function testAllowanceSetterAndGetter(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setAllowance(3.25);
        $this->assertSame(3.25, $amount->getAllowance());
    }

    public function testTotalSetterAndGetter(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setTotal(585.75);
        $this->assertSame(585.75, $amount->getTotal());
    }

    public function testSalesOrderItemRelationSetterAndGetter(): void
    {
        $amount = new SalesOrderItemAmount();
        $salesOrderItem = $this->createMock(SalesOrderItem::class);

        $amount->setSalesOrderItem($salesOrderItem);
        $this->assertSame($salesOrderItem, $amount->getSalesOrderItem());

        $amount->setSalesOrderItem(null);
        $this->assertNull($amount->getSalesOrderItem());
    }

    public function testZeroAmountsAreValid(): void
    {
        $amount = new SalesOrderItemAmount(
            subtotal: 0.00,
            tax_total: 0.00,
            discount: 0.00,
            charge: 0.00,
            allowance: 0.00,
            total: 0.00,
        );

        $this->assertSame(0.00, $amount->getSubtotal());
        $this->assertSame(0.00, $amount->getTotal());
    }

    public function testHighPrecisionAmounts(): void
    {
        $amount = new SalesOrderItemAmount();
        $amount->setSubtotal(9999999.99);
        $this->assertSame(9999999.99, $amount->getSubtotal());
    }
}
