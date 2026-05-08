<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteCustom\QuoteCustom;
use PHPUnit\Framework\TestCase;

class QuoteCustomEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $qc = new QuoteCustom();
        $this->assertFalse($qc->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $qc = new QuoteCustom();
        $this->expectException(\LogicException::class);
        $qc->reqId();
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $qc = new QuoteCustom();
        $qc->setId(8);
        $this->assertTrue($qc->hasIdentity());
        $this->assertSame(8, $qc->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $qc = new QuoteCustom();
        $qc->setId(1);
        $this->assertIsInt($qc->reqId());
    }

    public function testReqQuoteIdThrowsWhenNotSet(): void
    {
        $qc = new QuoteCustom();
        $this->expectException(\LogicException::class);
        $qc->reqQuoteId();
    }

    public function testSetQuoteIdAndReqQuoteId(): void
    {
        $qc = new QuoteCustom();
        $qc->setQuoteId(20);
        $this->assertSame(20, $qc->reqQuoteId());
    }

    public function testCustomFieldIdThrowsWhenNotSet(): void
    {
        $qc = new QuoteCustom();
        $this->expectException(\LogicException::class);
        $qc->reqCustomFieldId();
    }

    public function testCustomFieldIdSetterAndGetter(): void
    {
        $qc = new QuoteCustom();
        $qc->setCustomFieldId(7);
        $this->assertSame(7, $qc->reqCustomFieldId());
    }

    public function testValueIsEmptyStringByDefault(): void
    {
        $qc = new QuoteCustom();
        $this->assertSame('', $qc->getValue());
    }

    public function testValueSetterAndGetter(): void
    {
        $qc = new QuoteCustom();
        $qc->setValue('custom text');
        $this->assertSame('custom text', $qc->getValue());
    }

    public function testQuoteRelationSetterAndGetter(): void
    {
        $qc = new QuoteCustom();
        $quote = $this->createMock(Quote::class);
        $qc->setQuote($quote);
        $this->assertSame($quote, $qc->getQuote());
        $qc->setQuote(null);
        $this->assertNull($qc->getQuote());
    }

    public function testCustomFieldRelationSetterAndGetter(): void
    {
        $qc = new QuoteCustom();
        $field = $this->createMock(CustomField::class);
        $qc->setCustomField($field);
        $this->assertSame($field, $qc->getCustomField());
        $qc->setCustomField(null);
        $this->assertNull($qc->getCustomField());
    }
}
