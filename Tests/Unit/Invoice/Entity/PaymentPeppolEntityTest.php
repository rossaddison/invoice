<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\PaymentPeppol\PaymentPeppol;
use PHPUnit\Framework\TestCase;

class PaymentPeppolEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $pp = new PaymentPeppol();
        $this->assertFalse($pp->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $pp = new PaymentPeppol();
        $this->expectException(\LogicException::class);
        $pp->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $pp = new PaymentPeppol();
        $pp->setId(1);
        $this->assertTrue($pp->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $pp = new PaymentPeppol();
        $pp->setId(9);
        $this->assertSame(9, $pp->reqId());
    }

    public function testConstructorInitialisesAutoReference(): void
    {
        $pp = new PaymentPeppol();
        $this->assertIsInt($pp->getAutoReference());
        $this->assertGreaterThan(0, $pp->getAutoReference());
    }

    public function testConstructorDefaults(): void
    {
        $pp = new PaymentPeppol();
        $this->assertSame('', $pp->getProvider());
        $this->assertNull($pp->getInv());
    }

    public function testSetAndGetProvider(): void
    {
        $pp = new PaymentPeppol();
        $pp->setProvider('oxalis');
        $this->assertSame('oxalis', $pp->getProvider());
    }

    public function testSetAndGetAutoReference(): void
    {
        $pp = new PaymentPeppol();
        $pp->setAutoReference(1716076800);
        $this->assertSame(1716076800, $pp->getAutoReference());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $pp = new PaymentPeppol();
        $this->expectException(\LogicException::class);
        $pp->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $pp = new PaymentPeppol();
        $pp->setInvId(3);
        $this->assertSame(3, $pp->reqInvId());
    }
}
