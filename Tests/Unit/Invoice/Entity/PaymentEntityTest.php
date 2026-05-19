<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Payment\Payment;
use PHPUnit\Framework\TestCase;

class PaymentEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $p = new Payment();
        $this->assertFalse($p->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $p = new Payment();
        $this->expectException(\LogicException::class);
        $p->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $p = new Payment();
        $p->setId(1);
        $this->assertTrue($p->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $p = new Payment();
        $p->setId(15);
        $this->assertSame(15, $p->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $p = new Payment();
        $this->assertSame(0.00, $p->getAmount());
        $this->assertSame('', $p->getNote());
        $this->assertNull($p->getInv());
        $this->assertNull($p->getPaymentMethod());
    }

    public function testSetAndGetAmount(): void
    {
        $p = new Payment();
        $p->setAmount(250.00);
        $this->assertSame(250.00, $p->getAmount());
    }

    public function testSetAndGetNote(): void
    {
        $p = new Payment();
        $p->setNote('Bank transfer ref: XYZ123');
        $this->assertSame('Bank transfer ref: XYZ123', $p->getNote());
    }

    public function testPaymentDateDefaultsToEmptyString(): void
    {
        $p = new Payment();
        $this->assertSame('', $p->getPaymentDate());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $p = new Payment();
        $this->expectException(\LogicException::class);
        $p->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $p = new Payment();
        $p->setInvId(4);
        $this->assertSame(4, $p->reqInvId());
    }

    public function testReqPaymentMethodIdThrowsWhenNull(): void
    {
        $p = new Payment();
        $this->expectException(\LogicException::class);
        $p->reqPaymentMethodId();
    }

    public function testSetAndReqPaymentMethodId(): void
    {
        $p = new Payment();
        $p->setPaymentMethodId(2);
        $this->assertSame(2, $p->reqPaymentMethodId());
    }
}
