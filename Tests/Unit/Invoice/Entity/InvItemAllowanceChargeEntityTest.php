<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use PHPUnit\Framework\TestCase;

class InvItemAllowanceChargeEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $ac = new InvItemAllowanceCharge();
        $this->assertFalse($ac->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $ac = new InvItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $ac->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $ac = new InvItemAllowanceCharge();
        $ac->setId(1);
        $this->assertTrue($ac->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $ac = new InvItemAllowanceCharge();
        $ac->setId(6);
        $this->assertSame(6, $ac->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $ac = new InvItemAllowanceCharge();
        $this->assertNull($ac->getAllowanceCharge());
        $this->assertNull($ac->getInv());
        $this->assertNull($ac->getInvItem());
    }

    public function testSetAndGetAmount(): void
    {
        $ac = new InvItemAllowanceCharge();
        $ac->setAmount(10.50);
        $this->assertSame('10.5', $ac->getAmount());
    }

    public function testSetAndGetVatOrTax(): void
    {
        $ac = new InvItemAllowanceCharge();
        $ac->setVatOrTax(2.00);
        $this->assertSame('2', $ac->getVatOrTax());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $ac = new InvItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $ac->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $ac = new InvItemAllowanceCharge();
        $ac->setInvId(4);
        $this->assertSame(4, $ac->reqInvId());
    }

    public function testReqInvItemIdThrowsWhenNull(): void
    {
        $ac = new InvItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $ac->reqInvItemId();
    }

    public function testSetAndReqInvItemId(): void
    {
        $ac = new InvItemAllowanceCharge();
        $ac->setInvItemId(8);
        $this->assertSame(8, $ac->reqInvItemId());
    }

    public function testReqAllowanceChargeIdThrowsWhenNull(): void
    {
        $ac = new InvItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $ac->reqAllowanceChargeId();
    }

    public function testSetAndReqAllowanceChargeId(): void
    {
        $ac = new InvItemAllowanceCharge();
        $ac->setAllowanceChargeId(2);
        $this->assertSame(2, $ac->reqAllowanceChargeId());
    }
}
