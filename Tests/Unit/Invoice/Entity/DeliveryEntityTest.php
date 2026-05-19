<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Delivery\Delivery;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DeliveryEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $d = new Delivery();
        $this->assertFalse($d->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $d = new Delivery();
        $this->expectException(\LogicException::class);
        $d->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $d = new Delivery();
        $d->setId(1);
        $this->assertTrue($d->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $d = new Delivery();
        $d->setId(8);
        $this->assertSame(8, $d->reqId());
    }

    public function testConstructorInitialisesDateFields(): void
    {
        $d = new Delivery();
        $this->assertInstanceOf(DateTimeImmutable::class, $d->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $d->getDateModified());
        $this->assertInstanceOf(DateTimeImmutable::class, $d->getStartDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $d->getEndDate());
    }

    public function testSetAndGetInvId(): void
    {
        $d = new Delivery();
        $d->setInvId(5);
        $this->assertSame(5, $d->getInvId());
    }

    public function testSetAndGetInvItemId(): void
    {
        $d = new Delivery();
        $d->setInvItemId(3);
        $this->assertSame(3, $d->getInvItemId());
    }

    public function testSetAndGetStartDate(): void
    {
        $d = new Delivery();
        $date = new DateTimeImmutable('2026-06-01');
        $d->setStartDate($date);
        $this->assertSame($date, $d->getStartDate());
    }

    public function testSetAndGetEndDate(): void
    {
        $d = new Delivery();
        $date = new DateTimeImmutable('2026-06-30');
        $d->setEndDate($date);
        $this->assertSame($date, $d->getEndDate());
    }

    public function testSetAndGetActualDeliveryDate(): void
    {
        $d = new Delivery();
        $date = new DateTimeImmutable('2026-06-15');
        $d->setActualDeliveryDate($date);
        $this->assertSame($date, $d->getActualDeliveryDate());
    }

    public function testSetActualDeliveryDateAcceptsNull(): void
    {
        $d = new Delivery();
        $d->setActualDeliveryDate(null);
        $this->assertNull($d->getActualDeliveryDate());
    }

    public function testHasDeliveryLocationIdReturnsFalseByDefault(): void
    {
        $d = new Delivery();
        $this->assertFalse($d->hasDeliveryLocationId());
    }

    public function testSetAndReqDeliveryLocationId(): void
    {
        $d = new Delivery();
        $d->setDeliveryLocationId(2);
        $this->assertTrue($d->hasDeliveryLocationId());
        $this->assertSame(2, $d->reqDeliveryLocationId());
    }

    public function testHasDeliveryPartyIdReturnsFalseByDefault(): void
    {
        $d = new Delivery();
        $this->assertFalse($d->hasDeliveryPartyId());
    }

    public function testSetAndReqDeliveryPartyId(): void
    {
        $d = new Delivery();
        $d->setDeliveryPartyId(4);
        $this->assertTrue($d->hasDeliveryPartyId());
        $this->assertSame(4, $d->reqDeliveryPartyId());
    }
}
