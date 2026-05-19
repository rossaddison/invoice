<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use PHPUnit\Framework\TestCase;

class SalesOrderAmountEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $sa = new SalesOrderAmount();
        $this->assertFalse($sa->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $sa = new SalesOrderAmount();
        $this->expectException(\LogicException::class);
        $sa->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setId(6);
        $this->assertTrue($sa->hasIdentity());
        $this->assertSame(6, $sa->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setId(1);
        $this->assertIsInt($sa->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $sa = new SalesOrderAmount();
        $this->assertNull($sa->getSalesOrderId());
        $this->assertSame(0.00, $sa->getItemSubtotal());
        $this->assertSame(0.00, $sa->getItemTaxTotal());
        $this->assertSame(0.00, $sa->getPackhandleshipTotal());
        $this->assertSame(0.00, $sa->getPackhandleshipTax());
        $this->assertSame(0.00, $sa->getTaxTotal());
        $this->assertSame(0.00, $sa->getTotal());
        $this->assertNull($sa->getSalesOrder());
    }

    public function testSalesOrderIdSetterAndGetter(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setSalesOrderId(42);
        $this->assertSame(42, $sa->getSalesOrderId());
    }

    public function testItemSubtotalSetterAndGetter(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setItemSubtotal(300.00);
        $this->assertSame(300.00, $sa->getItemSubtotal());
    }

    public function testItemTaxTotalSetterAndGetter(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setItemTaxTotal(60.00);
        $this->assertSame(60.00, $sa->getItemTaxTotal());
    }

    public function testPackhandleshipTotalSetterAndGetter(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setPackhandleshipTotal(15.00);
        $this->assertSame(15.00, $sa->getPackhandleshipTotal());
    }

    public function testPackhandleshipTaxSetterAndGetter(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setPackhandleshipTax(3.00);
        $this->assertSame(3.00, $sa->getPackhandleshipTax());
    }

    public function testTaxTotalSetterAndGetter(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setTaxTotal(75.00);
        $this->assertSame(75.00, $sa->getTaxTotal());
    }

    public function testTotalSetterAndGetter(): void
    {
        $sa = new SalesOrderAmount();
        $sa->setTotal(400.00);
        $this->assertSame(400.00, $sa->getTotal());
    }

    public function testSalesOrderRelationSetterAndGetter(): void
    {
        $sa = new SalesOrderAmount();
        $so = $this->createStub(SalesOrder::class);
        $sa->setSalesOrder($so);
        $this->assertSame($so, $sa->getSalesOrder());
        $sa->setSalesOrder(null);
        $this->assertNull($sa->getSalesOrder());
    }
}
