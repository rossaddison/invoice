<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use PHPUnit\Framework\TestCase;

class QuoteAmountEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $qa = new QuoteAmount();
        $this->assertFalse($qa->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $qa = new QuoteAmount();
        $this->expectException(\LogicException::class);
        $qa->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $qa = new QuoteAmount();
        $qa->setId(4);
        $this->assertTrue($qa->hasIdentity());
        $this->assertSame(4, $qa->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $qa = new QuoteAmount();
        $qa->setId(1);
        $this->assertIsInt($qa->reqId());
    }

    public function testReqQuoteIdThrowsWhenNotSet(): void
    {
        $qa = new QuoteAmount();
        $this->expectException(\LogicException::class);
        $qa->reqQuoteId();
    }

    public function testSetQuoteIdAndReqQuoteId(): void
    {
        $qa = new QuoteAmount();
        $qa->setQuoteId(99);
        $this->assertSame(99, $qa->reqQuoteId());
    }

    public function testConstructorDefaults(): void
    {
        $qa = new QuoteAmount();
        $this->assertSame(0.00, $qa->getItemSubtotal());
        $this->assertSame(0.00, $qa->getItemTaxTotal());
        $this->assertSame(0.00, $qa->getPackhandleshipTotal());
        $this->assertSame(0.00, $qa->getPackhandleshipTax());
        $this->assertSame(0.00, $qa->getTaxTotal());
        $this->assertSame(0.00, $qa->getTotal());
        $this->assertNull($qa->getQuote());
    }

    public function testItemSubtotalSetterAndGetter(): void
    {
        $qa = new QuoteAmount();
        $qa->setItemSubtotal(150.50);
        $this->assertSame(150.50, $qa->getItemSubtotal());
    }

    public function testItemTaxTotalSetterAndGetter(): void
    {
        $qa = new QuoteAmount();
        $qa->setItemTaxTotal(30.00);
        $this->assertSame(30.00, $qa->getItemTaxTotal());
    }

    public function testPackhandleshipTotalSetterAndGetter(): void
    {
        $qa = new QuoteAmount();
        $qa->setPackhandleshipTotal(10.00);
        $this->assertSame(10.00, $qa->getPackhandleshipTotal());
    }

    public function testPackhandleshipTaxSetterAndGetter(): void
    {
        $qa = new QuoteAmount();
        $qa->setPackhandleshipTax(2.00);
        $this->assertSame(2.00, $qa->getPackhandleshipTax());
    }

    public function testTaxTotalSetterAndGetter(): void
    {
        $qa = new QuoteAmount();
        $qa->setTaxTotal(45.00);
        $this->assertSame(45.00, $qa->getTaxTotal());
    }

    public function testTotalSetterAndGetter(): void
    {
        $qa = new QuoteAmount();
        $qa->setTotal(200.00);
        $this->assertSame(200.00, $qa->getTotal());
    }

    public function testQuoteRelationSetterAndGetter(): void
    {
        $qa = new QuoteAmount();
        $quote = $this->createMock(Quote::class);
        $qa->setQuote($quote);
        $this->assertSame($quote, $qa->getQuote());
        $qa->setQuote(null);
        $this->assertNull($qa->getQuote());
    }
}
