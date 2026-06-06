<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\Inv\Inv;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use ReflectionProperty;

class InvEntityTest extends TestCase
{
    public function testNotDeletedByDefault(): void
    {
        $inv = new Inv();

        $this->assertFalse($inv->isDeleted());
    }

    public function testGetDeletedAtIsNullByDefault(): void
    {
        $inv = new Inv();

        $this->assertNull($inv->getDeletedAt());
    }

    public function testIsDeletedReturnsTrueWhenDeletedAtSet(): void
    {
        $inv = new Inv();
        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-10 12:00:00'));

        $this->assertTrue($inv->isDeleted());
    }

    public function testGetDeletedAtReturnsTimestampWhenSet(): void
    {
        $inv = new Inv();
        $timestamp = new DateTimeImmutable('2026-05-10 12:00:00');
        $this->setDeletedAt($inv, $timestamp);

        $this->assertSame($timestamp, $inv->getDeletedAt());
    }

    public function testIsDeletedReturnsFalseAfterDeletedAtCleared(): void
    {
        $inv = new Inv();
        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-10 12:00:00'));
        $this->assertTrue($inv->isDeleted());

        $this->setDeletedAt($inv, null);
        $this->assertFalse($inv->isDeleted());
    }

    public function testGetDeletedAtReturnsNullAfterCleared(): void
    {
        $inv = new Inv();
        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-10 12:00:00'));
        $this->setDeletedAt($inv, null);

        $this->assertNull($inv->getDeletedAt());
    }

    public function testGetDeletedAtReturnsDateTimeImmutable(): void
    {
        $inv = new Inv();
        $this->setDeletedAt($inv, new DateTimeImmutable());

        $this->assertInstanceOf(DateTimeImmutable::class, $inv->getDeletedAt());
    }

    public function testNotPersistedByDefault(): void
    {
        $inv = new Inv();

        $this->assertFalse($inv->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $inv = new Inv();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Inv not persisted');
        $inv->reqId();
    }

    public function testHasIdentityReturnsTrueWhenIdSet(): void
    {
        $inv = new Inv();
        $inv->setId(1);

        $this->assertTrue($inv->hasIdentity());
    }

    public function testReqIdReturnsIntWhenPersisted(): void
    {
        $inv = new Inv();
        $inv->setId(42);

        $this->assertSame(42, $inv->reqId());
        $this->assertIsInt($inv->reqId());
    }

    public function testSoftDeletedInvCanStillBeQueried(): void
    {
        $inv = new Inv();
        $inv->setId(7);
        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-10 09:00:00'));

        $this->assertTrue($inv->isDeleted());
        $this->assertSame(7, $inv->reqId());
        $this->assertInstanceOf(DateTimeImmutable::class, $inv->getDeletedAt());
    }

    public function testRestoreNullifiesDeletedAt(): void
    {
        $inv = new Inv();
        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-10 12:00:00'));

        $inv->restore();

        $this->assertNull($inv->getDeletedAt());
    }

    public function testRestoreIsDeletedReturnsFalse(): void
    {
        $inv = new Inv();
        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-10 12:00:00'));
        $this->assertTrue($inv->isDeleted());

        $inv->restore();

        $this->assertFalse($inv->isDeleted());
    }

    public function testRestoreIsIdempotentOnFreshInv(): void
    {
        $inv = new Inv();

        $inv->restore();

        $this->assertFalse($inv->isDeleted());
        $this->assertNull($inv->getDeletedAt());
    }

    public function testRestorePreservesId(): void
    {
        $inv = new Inv();
        $inv->setId(99);
        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-10 12:00:00'));

        $inv->restore();

        $this->assertSame(99, $inv->reqId());
        $this->assertFalse($inv->isDeleted());
    }

    public function testCanSoftDeleteAndRestoreMultipleTimes(): void
    {
        $inv = new Inv();

        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-10 09:00:00'));
        $this->assertTrue($inv->isDeleted());

        $inv->restore();
        $this->assertFalse($inv->isDeleted());

        $this->setDeletedAt($inv, new DateTimeImmutable('2026-05-11 10:00:00'));
        $this->assertTrue($inv->isDeleted());

        $inv->restore();
        $this->assertFalse($inv->isDeleted());
    }

    public function testClientPoNumberDefaultsToEmpty(): void
    {
        $inv = new Inv();

        $this->assertSame('', $inv->getClientPoNumber());
    }

    public function testSetAndGetClientPoNumber(): void
    {
        $inv = new Inv();
        $inv->setClientPoNumber('PO-2026-001');

        $this->assertSame('PO-2026-001', $inv->getClientPoNumber());
    }

    public function testClientPoNumberIsString(): void
    {
        $inv = new Inv();
        $inv->setClientPoNumber('PO-XYZ');

        $this->assertIsString($inv->getClientPoNumber());
    }

    public function testClientPoPersonDefaultsToEmpty(): void
    {
        $inv = new Inv();

        $this->assertSame('', $inv->getClientPoPerson());
    }

    public function testSetAndGetClientPoPerson(): void
    {
        $inv = new Inv();
        $inv->setClientPoPerson('Jane Smith');

        $this->assertSame('Jane Smith', $inv->getClientPoPerson());
    }

    public function testClientPoPersonIsString(): void
    {
        $inv = new Inv();
        $inv->setClientPoPerson('J. Doe');

        $this->assertIsString($inv->getClientPoPerson());
    }

    /**
     * Simulates what the ORM SoftDelete behavior does at the persistence layer.
     */
    private function setDeletedAt(Inv $inv, ?DateTimeImmutable $value): void
    {
        $prop = new ReflectionProperty(Inv::class, 'deleted_at');
        $prop->setValue($inv, $value);
    }
}
