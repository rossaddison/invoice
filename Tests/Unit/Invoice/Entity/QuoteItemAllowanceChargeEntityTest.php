<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\QuoteItemAllowanceCharge\QuoteItemAllowanceCharge;
use PHPUnit\Framework\TestCase;

class QuoteItemAllowanceChargeEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $this->assertFalse($qiac->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $qiac->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $qiac->setId(15);
        $this->assertTrue($qiac->isPersisted());
        $this->assertSame(15, $qiac->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $qiac->setId(1);
        $this->assertIsInt($qiac->reqId());
    }

    public function testReqQuoteIdThrowsWhenNotSet(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $qiac->reqQuoteId();
    }

    public function testSetQuoteIdAndReqQuoteId(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $qiac->setQuoteId(5);
        $this->assertSame(5, $qiac->reqQuoteId());
    }

    public function testReqQuoteItemIdThrowsWhenNotSet(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $qiac->reqQuoteItemId();
    }

    public function testSetQuoteItemIdAndReqQuoteItemId(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $qiac->setQuoteItemId(8);
        $this->assertSame(8, $qiac->reqQuoteItemId());
    }

    public function testReqAllowanceChargeIdThrowsWhenNotSet(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $this->expectException(\LogicException::class);
        $qiac->reqAllowanceChargeId();
    }

    public function testSetAllowanceChargeIdAndReqAllowanceChargeId(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $qiac->setAllowanceChargeId(2);
        $this->assertSame(2, $qiac->reqAllowanceChargeId());
    }

    public function testAmountReturnsEmptyStringByDefault(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $this->assertSame('', $qiac->getAmount());
    }

    public function testAmountSetterAndGetter(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $qiac->setAmount(99.99);
        $this->assertSame('99.99', $qiac->getAmount());
    }

    public function testVatOrTaxReturnsEmptyStringByDefault(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $this->assertSame('', $qiac->getVatOrTax());
    }

    public function testVatOrTaxSetterAndGetter(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $qiac->setVatOrTax(20.00);
        $this->assertSame('20', $qiac->getVatOrTax());
    }

    public function testQuoteRelationSetterAndGetter(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $quote = $this->createMock(Quote::class);
        $qiac->setQuote($quote);
        $this->assertSame($quote, $qiac->getQuote());
        $qiac->setQuote(null);
        $this->assertNull($qiac->getQuote());
    }

    public function testQuoteItemRelationSetterAndGetter(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $quoteItem = $this->createMock(QuoteItem::class);
        $qiac->setQuoteItem($quoteItem);
        $this->assertSame($quoteItem, $qiac->getQuoteItem());
        $qiac->setQuoteItem(null);
        $this->assertNull($qiac->getQuoteItem());
    }

    public function testAllowanceChargeRelationSetterAndGetter(): void
    {
        $qiac = new QuoteItemAllowanceCharge();
        $ac = $this->createMock(AllowanceCharge::class);
        $qiac->setAllowanceCharge($ac);
        $this->assertSame($ac, $qiac->getAllowanceCharge());
        $qiac->setAllowanceCharge(null);
        $this->assertNull($qiac->getAllowanceCharge());
    }
}
