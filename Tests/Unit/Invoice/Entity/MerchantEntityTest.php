<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Merchant\Merchant;
use PHPUnit\Framework\TestCase;

class MerchantEntityTest extends TestCase
{
    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $m = new Merchant();
        $this->expectException(\LogicException::class);
        $m->reqId();
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $m = new Merchant();
        $m->setId(3);
        $this->assertSame(3, $m->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $m = new Merchant();
        $this->assertSame('', $m->getDriver());
        $this->assertSame('', $m->getResponse());
        $this->assertSame('', $m->getReference());
        $this->assertTrue($m->getSuccessful());
        $this->assertNull($m->getInv());
    }

    public function testSetAndGetDriver(): void
    {
        $m = new Merchant();
        $m->setDriver('stripe');
        $this->assertSame('stripe', $m->getDriver());
    }

    public function testSetAndGetResponse(): void
    {
        $m = new Merchant();
        $m->setResponse('{"status":"ok"}');
        $this->assertSame('{"status":"ok"}', $m->getResponse());
    }

    public function testSetAndGetReference(): void
    {
        $m = new Merchant();
        $m->setReference('ch_abc123');
        $this->assertSame('ch_abc123', $m->getReference());
    }

    public function testSetAndGetSuccessful(): void
    {
        $m = new Merchant();
        $m->setSuccessful(false);
        $this->assertFalse($m->getSuccessful());
        $m->setSuccessful(true);
        $this->assertTrue($m->getSuccessful());
    }

    public function testDateDefaultsToEmptyString(): void
    {
        $m = new Merchant();
        $this->assertSame('', $m->getDate());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $m = new Merchant();
        $this->expectException(\LogicException::class);
        $m->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $m = new Merchant();
        $m->setInvId(7);
        $this->assertSame(7, $m->reqInvId());
    }
}
