<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use PHPUnit\Framework\TestCase;

class InvItemAmountEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $ia = new InvItemAmount();
        $this->assertFalse($ia->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $ia = new InvItemAmount();
        $this->expectException(\LogicException::class);
        $ia->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $ia = new InvItemAmount();
        $ia->setId(1);
        $this->assertTrue($ia->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $ia = new InvItemAmount();
        $ia->setId(3);
        $this->assertSame(3, $ia->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $ia = new InvItemAmount();
        $this->assertSame(0.00, $ia->getSubtotal());
        $this->assertSame(0.00, $ia->getTaxTotal());
        $this->assertSame(0.00, $ia->getDiscount());
        $this->assertSame(0.00, $ia->getCharge());
        $this->assertSame(0.00, $ia->getAllowance());
        $this->assertSame(0.00, $ia->getTotal());
        $this->assertNull($ia->getInvItem());
    }

    public function testSetAndGetSubtotal(): void
    {
        $ia = new InvItemAmount();
        $ia->setSubtotal(100.00);
        $this->assertSame(100.00, $ia->getSubtotal());
    }

    public function testSetAndGetTaxTotal(): void
    {
        $ia = new InvItemAmount();
        $ia->setTaxTotal(20.00);
        $this->assertSame(20.00, $ia->getTaxTotal());
    }

    public function testSetAndGetDiscount(): void
    {
        $ia = new InvItemAmount();
        $ia->setDiscount(5.00);
        $this->assertSame(5.00, $ia->getDiscount());
    }

    public function testSetAndGetChargeAndAllowance(): void
    {
        $ia = new InvItemAmount();
        $ia->setCharge(3.00);
        $ia->setAllowance(2.00);
        $this->assertSame(3.00, $ia->getCharge());
        $this->assertSame(2.00, $ia->getAllowance());
    }

    public function testSetAndGetTotal(): void
    {
        $ia = new InvItemAmount();
        $ia->setTotal(118.00);
        $this->assertSame(118.00, $ia->getTotal());
    }

    public function testReqInvItemIdThrowsWhenNull(): void
    {
        $ia = new InvItemAmount();
        $this->expectException(\LogicException::class);
        $ia->reqInvItemId();
    }

    public function testSetAndReqInvItemId(): void
    {
        $ia = new InvItemAmount();
        $ia->setInvItemId(5);
        $this->assertSame(5, $ia->reqInvItemId());
    }
}
