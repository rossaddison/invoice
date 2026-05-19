<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SalesOrderEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $so = new SalesOrder();
        $this->assertFalse($so->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $so = new SalesOrder();
        $this->expectException(\LogicException::class);
        $so->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $so = new SalesOrder();
        $so->setId(1);
        $this->assertTrue($so->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $so = new SalesOrder();
        $so->setId(12);
        $this->assertSame(12, $so->reqId());
    }

    public function testConstructorInitialisesDateFields(): void
    {
        $so = new SalesOrder();
        $this->assertInstanceOf(DateTimeImmutable::class, $so->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $so->getDateModified());
        $this->assertInstanceOf(DateTimeImmutable::class, $so->getDateExpires());
    }

    public function testConstructorDefaults(): void
    {
        $so = new SalesOrder();
        $this->assertSame('', $so->getNumber());
        $this->assertSame('', $so->getClientPoNumber());
        $this->assertSame('', $so->getClientPoLineNumber());
        $this->assertSame('', $so->getClientPoPerson());
        $this->assertSame(0.00, $so->getDiscountAmount());
        $this->assertSame('', $so->getUrlKey());
        $this->assertSame('', $so->getPassword());
        $this->assertSame('', $so->getNotes());
        $this->assertSame('', $so->getPaymentTerm());
        $this->assertNull($so->getClient());
        $this->assertNull($so->getGroup());
        $this->assertNull($so->getUser());
    }

    public function testSetAndGetNumber(): void
    {
        $so = new SalesOrder();
        $so->setNumber('SO-0001');
        $this->assertSame('SO-0001', $so->getNumber());
    }

    public function testSetAndGetClientPoFields(): void
    {
        $so = new SalesOrder();
        $so->setClientPoNumber('PO-123');
        $so->setClientPoLineNumber('LINE-1');
        $so->setClientPoPerson('John Buyer');
        $this->assertSame('PO-123', $so->getClientPoNumber());
        $this->assertSame('LINE-1', $so->getClientPoLineNumber());
        $this->assertSame('John Buyer', $so->getClientPoPerson());
    }

    public function testSetAndGetDiscountAmount(): void
    {
        $so = new SalesOrder();
        $so->setDiscountAmount(10.00);
        $this->assertSame(10.00, $so->getDiscountAmount());
    }

    public function testSetAndGetPaymentTerm(): void
    {
        $so = new SalesOrder();
        $so->setPaymentTerm('Net 30');
        $this->assertSame('Net 30', $so->getPaymentTerm());
    }

    public function testSetStatusIdValidValue(): void
    {
        $so = new SalesOrder();
        $so->setStatusId(2);
        $this->assertSame(2, $so->getStatusId());
    }

    public function testSetStatusIdInvalidValueClampsToOne(): void
    {
        $so = new SalesOrder();
        $so->setStatusId(99);
        $this->assertSame(1, $so->getStatusId());
    }

    public function testGetStatusReturnsLabel(): void
    {
        $so = new SalesOrder();
        $this->assertSame('draft', $so->getStatus(1));
        $this->assertSame('approved', $so->getStatus(4));
        $this->assertSame('cancelled', $so->getStatus(6));
        $this->assertSame('', $so->getStatus(99));
    }

    public function testSetDateExpiresAddsOneDay(): void
    {
        $so = new SalesOrder();
        $before = new DateTimeImmutable('now');
        $so->setDateExpires();
        $this->assertGreaterThan($before, $so->getDateExpires());
    }

    public function testSetAndGetDateCreated(): void
    {
        $so = new SalesOrder();
        $date = new DateTimeImmutable('2026-03-01');
        $so->setDateCreated($date);
        $this->assertSame($date, $so->getDateCreated());
    }

    public function testHasLinkedInvoiceReturnsFalseWhenNull(): void
    {
        $so = new SalesOrder();
        $this->assertFalse($so->hasLinkedInvoice());
    }

    public function testHasLinkedInvoiceReturnsTrueWhenSet(): void
    {
        $so = new SalesOrder();
        $so->setInvId(3);
        $this->assertTrue($so->hasLinkedInvoice());
    }

    public function testReqUserIdThrowsWhenNull(): void
    {
        $so = new SalesOrder();
        $this->expectException(\LogicException::class);
        $so->reqUserId();
    }

    public function testSetAndReqUserId(): void
    {
        $so = new SalesOrder();
        $so->setUserId(4);
        $this->assertSame(4, $so->reqUserId());
    }

    public function testReqClientIdThrowsWhenNull(): void
    {
        $so = new SalesOrder();
        $this->expectException(\LogicException::class);
        $so->reqClientId();
    }

    public function testSetAndReqClientId(): void
    {
        $so = new SalesOrder();
        $so->setClientId(6);
        $this->assertSame(6, $so->reqClientId());
    }

    public function testReqGroupIdThrowsWhenNull(): void
    {
        $so = new SalesOrder();
        $this->expectException(\LogicException::class);
        $so->reqGroupId();
    }

    public function testSetAndReqGroupId(): void
    {
        $so = new SalesOrder();
        $so->setGroupId(1);
        $this->assertSame(1, $so->reqGroupId());
    }

    public function testReqQuoteIdThrowsWhenNull(): void
    {
        $so = new SalesOrder();
        $this->expectException(\LogicException::class);
        $so->reqQuoteId();
    }

    public function testSetAndReqQuoteId(): void
    {
        $so = new SalesOrder();
        $so->setQuoteId(5);
        $this->assertSame(5, $so->reqQuoteId());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $so = new SalesOrder();
        $this->expectException(\LogicException::class);
        $so->reqInvId();
    }

    public function testSetAndReqInvId(): void
    {
        $so = new SalesOrder();
        $so->setInvId(8);
        $this->assertSame(8, $so->reqInvId());
    }
}
