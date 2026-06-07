<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PurchaseEntryEntityTest extends TestCase
{
    public function testDefaultConstructorNotPersisted(): void
    {
        $entry = new PurchaseEntry();

        $this->assertFalse($entry->isPersisted());
        $this->assertNull($entry->getDate());
        $this->assertSame('', $entry->getSupplier());
        $this->assertNull($entry->getDescription());
        $this->assertSame(0.00, $entry->getAmountExVat());
        $this->assertSame(0.00, $entry->getVatAmount());
        $this->assertInstanceOf(DateTimeImmutable::class, $entry->getCreatedAt());
    }

    public function testReqIdThrowsWhenUnpersisted(): void
    {
        $entry = new PurchaseEntry();

        $this->expectException(\LogicException::class);
        $entry->reqId();
    }

    public function testSetIdMakesPersisted(): void
    {
        $entry = new PurchaseEntry();
        $entry->setId(42);

        $this->assertTrue($entry->isPersisted());
        $this->assertSame(42, $entry->reqId());
        $this->assertIsInt($entry->reqId());
    }

    public function testGetIdReturnsNullBeforePersist(): void
    {
        $entry = new PurchaseEntry();

        $this->assertNull($entry->getId());
    }

    public function testGetIdAfterSetId(): void
    {
        $entry = new PurchaseEntry();
        $entry->setId(7);

        $this->assertSame(7, $entry->getId());
    }

    public function testDateSetterAndGetter(): void
    {
        $entry = new PurchaseEntry();
        $date  = new DateTimeImmutable('2026-01-15');
        $entry->setDate($date);

        $result = $entry->getDate();
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2026-01-15', $result->format('Y-m-d'));
    }

    public function testDateSetterAcceptsNull(): void
    {
        $entry = new PurchaseEntry();
        $entry->setDate(null);

        $this->assertNull($entry->getDate());
    }

    public function testSupplierSetterAndGetter(): void
    {
        $entry = new PurchaseEntry();
        $entry->setSupplier('Office Supplies Ltd');

        $this->assertSame('Office Supplies Ltd', $entry->getSupplier());
    }

    public function testDescriptionNullable(): void
    {
        $entry = new PurchaseEntry();

        $entry->setDescription('Invoice #1234');
        $this->assertSame('Invoice #1234', $entry->getDescription());

        $entry->setDescription(null);
        $this->assertNull($entry->getDescription());
    }

    public function testAmountExVatSetterAndGetter(): void
    {
        $entry = new PurchaseEntry();
        $entry->setAmountExVat(120.50);

        $this->assertSame(120.50, $entry->getAmountExVat());
        $this->assertIsFloat($entry->getAmountExVat());
    }

    public function testVatAmountSetterAndGetter(): void
    {
        $entry = new PurchaseEntry();
        $entry->setVatAmount(24.10);

        $this->assertSame(24.10, $entry->getVatAmount());
        $this->assertIsFloat($entry->getVatAmount());
    }

    public function testCreatedAtSetterAndGetter(): void
    {
        $entry = new PurchaseEntry();
        $entry->setCreatedAt('2026-06-06 10:30:00');

        $this->assertInstanceOf(DateTimeImmutable::class, $entry->getCreatedAt());
        $this->assertSame('2026-06-06 10:30:00', $entry->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testZeroAmounts(): void
    {
        $entry = new PurchaseEntry();
        $entry->setAmountExVat(0.00);
        $entry->setVatAmount(0.00);

        $this->assertSame(0.00, $entry->getAmountExVat());
        $this->assertSame(0.00, $entry->getVatAmount());
    }

    public function testTypicalPurchaseInvoice(): void
    {
        $entry = new PurchaseEntry();
        $entry->setId(1);
        $date = new DateTimeImmutable('2026-01-05');
        $entry->setDate($date);
        $entry->setSupplier('Cloud Hosting Co');
        $entry->setDescription('Monthly hosting — Jan 2026');
        $entry->setAmountExVat(200.00);
        $entry->setVatAmount(40.00);
        $entry->setCreatedAt('2026-01-06 09:00:00');

        $this->assertTrue($entry->isPersisted());
        $this->assertSame(1, $entry->reqId());
        $result = $entry->getDate();
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2026-01-05', $result->format('Y-m-d'));
        $this->assertSame('Cloud Hosting Co', $entry->getSupplier());
        $this->assertSame('Monthly hosting — Jan 2026', $entry->getDescription());
        $this->assertSame(200.00, $entry->getAmountExVat());
        $this->assertSame(40.00, $entry->getVatAmount());
    }

    public function testLargeAmounts(): void
    {
        $entry = new PurchaseEntry();
        $entry->setAmountExVat(999999.99);
        $entry->setVatAmount(199999.99);

        $this->assertSame(999999.99, $entry->getAmountExVat());
        $this->assertSame(199999.99, $entry->getVatAmount());
    }

    public function testIsPersistedFalseBeforeSetId(): void
    {
        $entry = new PurchaseEntry();

        $this->assertFalse($entry->isPersisted());
    }

    public function testIsPersistedTrueAfterSetId(): void
    {
        $entry = new PurchaseEntry();
        $entry->setId(99);

        $this->assertTrue($entry->isPersisted());
    }
}
