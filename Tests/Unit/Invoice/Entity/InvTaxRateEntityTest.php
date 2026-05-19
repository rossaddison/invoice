<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\InvTaxRate\InvTaxRate;
use PHPUnit\Framework\TestCase;

class InvTaxRateEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $itr = new InvTaxRate();
        $this->assertFalse($itr->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $itr = new InvTaxRate();
        $this->expectException(\LogicException::class);
        $itr->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $itr = new InvTaxRate();
        $itr->setId(1);
        $this->assertTrue($itr->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $itr = new InvTaxRate();
        $itr->setId(11);
        $this->assertSame(11, $itr->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $itr = new InvTaxRate();
        $this->assertNull($itr->getIncludeItemTax());
        $this->assertSame(0.00, $itr->getInvTaxRateAmount());
        $this->assertNull($itr->getInv());
        $this->assertNull($itr->getTaxRate());
    }

    public function testSetAndGetIncludeItemTax(): void
    {
        $itr = new InvTaxRate();
        $itr->setIncludeItemTax(1);
        $this->assertSame(1, $itr->getIncludeItemTax());
    }

    public function testSetAndGetInvTaxRateAmount(): void
    {
        $itr = new InvTaxRate();
        $itr->setInvTaxRateAmount(95.00);
        $this->assertSame(95.00, $itr->getInvTaxRateAmount());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $itr = new InvTaxRate();
        $this->expectException(\LogicException::class);
        $itr->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $itr = new InvTaxRate();
        $itr->setInvId(6);
        $this->assertSame(6, $itr->reqInvId());
    }

    public function testReqTaxRateIdThrowsWhenNull(): void
    {
        $itr = new InvTaxRate();
        $this->expectException(\LogicException::class);
        $itr->reqTaxRateId();
    }

    public function testSetAndReqTaxRateId(): void
    {
        $itr = new InvTaxRate();
        $itr->setTaxRateId(3);
        $this->assertSame(3, $itr->reqTaxRateId());
    }
}
