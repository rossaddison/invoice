<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Quote\Quote;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class QuoteEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $q = new Quote();
        $this->assertFalse($q->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $q = new Quote();
        $this->expectException(\LogicException::class);
        $q->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $q = new Quote();
        $q->setId(1);
        $this->assertTrue($q->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $q = new Quote();
        $q->setId(9);
        $this->assertSame(9, $q->reqId());
    }

    public function testConstructorInitialisesDateFields(): void
    {
        $q = new Quote();
        $this->assertInstanceOf(DateTimeImmutable::class, $q->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $q->getDateModified());
        $this->assertInstanceOf(DateTimeImmutable::class, $q->getDateExpires());
        $this->assertInstanceOf(DateTimeImmutable::class, $q->getDateRequired());
    }

    public function testConstructorDefaults(): void
    {
        $q = new Quote();
        $this->assertSame('', $q->getNumber());
        $this->assertSame(0.00, $q->getDiscountAmount());
        $this->assertSame('', $q->getUrlKey());
        $this->assertSame('', $q->getPassword());
        $this->assertSame('', $q->getNotes());
        $this->assertNull($q->getClient());
        $this->assertNull($q->getGroup());
        $this->assertNull($q->getUser());
        $this->assertNull($q->getSoId());
        $this->assertNull($q->getInvId());
        $this->assertNull($q->getDeliveryLocationId());
    }

    public function testSetAndGetNumber(): void
    {
        $q = new Quote();
        $q->setNumber('QT-0001');
        $this->assertSame('QT-0001', $q->getNumber());
    }

    public function testSetAndGetNotes(): void
    {
        $q = new Quote();
        $q->setNotes('Please pay within 30 days.');
        $this->assertSame('Please pay within 30 days.', $q->getNotes());
    }

    public function testSetAndGetDiscountAmount(): void
    {
        $q = new Quote();
        $q->setDiscountAmount(15.50);
        $this->assertSame(15.50, $q->getDiscountAmount());
    }

    public function testSetAndGetUrlKey(): void
    {
        $q = new Quote();
        $q->setUrlKey('abc123xyz');
        $this->assertSame('abc123xyz', $q->getUrlKey());
    }

    public function testSetAndGetPassword(): void
    {
        $q = new Quote();
        $q->setPassword('secret');
        $this->assertSame('secret', $q->getPassword());
    }

    public function testSetStatusIdValidValue(): void
    {
        $q = new Quote();
        $q->setStatusId(3);
        $this->assertSame(3, $q->reqStatusId());
    }

    public function testSetStatusIdInvalidValueClampsToOne(): void
    {
        $q = new Quote();
        $q->setStatusId(99);
        $this->assertSame(1, $q->reqStatusId());
    }

    public function testGetStatusReturnsLabel(): void
    {
        $q = new Quote();
        $this->assertSame('draft', $q->getStatus(1));
        $this->assertSame('sent', $q->getStatus(2));
        $this->assertSame('approved', $q->getStatus(4));
        $this->assertSame('', $q->getStatus(99));
    }

    public function testSetAndGetDateCreated(): void
    {
        $q = new Quote();
        $date = new DateTimeImmutable('2026-01-15');
        $q->setDateCreated($date);
        $this->assertSame($date, $q->getDateCreated());
    }

    public function testSetAndGetDateRequired(): void
    {
        $q = new Quote();
        $date = new DateTimeImmutable('2026-02-28');
        $q->setDateRequired($date);
        $this->assertSame($date, $q->getDateRequired());
    }

    public function testReqUserIdThrowsWhenNull(): void
    {
        $q = new Quote();
        $this->expectException(\LogicException::class);
        $q->reqUserId();
    }

    public function testSetAndReqUserId(): void
    {
        $q = new Quote();
        $q->setUserId(5);
        $this->assertSame(5, $q->reqUserId());
    }

    public function testReqClientIdThrowsWhenNull(): void
    {
        $q = new Quote();
        $this->expectException(\LogicException::class);
        $q->reqClientId();
    }

    public function testSetAndReqClientId(): void
    {
        $q = new Quote();
        $q->setClientId(3);
        $this->assertSame(3, $q->reqClientId());
    }

    public function testReqGroupIdThrowsWhenNull(): void
    {
        $q = new Quote();
        $this->expectException(\LogicException::class);
        $q->reqGroupId();
    }

    public function testSetAndReqGroupId(): void
    {
        $q = new Quote();
        $q->setGroupId(2);
        $this->assertSame(2, $q->reqGroupId());
    }

    public function testReqContractIdThrowsWhenNull(): void
    {
        $q = new Quote();
        $this->expectException(\LogicException::class);
        $q->reqContractId();
    }

    public function testSetAndReqContractId(): void
    {
        $q = new Quote();
        $q->setContractId(7);
        $this->assertSame(7, $q->reqContractId());
    }

    public function testSetAndGetSoId(): void
    {
        $q = new Quote();
        $q->setSoId(4);
        $this->assertSame(4, $q->getSoId());
    }

    public function testSetAndGetInvId(): void
    {
        $q = new Quote();
        $q->setInvId(6);
        $this->assertSame(6, $q->getInvId());
    }

    public function testSetAndGetDeliveryLocationId(): void
    {
        $q = new Quote();
        $q->setDeliveryLocationId(8);
        $this->assertSame(8, $q->getDeliveryLocationId());
    }
}
