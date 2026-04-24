<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\QuoteAllowanceCharge\QuoteAllowanceCharge;
use App\Invoice\Entity\Quote;
use PHPUnit\Framework\TestCase;

class QuoteAllowanceChargeEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseWhenIdIsNull(): void
    {
        $qac = new QuoteAllowanceCharge();
        $this->assertFalse($qac->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $qac = new QuoteAllowanceCharge();
        $this->expectException(\LogicException::class);
        $qac->reqId();
    }

    public function testConstructorWithAllParameters(): void
    {
        $qac = new QuoteAllowanceCharge(
            id: 1,
            quote_id: 5,
            allowance_charge_id: 3,
            amount: 50.00,
            vat_or_tax: 10.00,
        );

        $this->assertSame(1, $qac->reqId());
        $this->assertTrue($qac->isPersisted());
        $this->assertSame(5, $qac->getQuoteId());
        $this->assertSame(3, $qac->getAllowanceChargeId());
        $this->assertSame(50.00, $qac->getAmount());
        $this->assertSame(10.00, $qac->getVatOrTax());
    }

    public function testConstructorWithDefaults(): void
    {
        $qac = new QuoteAllowanceCharge();
        $this->assertNull($qac->getQuoteId());
        $this->assertNull($qac->getAllowanceChargeId());
        $this->assertNull($qac->getAmount());
        $this->assertNull($qac->getVatOrTax());
        $this->assertNull($qac->getAllowanceCharge());
        $this->assertNull($qac->getQuote());
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $qac = new QuoteAllowanceCharge();
        $this->assertFalse($qac->isPersisted());
        $qac->setId(25);
        $this->assertTrue($qac->isPersisted());
        $this->assertSame(25, $qac->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $qac = new QuoteAllowanceCharge(id: 1);
        $this->assertIsInt($qac->reqId());
    }

    public function testQuoteIdSetterAndGetter(): void
    {
        $qac = new QuoteAllowanceCharge();
        $qac->setQuoteId(10);
        $this->assertSame(10, $qac->getQuoteId());
    }

    public function testAllowanceChargeIdSetterAndGetter(): void
    {
        $qac = new QuoteAllowanceCharge();
        $qac->setAllowanceChargeId(7);
        $this->assertSame(7, $qac->getAllowanceChargeId());
    }

    public function testAmountSetterAndGetter(): void
    {
        $qac = new QuoteAllowanceCharge();
        $qac->setAmount(125.50);
        $this->assertSame(125.50, $qac->getAmount());
    }

    public function testVatOrTaxSetterAndGetter(): void
    {
        $qac = new QuoteAllowanceCharge();
        $qac->setVatOrTax(25.00);
        $this->assertSame(25.00, $qac->getVatOrTax());
    }

    public function testAllowanceChargeRelationSetterAndGetter(): void
    {
        $qac = new QuoteAllowanceCharge();
        $allowanceCharge = $this->createMock(AllowanceCharge::class);

        $qac->setAllowanceCharge($allowanceCharge);
        $this->assertSame($allowanceCharge, $qac->getAllowanceCharge());

        $qac->setAllowanceCharge(null);
        $this->assertNull($qac->getAllowanceCharge());
    }

    public function testQuoteRelationSetterAndGetter(): void
    {
        $qac = new QuoteAllowanceCharge();
        $quote = $this->createMock(Quote::class);

        $qac->setQuote($quote);
        $this->assertSame($quote, $qac->getQuote());

        $qac->setQuote(null);
        $this->assertNull($qac->getQuote());
    }

    public function testZeroAmounts(): void
    {
        $qac = new QuoteAllowanceCharge(
            id: 1,
            quote_id: 1,
            allowance_charge_id: 1,
            amount: 0.00,
            vat_or_tax: 0.00,
        );

        $this->assertSame(0.00, $qac->getAmount());
        $this->assertSame(0.00, $qac->getVatOrTax());
    }

    public function testHighPrecisionAmounts(): void
    {
        $qac = new QuoteAllowanceCharge();
        $qac->setAmount(99999.99);
        $qac->setVatOrTax(19999.99);
        $this->assertSame(99999.99, $qac->getAmount());
        $this->assertSame(19999.99, $qac->getVatOrTax());
    }
}
