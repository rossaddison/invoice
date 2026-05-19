<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SalesOrderItemEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $soi = new SalesOrderItem();
        $this->assertFalse($soi->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $soi = new SalesOrderItem();
        $this->expectException(\LogicException::class);
        $soi->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $soi = new SalesOrderItem();
        $soi->setId(1);
        $this->assertTrue($soi->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $soi = new SalesOrderItem();
        $soi->setId(8);
        $this->assertSame(8, $soi->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $soi = new SalesOrderItem();
        $this->assertSame('', $soi->getName());
        $this->assertSame('', $soi->getDescription());
        $this->assertSame(1.00, $soi->getQuantity());
        $this->assertSame(0.00, $soi->getPrice());
        $this->assertSame(0.00, $soi->getDiscountAmount());
        $this->assertNull($soi->getProduct());
        $this->assertNull($soi->getTask());
        $this->assertNull($soi->getSalesOrder());
        $this->assertInstanceOf(DateTimeImmutable::class, $soi->getDateAdded());
    }

    public function testSetAndGetName(): void
    {
        $soi = new SalesOrderItem();
        $soi->setName('Widget A');
        $this->assertSame('Widget A', $soi->getName());
    }

    public function testSetAndGetQuantityAndPrice(): void
    {
        $soi = new SalesOrderItem();
        $soi->setQuantity(5.0);
        $soi->setPrice(20.00);
        $this->assertSame(5.0, $soi->getQuantity());
        $this->assertSame(20.00, $soi->getPrice());
    }

    public function testSetAndGetPeppolFields(): void
    {
        $soi = new SalesOrderItem();
        $soi->setPeppolPoItemid('ITEM-001');
        $soi->setPeppolPoLineid('LINE-001');
        $this->assertSame('ITEM-001', $soi->getPeppolPoItemid());
        $this->assertSame('LINE-001', $soi->getPeppolPoLineid());
    }

    public function testReqSalesOrderIdThrowsWhenNull(): void
    {
        $soi = new SalesOrderItem();
        $this->expectException(\LogicException::class);
        $soi->reqSalesOrderId();
    }

    public function testSetAndReqSalesOrderId(): void
    {
        $soi = new SalesOrderItem();
        $soi->setSalesOrderId(3);
        $this->assertSame(3, $soi->reqSalesOrderId());
    }

    public function testSetAndGetDateAdded(): void
    {
        $soi = new SalesOrderItem();
        $date = new DateTimeImmutable('2026-01-01');
        $soi->setDateAdded($date);
        $this->assertSame($date, $soi->getDateAdded());
    }
}
