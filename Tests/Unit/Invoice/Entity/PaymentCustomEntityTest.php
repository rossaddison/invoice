<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\PaymentCustom\PaymentCustom;
use PHPUnit\Framework\TestCase;

class PaymentCustomEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $pc = new PaymentCustom();
        $this->assertFalse($pc->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $pc = new PaymentCustom();
        $this->expectException(\LogicException::class);
        $pc->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $pc = new PaymentCustom();
        $pc->setId(1);
        $this->assertTrue($pc->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $pc = new PaymentCustom();
        $pc->setId(4);
        $this->assertSame(4, $pc->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $pc = new PaymentCustom();
        $this->assertSame('', $pc->getValue());
        $this->assertNull($pc->getPayment());
        $this->assertNull($pc->getCustomField());
    }

    public function testSetAndGetValue(): void
    {
        $pc = new PaymentCustom();
        $pc->setValue('ref-abc');
        $this->assertSame('ref-abc', $pc->getValue());
    }

    public function testReqPaymentIdThrowsWhenNull(): void
    {
        $pc = new PaymentCustom();
        $this->expectException(\LogicException::class);
        $pc->reqPaymentId();
    }

    public function testSetAndReqPaymentId(): void
    {
        $pc = new PaymentCustom();
        $pc->setPaymentId(5);
        $this->assertSame(5, $pc->reqPaymentId());
    }

    public function testReqCustomFieldIdThrowsWhenNull(): void
    {
        $pc = new PaymentCustom();
        $this->expectException(\LogicException::class);
        $pc->reqCustomFieldId();
    }

    public function testSetAndReqCustomFieldId(): void
    {
        $pc = new PaymentCustom();
        $pc->setCustomFieldId(2);
        $this->assertSame(2, $pc->reqCustomFieldId());
    }
}
