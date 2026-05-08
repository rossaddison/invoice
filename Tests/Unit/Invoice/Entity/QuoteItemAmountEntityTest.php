<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use PHPUnit\Framework\TestCase;

class QuoteItemAmountEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $amount = new QuoteItemAmount();
        $this->assertFalse($amount->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $amount = new QuoteItemAmount();
        $this->expectException(\LogicException::class);
        $amount->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setId(10);
        $this->assertTrue($amount->hasIdentity());
        $this->assertSame(10, $amount->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setId(1);
        $this->assertIsInt($amount->reqId());
    }

    public function testConstructorWithDefaults(): void
    {
        $amount = new QuoteItemAmount();
        $this->assertSame(0.00, $amount->getSubtotal());
        $this->assertSame(0.00, $amount->getTaxTotal());
        $this->assertSame(0.00, $amount->getDiscount());
        $this->assertSame(0.00, $amount->getCharge());
        $this->assertSame(0.00, $amount->getAllowance());
        $this->assertSame(0.00, $amount->getTotal());
        $this->assertNull($amount->getQuoteItem());
    }

    public function testConstructorWithAllParameters(): void
    {
        $amount = new QuoteItemAmount(
            quote_item_id: 5,
            subtotal: 100.00,
            tax_total: 20.00,
            discount: 5.00,
            charge: 2.50,
            allowance: 1.00,
            total: 116.50,
        );

        $this->assertSame(5, $amount->reqQuoteItemId());
        $this->assertSame(100.00, $amount->getSubtotal());
        $this->assertSame(20.00, $amount->getTaxTotal());
        $this->assertSame(5.00, $amount->getDiscount());
        $this->assertSame(2.50, $amount->getCharge());
        $this->assertSame(1.00, $amount->getAllowance());
        $this->assertSame(116.50, $amount->getTotal());
    }

    public function testQuoteItemIdSetterAndGetter(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setQuoteItemId(99);
        $this->assertSame(99, $amount->reqQuoteItemId());
    }

    public function testSubtotalSetterAndGetter(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setSubtotal(250.75);
        $this->assertSame(250.75, $amount->getSubtotal());
    }

    public function testTaxTotalSetterAndGetter(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setTaxTotal(50.00);
        $this->assertSame(50.00, $amount->getTaxTotal());
    }

    public function testDiscountSetterAndGetter(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setDiscount(10.00);
        $this->assertSame(10.00, $amount->getDiscount());
    }

    public function testChargeSetterAndGetter(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setCharge(5.50);
        $this->assertSame(5.50, $amount->getCharge());
    }

    public function testAllowanceSetterAndGetter(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setAllowance(2.25);
        $this->assertSame(2.25, $amount->getAllowance());
    }

    public function testTotalSetterAndGetter(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setTotal(300.00);
        $this->assertSame(300.00, $amount->getTotal());
    }

    public function testQuoteItemRelationSetterAndGetter(): void
    {
        $amount = new QuoteItemAmount();
        $quoteItem = $this->createMock(QuoteItem::class);

        $amount->setQuoteItem($quoteItem);
        $this->assertSame($quoteItem, $amount->getQuoteItem());

        $amount->setQuoteItem(null);
        $this->assertNull($amount->getQuoteItem());
    }

    public function testZeroAmountsAreValid(): void
    {
        $amount = new QuoteItemAmount(
            subtotal: 0.00,
            tax_total: 0.00,
            discount: 0.00,
            charge: 0.00,
            allowance: 0.00,
            total: 0.00,
        );

        $this->assertSame(0.00, $amount->getSubtotal());
        $this->assertSame(0.00, $amount->getTotal());
    }

    public function testHighPrecisionAmounts(): void
    {
        $amount = new QuoteItemAmount();
        $amount->setSubtotal(1234567.89);
        $this->assertSame(1234567.89, $amount->getSubtotal());
    }

    public function testQuoteItemIdIsReturnedAsInt(): void
    {
        $amount = new QuoteItemAmount(quote_item_id: 42);
        $this->assertIsInt($amount->reqQuoteItemId());
        $this->assertSame(42, $amount->reqQuoteItemId());
    }
}
