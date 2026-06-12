<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\As4Receipt\As4Receipt;
use PHPUnit\Framework\TestCase;
use DateTime;

class As4ReceiptEntityTest extends TestCase
{
    private function makeReceipt(): As4Receipt
    {
        return new As4Receipt(
            receiptMessageId: 'receipt-msg-001@as4.example.com',
            refToMessageId: 'orig-msg-001@as4.example.com',
            digestValue: 'abc123digestvalue==',
            originSender: '0088:1234567890123',
            originReceiver: '0088:9876543210987',
            receiptXml: '<eb:SignalMessage/>'
        );
    }

    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $this->assertFalse($this->makeReceipt()->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $this->expectException(\LogicException::class);
        $this->makeReceipt()->reqId();
    }

    public function testSetIdMakesPersisted(): void
    {
        $receipt = $this->makeReceipt();
        $receipt->setId(1);
        $this->assertTrue($receipt->isPersisted());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $receipt = $this->makeReceipt();
        $receipt->setId(7);
        $this->assertIsInt($receipt->reqId());
        $this->assertSame(7, $receipt->reqId());
    }

    public function testConstructorAssignsAllFields(): void
    {
        $receipt = $this->makeReceipt();

        $this->assertSame('receipt-msg-001@as4.example.com', $receipt->getReceiptMessageId());
        $this->assertSame('orig-msg-001@as4.example.com', $receipt->getRefToMessageId());
        $this->assertSame('abc123digestvalue==', $receipt->getDigestValue());
        $this->assertSame('0088:1234567890123', $receipt->getOriginSender());
        $this->assertSame('0088:9876543210987', $receipt->getOriginReceiver());
        $this->assertSame('<eb:SignalMessage/>', $receipt->getReceiptXml());
        $this->assertTrue($receipt->isSigned());
    }

    public function testReceivedAtAndCreatedAtAreSetOnConstruction(): void
    {
        $before = new DateTime();
        $receipt = $this->makeReceipt();
        $after = new DateTime();

        $this->assertGreaterThanOrEqual($before, $receipt->getReceivedAt());
        $this->assertLessThanOrEqual($after, $receipt->getReceivedAt());
        $this->assertGreaterThanOrEqual($before, $receipt->getCreatedAt());
        $this->assertLessThanOrEqual($after, $receipt->getCreatedAt());
    }

    public function testReturnTypes(): void
    {
        $receipt = $this->makeReceipt();
        $receipt->setId(1);

        $this->assertIsInt($receipt->reqId());
        $this->assertIsString($receipt->getReceiptMessageId());
        $this->assertIsString($receipt->getRefToMessageId());
        $this->assertIsString($receipt->getDigestValue());
        $this->assertIsString($receipt->getOriginSender());
        $this->assertIsString($receipt->getOriginReceiver());
        $this->assertIsString($receipt->getReceiptXml());
        $this->assertIsBool($receipt->isSigned());
        $this->assertInstanceOf(DateTime::class, $receipt->getReceivedAt());
        $this->assertInstanceOf(DateTime::class, $receipt->getCreatedAt());
    }
}
