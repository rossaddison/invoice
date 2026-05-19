<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class QuoteItemEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $qi = new QuoteItem();
        $this->assertFalse($qi->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $qi = new QuoteItem();
        $this->expectException(\LogicException::class);
        $qi->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $qi = new QuoteItem();
        $qi->setId(1);
        $this->assertTrue($qi->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $qi = new QuoteItem();
        $qi->setId(7);
        $this->assertSame(7, $qi->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $qi = new QuoteItem();
        $this->assertSame('', $qi->getName());
        $this->assertSame('', $qi->getDescription());
        $this->assertNull($qi->getQuantity());
        $this->assertNull($qi->getPrice());
        $this->assertNull($qi->getDiscountAmount());
        $this->assertNull($qi->getProductId());
        $this->assertNull($qi->getTaskId());
        $this->assertNull($qi->getProduct());
        $this->assertNull($qi->getTask());
        $this->assertNull($qi->getQuote());
        $this->assertNull($qi->getTaxRate());
        $this->assertInstanceOf(DateTimeImmutable::class, $qi->getDateAdded());
    }

    public function testSetAndGetName(): void
    {
        $qi = new QuoteItem();
        $qi->setName('Consulting services');
        $this->assertSame('Consulting services', $qi->getName());
    }

    public function testSetAndGetDescription(): void
    {
        $qi = new QuoteItem();
        $qi->setDescription('10 hours at standard rate');
        $this->assertSame('10 hours at standard rate', $qi->getDescription());
    }

    public function testSetAndGetQuantityAndPrice(): void
    {
        $qi = new QuoteItem();
        $qi->setQuantity(10.0);
        $qi->setPrice(150.00);
        $this->assertSame(10.0, $qi->getQuantity());
        $this->assertSame(150.00, $qi->getPrice());
    }

    public function testSetAndGetDiscountAmount(): void
    {
        $qi = new QuoteItem();
        $qi->setDiscountAmount(15.00);
        $this->assertSame(15.00, $qi->getDiscountAmount());
    }

    public function testSetAndGetOrder(): void
    {
        $qi = new QuoteItem();
        $qi->setOrder(2);
        $this->assertSame(2, $qi->getOrder());
    }

    public function testSetAndGetProductId(): void
    {
        $qi = new QuoteItem();
        $qi->setProductId(4);
        $this->assertSame(4, $qi->getProductId());
    }

    public function testSetAndGetTaskId(): void
    {
        $qi = new QuoteItem();
        $qi->setTaskId(5);
        $this->assertSame(5, $qi->getTaskId());
    }

    public function testReqQuoteIdThrowsWhenNull(): void
    {
        $qi = new QuoteItem();
        $this->expectException(\LogicException::class);
        $qi->reqQuoteId();
    }

    public function testSetAndReqQuoteId(): void
    {
        $qi = new QuoteItem();
        $qi->setQuoteId(3);
        $this->assertSame(3, $qi->reqQuoteId());
    }

    public function testReqTaxRateIdThrowsWhenNull(): void
    {
        $qi = new QuoteItem();
        $this->expectException(\LogicException::class);
        $qi->reqTaxRateId();
    }

    public function testSetAndReqTaxRateId(): void
    {
        $qi = new QuoteItem();
        $qi->setTaxRateId(2);
        $this->assertSame(2, $qi->reqTaxRateId());
    }
}
