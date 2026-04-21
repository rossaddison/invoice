<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderAllowanceCharge\SalesOrderAllowanceCharge;
use PHPUnit\Framework\TestCase;

class SalesOrderAllowanceChargeEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseWhenIdIsNull(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $this->assertFalse($soac->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $this->expectException(\LogicException::class);
        $soac->reqId();
    }

    public function testConstructorWithAllParameters(): void
    {
        $soac = new SalesOrderAllowanceCharge(
            id: 1,
            sales_order_id: 5,
            allowance_charge_id: 3,
            amount: 50.00,
            vat_or_tax: 10.00,
        );

        $this->assertSame(1, $soac->reqId());
        $this->assertTrue($soac->isPersisted());
        $this->assertSame(5, $soac->getSalesOrderId());
        $this->assertSame(3, $soac->getAllowanceChargeId());
        $this->assertSame(50.00, $soac->getAmount());
        $this->assertSame(10.00, $soac->getVatOrTax());
    }

    public function testConstructorWithDefaults(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $this->assertNull($soac->getSalesOrderId());
        $this->assertNull($soac->getAllowanceChargeId());
        $this->assertNull($soac->getAmount());
        $this->assertNull($soac->getVatOrTax());
        $this->assertNull($soac->getAllowanceCharge());
        $this->assertNull($soac->getSalesOrder());
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $this->assertFalse($soac->isPersisted());
        $soac->setId(25);
        $this->assertTrue($soac->isPersisted());
        $this->assertSame(25, $soac->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $soac = new SalesOrderAllowanceCharge(id: 1);
        $this->assertIsInt($soac->reqId());
    }

    public function testSalesOrderIdSetterAndGetter(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $soac->setSalesOrderId(10);
        $this->assertSame(10, $soac->getSalesOrderId());
    }

    public function testAllowanceChargeIdSetterAndGetter(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $soac->setAllowanceChargeId(7);
        $this->assertSame(7, $soac->getAllowanceChargeId());
    }

    public function testAmountSetterAndGetter(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $soac->setAmount(125.50);
        $this->assertSame(125.50, $soac->getAmount());
    }

    public function testVatOrTaxSetterAndGetter(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $soac->setVatOrTax(25.00);
        $this->assertSame(25.00, $soac->getVatOrTax());
    }

    public function testAllowanceChargeRelationSetterAndGetter(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $allowanceCharge = $this->createMock(AllowanceCharge::class);

        $soac->setAllowanceCharge($allowanceCharge);
        $this->assertSame($allowanceCharge, $soac->getAllowanceCharge());

        $soac->setAllowanceCharge(null);
        $this->assertNull($soac->getAllowanceCharge());
    }

    public function testSalesOrderRelationSetterAndGetter(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $salesOrder = $this->createMock(SalesOrder::class);

        $soac->setSalesOrder($salesOrder);
        $this->assertSame($salesOrder, $soac->getSalesOrder());

        $soac->setSalesOrder(null);
        $this->assertNull($soac->getSalesOrder());
    }

    public function testZeroAmounts(): void
    {
        $soac = new SalesOrderAllowanceCharge(
            id: 1,
            sales_order_id: 1,
            allowance_charge_id: 1,
            amount: 0.00,
            vat_or_tax: 0.00,
        );

        $this->assertSame(0.00, $soac->getAmount());
        $this->assertSame(0.00, $soac->getVatOrTax());
    }

    public function testHighPrecisionAmounts(): void
    {
        $soac = new SalesOrderAllowanceCharge();
        $soac->setAmount(99999.99);
        $soac->setVatOrTax(19999.99);
        $this->assertSame(99999.99, $soac->getAmount());
        $this->assertSame(19999.99, $soac->getVatOrTax());
    }
}
