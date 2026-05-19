<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate;
use PHPUnit\Framework\TestCase;

class SalesOrderTaxRateEntityTest extends TestCase
{
    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $s = new SalesOrderTaxRate();
        $this->expectException(\LogicException::class);
        $s->reqId();
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $s = new SalesOrderTaxRate();
        $s->setId(2);
        $this->assertSame(2, $s->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $s = new SalesOrderTaxRate();
        $this->assertNull($s->getIncludeItemTax());
        $this->assertSame(0.00, $s->getSalesOrderTaxRateAmount());
        $this->assertNull($s->getSalesOrder());
        $this->assertNull($s->getTaxRate());
    }

    public function testSetAndGetIncludeItemTax(): void
    {
        $s = new SalesOrderTaxRate();
        $s->setIncludeItemTax(1);
        $this->assertSame(1, $s->getIncludeItemTax());
    }

    public function testSetAndGetSalesOrderTaxRateAmount(): void
    {
        $s = new SalesOrderTaxRate();
        $s->setSalesOrderTaxRateAmount(19.50);
        $this->assertSame(19.50, $s->getSalesOrderTaxRateAmount());
    }

    public function testSetSalesOrderIdAndReqTaxRateId(): void
    {
        $s = new SalesOrderTaxRate();
        $s->setSalesOrderId(10);
        $s->setTaxRateId(3);
        $this->assertSame(3, $s->reqTaxRateId());
    }

    public function testReqTaxRateIdThrowsWhenNull(): void
    {
        $s = new SalesOrderTaxRate();
        $this->expectException(\LogicException::class);
        $s->reqTaxRateId();
    }
}
