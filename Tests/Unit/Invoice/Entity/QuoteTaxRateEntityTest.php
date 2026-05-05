<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use PHPUnit\Framework\TestCase;

class QuoteTaxRateEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $qtr = new QuoteTaxRate();
        $this->assertFalse($qtr->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $qtr = new QuoteTaxRate();
        $this->expectException(\LogicException::class);
        $qtr->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $qtr = new QuoteTaxRate();
        $qtr->setId(5);
        $this->assertTrue($qtr->hasIdentity());
        $this->assertSame(5, $qtr->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $qtr = new QuoteTaxRate();
        $qtr->setId(1);
        $this->assertIsInt($qtr->reqId());
    }

    public function testReqQuoteIdThrowsWhenNotSet(): void
    {
        $qtr = new QuoteTaxRate();
        $this->expectException(\LogicException::class);
        $qtr->reqQuoteId();
    }

    public function testSetQuoteIdAndReqQuoteId(): void
    {
        $qtr = new QuoteTaxRate();
        $qtr->setQuoteId(10);
        $this->assertSame(10, $qtr->reqQuoteId());
    }

    public function testReqTaxRateIdThrowsWhenNotSet(): void
    {
        $qtr = new QuoteTaxRate();
        $this->expectException(\LogicException::class);
        $qtr->reqTaxRateId();
    }

    public function testSetTaxRateIdAndReqTaxRateId(): void
    {
        $qtr = new QuoteTaxRate();
        $qtr->setTaxRateId(3);
        $this->assertSame(3, $qtr->reqTaxRateId());
    }

    public function testIncludeItemTaxIsNullByDefault(): void
    {
        $qtr = new QuoteTaxRate();
        $this->assertNull($qtr->getIncludeItemTax());
    }

    public function testIncludeItemTaxSetterAndGetter(): void
    {
        $qtr = new QuoteTaxRate();
        $qtr->setIncludeItemTax(1);
        $this->assertSame(1, $qtr->getIncludeItemTax());
    }

    public function testQuoteTaxRateAmountDefaultsToZero(): void
    {
        $qtr = new QuoteTaxRate();
        $this->assertSame(0.00, $qtr->getQuoteTaxRateAmount());
    }

    public function testQuoteTaxRateAmountSetterAndGetter(): void
    {
        $qtr = new QuoteTaxRate();
        $qtr->setQuoteTaxRateAmount(55.75);
        $this->assertSame(55.75, $qtr->getQuoteTaxRateAmount());
    }

    public function testQuoteRelationSetterAndGetter(): void
    {
        $qtr = new QuoteTaxRate();
        $quote = $this->createMock(Quote::class);
        $qtr->setQuote($quote);
        $this->assertSame($quote, $qtr->getQuote());
        $qtr->setQuote(null);
        $this->assertNull($qtr->getQuote());
    }

    public function testTaxRateRelationSetterAndGetter(): void
    {
        $qtr = new QuoteTaxRate();
        $taxRate = $this->createMock(TaxRate::class);
        $qtr->setTaxRate($taxRate);
        $this->assertSame($taxRate, $qtr->getTaxRate());
        $qtr->setTaxRate(null);
        $this->assertNull($qtr->getTaxRate());
    }
}
