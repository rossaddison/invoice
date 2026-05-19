<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\InvAmount\InvAmount;
use PHPUnit\Framework\TestCase;

class InvAmountEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $ia = new InvAmount();
        $this->assertFalse($ia->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $ia = new InvAmount();
        $this->expectException(\LogicException::class);
        $ia->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $ia = new InvAmount();
        $ia->setId(1);
        $this->assertTrue($ia->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $ia = new InvAmount();
        $ia->setId(12);
        $this->assertSame(12, $ia->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $ia = new InvAmount();
        $this->assertSame(1, $ia->getSign());
        $this->assertSame(0.00, $ia->getItemSubtotal());
        $this->assertSame(0.00, $ia->getItemTaxTotal());
        $this->assertSame(0.00, $ia->getPackhandleshipTotal());
        $this->assertSame(0.00, $ia->getPackhandleshipTax());
        $this->assertSame(0.00, $ia->getTaxTotal());
        $this->assertSame(0.00, $ia->getTotal());
        $this->assertSame(0.00, $ia->getPaid());
        $this->assertSame(0.00, $ia->getBalance());
        $this->assertNull($ia->getInv());
    }

    public function testSetAndGetItemSubtotal(): void
    {
        $ia = new InvAmount();
        $ia->setItemSubtotal(500.00);
        $this->assertSame(500.00, $ia->getItemSubtotal());
    }

    public function testSetAndGetItemTaxTotal(): void
    {
        $ia = new InvAmount();
        $ia->setItemTaxTotal(95.00);
        $this->assertSame(95.00, $ia->getItemTaxTotal());
    }

    public function testSetAndGetTotal(): void
    {
        $ia = new InvAmount();
        $ia->setTotal(595.00);
        $this->assertSame(595.00, $ia->getTotal());
    }

    public function testSetAndGetPaidAndBalance(): void
    {
        $ia = new InvAmount();
        $ia->setTotal(595.00);
        $ia->setPaid(200.00);
        $ia->setBalance(395.00);
        $this->assertSame(200.00, $ia->getPaid());
        $this->assertSame(395.00, $ia->getBalance());
    }

    public function testSetAndGetSign(): void
    {
        $ia = new InvAmount();
        $ia->setSign(-1);
        $this->assertSame(-1, $ia->getSign());
    }

    public function testSetAndGetPackhandleshipFields(): void
    {
        $ia = new InvAmount();
        $ia->setPackhandleshipTotal(25.00);
        $ia->setPackhandleshipTax(5.00);
        $this->assertSame(25.00, $ia->getPackhandleshipTotal());
        $this->assertSame(5.00, $ia->getPackhandleshipTax());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $ia = new InvAmount();
        $this->expectException(\LogicException::class);
        $ia->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $ia = new InvAmount();
        $ia->setInvId(9);
        $this->assertSame(9, $ia->reqInvId());
    }
}
