<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\InvItem\InvItem;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class InvItemEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $item = new InvItem();
        $this->assertFalse($item->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $item = new InvItem();
        $this->expectException(\LogicException::class);
        $item->reqId();
    }

    public function testIsPersistedReturnsTrueAfterSetId(): void
    {
        $item = new InvItem();
        $item->setId(1);
        $this->assertTrue($item->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $item = new InvItem();
        $item->setId(42);
        $this->assertIsInt($item->reqId());
        $this->assertSame(42, $item->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $item = new InvItem();

        $this->assertFalse($item->hasIdentity());
        $this->assertSame('', $item->getName());
        $this->assertSame('', $item->getDescription());
        $this->assertNull($item->getQuantity());
        $this->assertNull($item->getPrice());
        $this->assertNull($item->getDiscountAmount());
        $this->assertNull($item->getOrder());
        $this->assertFalse($item->getIsRecurring());
        $this->assertSame('', $item->getProductUnit());
        $this->assertNull($item->getInv());
        $this->assertNull($item->getTaxRate());
        $this->assertNull($item->getProduct());
        $this->assertNull($item->getTask());
        $this->assertSame('', $item->getPeppolPoItemid());
        $this->assertSame('', $item->getPeppolPoLineid());
        $this->assertNull($item->getNote());
        $this->assertInstanceOf(DateTimeImmutable::class, $item->getDate());
    }

    public function testNameSetterAndGetter(): void
    {
        $item = new InvItem();
        $item->setName('Consulting Services');
        $this->assertSame('Consulting Services', $item->getName());
    }

    public function testDescriptionSetterAndGetter(): void
    {
        $item = new InvItem();
        $item->setDescription('Monthly retainer for software consulting');
        $this->assertSame('Monthly retainer for software consulting', $item->getDescription());
    }

    public function testQuantitySetterAndGetter(): void
    {
        $item = new InvItem();
        $item->setQuantity(5.0);
        $this->assertSame(5.0, $item->getQuantity());
    }

    public function testPriceSetterAndGetter(): void
    {
        $item = new InvItem();
        $item->setPrice(100.50);
        $this->assertSame(100.50, $item->getPrice());
    }

    public function testDiscountAmountSetterAndGetter(): void
    {
        $item = new InvItem();
        $item->setDiscountAmount(10.00);
        $this->assertSame(10.00, $item->getDiscountAmount());
    }

    public function testOrderSetterAndGetter(): void
    {
        $item = new InvItem();
        $item->setOrder(3);
        $this->assertSame(3, $item->getOrder());
    }

    public function testIsRecurringSetterAndGetter(): void
    {
        $item = new InvItem();
        $item->setIsRecurring(true);
        $this->assertTrue($item->getIsRecurring());

        $item->setIsRecurring(false);
        $this->assertFalse($item->getIsRecurring());
    }

    public function testProductUnitSetterAndGetter(): void
    {
        $item = new InvItem();
        $item->setProductUnit('EA');
        $this->assertSame('EA', $item->getProductUnit());
    }

    public function testInvIdSetterAndReqInvId(): void
    {
        $item = new InvItem();
        $item->setInvId(99);
        $this->assertSame(99, $item->reqInvId());
    }

    public function testReqInvIdThrowsWhenNull(): void
    {
        $item = new InvItem();
        $this->expectException(\LogicException::class);
        $item->reqInvId();
    }

    public function testSoItemIdSetterAndGetter(): void
    {
        $item = new InvItem();
        $this->assertNull($item->getSoItemId());

        $item->setSoItemId(77);
        $this->assertSame(77, $item->getSoItemId());
    }

    public function testTaxRateIdSetterAndReqTaxRateId(): void
    {
        $item = new InvItem();
        $item->setTaxRateId(2);
        $this->assertSame(2, $item->reqTaxRateId());
    }

    public function testProductIdSetterAndGetter(): void
    {
        $item = new InvItem();
        $this->assertNull($item->getProductId());

        $item->setProductId(10);
        $this->assertSame(10, $item->getProductId());
    }

    public function testTaskIdSetterAndGetter(): void
    {
        $item = new InvItem();
        $this->assertNull($item->getTaskId());

        $item->setTaskId(5);
        $this->assertSame(5, $item->getTaskId());
    }

    public function testProductUnitIdSetterAndGetter(): void
    {
        $item = new InvItem();
        $this->assertNull($item->getProductUnitId());

        $item->setProductUnitId(3);
        $this->assertSame(3, $item->getProductUnitId());
    }

    public function testDeliveryIdSetterAndGetter(): void
    {
        $item = new InvItem();
        $this->assertNull($item->getDeliveryId());

        $item->setDeliveryId(8);
        $this->assertSame(8, $item->getDeliveryId());
    }

    public function testNoteSetterAndGetter(): void
    {
        $item = new InvItem();
        $this->assertNull($item->getNote());

        $item->setNote('Internal note for this line item');
        $this->assertSame('Internal note for this line item', $item->getNote());
    }

    public function testDateSetterAndGetter(): void
    {
        $item = new InvItem();
        $date = new DateTimeImmutable('2026-05-19 12:00:00');
        $item->setDate($date);
        $this->assertSame($date, $item->getDate());
    }

    public function testPeppolPoItemidSetterAndGetter(): void
    {
        $item = new InvItem();
        $this->assertSame('', $item->getPeppolPoItemid());

        $item->setPeppolPoItemid('PO-ITEM-001');
        $this->assertSame('PO-ITEM-001', $item->getPeppolPoItemid());
    }

    public function testPeppolPoLineidSetterAndGetter(): void
    {
        $item = new InvItem();
        $this->assertSame('', $item->getPeppolPoLineid());

        $item->setPeppolPoLineid('LINE-001');
        $this->assertSame('LINE-001', $item->getPeppolPoLineid());
    }

    public function testPeppolFieldsDefaultToEmptyString(): void
    {
        $item = new InvItem();
        $this->assertIsString($item->getPeppolPoItemid());
        $this->assertIsString($item->getPeppolPoLineid());
        $this->assertSame('', $item->getPeppolPoItemid());
        $this->assertSame('', $item->getPeppolPoLineid());
    }

    public function testPeppolFieldsAcceptLongValues(): void
    {
        $item = new InvItem();

        $longItemId = str_repeat('A', 200);
        $longLineId = str_repeat('B', 200);

        $item->setPeppolPoItemid($longItemId);
        $item->setPeppolPoLineid($longLineId);

        $this->assertSame($longItemId, $item->getPeppolPoItemid());
        $this->assertSame($longLineId, $item->getPeppolPoLineid());
    }

    public function testPeppolFieldsViaConstructor(): void
    {
        $item = new InvItem(
            inv_id: 1,
            peppol_po_itemid: 'PO-42-ITEM-7',
            peppol_po_lineid: 'PO-42-LINE-7',
        );
        $item->setId(1);

        $this->assertSame(1, $item->reqId());
        $this->assertSame('PO-42-ITEM-7', $item->getPeppolPoItemid());
        $this->assertSame('PO-42-LINE-7', $item->getPeppolPoLineid());
    }

    public function testPeppolFieldsCopiedFromSalesOrder(): void
    {
        $item = new InvItem();
        $item->setInvId(10);
        $item->setSoItemId(55);
        $item->setPeppolPoItemid('SO-ITEM-REF-001');
        $item->setPeppolPoLineid('SO-LINE-REF-001');

        $this->assertSame(55, $item->getSoItemId());
        $this->assertSame('SO-ITEM-REF-001', $item->getPeppolPoItemid());
        $this->assertSame('SO-LINE-REF-001', $item->getPeppolPoLineid());
    }
}
