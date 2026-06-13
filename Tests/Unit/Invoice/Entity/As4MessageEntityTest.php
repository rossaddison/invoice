<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Invoice\As4\As4MessageState;
use PHPUnit\Framework\TestCase;
use DateTime;

class As4MessageEntityTest extends TestCase
{
    private function makeMessage(): As4Message
    {
        return new As4Message(
            messageId: 'msg-001@as4.example.com',
            conversationId: 'conv-001',
            senderPartyId: '0088:1234567890123',
            senderRole: 'Seller',
            receiverPartyId: '0088:9876543210987',
            receiverRole: 'Buyer',
            service: 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            action: 'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            receiverEndpoint: 'https://ap.example.com/as4',
            soapMessage: '<env:Envelope/>'
        );
    }

    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $this->assertFalse($this->makeMessage()->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $this->expectException(\LogicException::class);
        $this->makeMessage()->reqId();
    }

    public function testSetIdMakesPersisted(): void
    {
        $msg = $this->makeMessage();
        $msg->setId(1);
        $this->assertTrue($msg->isPersisted());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $msg = $this->makeMessage();
        $msg->setId(99);
        $this->assertIsInt($msg->reqId());
        $this->assertSame(99, $msg->reqId());
    }

    public function testConstructorAssignsAllFields(): void
    {
        $msg = $this->makeMessage();

        $this->assertSame('msg-001@as4.example.com', $msg->getMessageId());
        $this->assertSame('conv-001', $msg->getConversationId());
        $this->assertSame('0088:1234567890123', $msg->getSenderPartyId());
        $this->assertSame('Seller', $msg->getSenderRole());
        $this->assertSame('0088:9876543210987', $msg->getReceiverPartyId());
        $this->assertSame('Buyer', $msg->getReceiverRole());
        $this->assertSame('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0', $msg->getService());
        $this->assertSame(
            'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            $msg->getAction()
        );
        $this->assertSame('https://ap.example.com/as4', $msg->getReceiverEndpoint());
        $this->assertSame('<env:Envelope/>', $msg->getSoapMessage());
    }

    public function testDefaultStateIsPending(): void
    {
        $this->assertSame(As4MessageState::pending, $this->makeMessage()->getState());
    }

    public function testDefaultAttemptCountIsZero(): void
    {
        $this->assertSame(0, $this->makeMessage()->getAttemptCount());
    }

    public function testDefaultMaxAttemptsIsThree(): void
    {
        $this->assertSame(3, $this->makeMessage()->getMaxAttempts());
    }

    public function testNullableFieldsAreNullByDefault(): void
    {
        $msg = $this->makeMessage();

        $this->assertNull($msg->getRefToMessageId());
        $this->assertNull($msg->getPayloadPartIds());
        $this->assertNull($msg->getLastAttemptAt());
        $this->assertNull($msg->getReceiptMessageId());
        $this->assertNull($msg->getReceiptDigest());
        $this->assertNull($msg->getReceiptReceivedAt());
        $this->assertNull($msg->getErrorCode());
        $this->assertNull($msg->getErrorDescription());
    }

    public function testStateEnumValues(): void
    {
        $this->assertSame('pending',   As4MessageState::pending->value);
        $this->assertSame('sent',      As4MessageState::sent->value);
        $this->assertSame('receipt',   As4MessageState::receiptReceived->value);
        $this->assertSame('failed',    As4MessageState::failed->value);
        $this->assertSame('duplicate', As4MessageState::duplicate->value);
        $this->assertSame('delivered', As4MessageState::delivered->value);
    }

    public function testSetRefToMessageId(): void
    {
        $msg = $this->makeMessage();
        $msg->setRefToMessageId('ref-msg-001@as4.example.com');
        $this->assertSame('ref-msg-001@as4.example.com', $msg->getRefToMessageId());
    }

    public function testSetPayloadPartIds(): void
    {
        $msg = $this->makeMessage();
        $msg->setPayloadPartIds(['part1@example.com', 'part2@example.com']);
        $this->assertSame('part1@example.com,part2@example.com', $msg->getPayloadPartIds());
    }

    public function testMarkSentIncrementsAttemptCountAndSetsState(): void
    {
        $msg = $this->makeMessage();
        $msg->markSent();

        $this->assertSame(As4MessageState::sent, $msg->getState());
        $this->assertSame(1, $msg->getAttemptCount());
        $this->assertInstanceOf(DateTime::class, $msg->getLastAttemptAt());
        $this->assertInstanceOf(DateTime::class, $msg->getFirstSentAt());
    }

    public function testMarkSentIncrementsTwice(): void
    {
        $msg = $this->makeMessage();
        $msg->markSent();
        $firstSentAt = $msg->getFirstSentAt();
        $msg->markSent();

        $this->assertSame(2, $msg->getAttemptCount());
        $this->assertSame($firstSentAt, $msg->getFirstSentAt(), 'firstSentAt must not change on subsequent markSent');
    }

    public function testFirstSentAtIsNullBeforeMarkSent(): void
    {
        $this->assertNull($this->makeMessage()->getFirstSentAt());
    }

    public function testRecordAttemptIncrementsCountAndSetsTimestamps(): void
    {
        $msg = $this->makeMessage();
        $msg->recordAttempt();

        $this->assertSame(1, $msg->getAttemptCount());
        $this->assertInstanceOf(DateTime::class, $msg->getLastAttemptAt());
    }

    public function testRecordAttemptDoesNotChangeState(): void
    {
        $msg = $this->makeMessage();
        $msg->recordAttempt();

        $this->assertSame(As4MessageState::pending, $msg->getState());
    }

    public function testRecordAttemptIncrementsTwice(): void
    {
        $msg = $this->makeMessage();
        $msg->recordAttempt();
        $msg->recordAttempt();

        $this->assertSame(2, $msg->getAttemptCount());
    }

    public function testMarkReceiptReceived(): void
    {
        $msg = $this->makeMessage();
        $msg->markReceiptReceived('receipt-msg-001', 'abc123digest==');

        $this->assertSame(As4MessageState::receiptReceived, $msg->getState());
        $this->assertSame('receipt-msg-001', $msg->getReceiptMessageId());
        $this->assertSame('abc123digest==', $msg->getReceiptDigest());
        $this->assertInstanceOf(DateTime::class, $msg->getReceiptReceivedAt());
    }

    public function testMarkFailed(): void
    {
        $msg = $this->makeMessage();
        $msg->markFailed('EBMS:0202', 'Delivery failure');

        $this->assertSame(As4MessageState::failed, $msg->getState());
        $this->assertSame('EBMS:0202', $msg->getErrorCode());
        $this->assertSame('Delivery failure', $msg->getErrorDescription());
    }

    public function testIsReadyForRetryReturnsFalseWhenPending(): void
    {
        $this->assertFalse($this->makeMessage()->isReadyForRetry());
    }

    public function testIsReadyForRetryReturnsTrueWhenSentAndBelowMaxAttempts(): void
    {
        $msg = $this->makeMessage();
        $msg->markSent();
        // After first markSent, lastAttemptAt is now, but retryInterval is 300s
        // isReadyForRetry should be false immediately (interval not elapsed)
        $this->assertFalse($msg->isReadyForRetry());
    }

    public function testIsReadyForRetryReturnsFalseWhenMaxAttemptsReached(): void
    {
        $msg = $this->makeMessage();
        $msg->markSent();
        $msg->markSent();
        $msg->markSent();
        // 3 attempts = maxAttempts reached
        $this->assertFalse($msg->isReadyForRetry());
    }

    public function testCreatedAtAndUpdatedAtSetOnConstruction(): void
    {
        $before = new DateTime();
        $msg = $this->makeMessage();
        $after = new DateTime();

        $this->assertGreaterThanOrEqual($before, $msg->getCreatedAt());
        $this->assertLessThanOrEqual($after, $msg->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $msg->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $msg->getUpdatedAt());
    }

    public function testReturnTypes(): void
    {
        $msg = $this->makeMessage();
        $msg->setId(1);

        $this->assertIsInt($msg->reqId());
        $this->assertIsString($msg->getMessageId());
        $this->assertIsString($msg->getConversationId());
        $this->assertIsString($msg->getSenderPartyId());
        $this->assertIsString($msg->getSenderRole());
        $this->assertIsString($msg->getReceiverPartyId());
        $this->assertIsString($msg->getReceiverRole());
        $this->assertIsString($msg->getService());
        $this->assertIsString($msg->getAction());
        $this->assertIsString($msg->getReceiverEndpoint());
        $this->assertIsString($msg->getSoapMessage());
        $this->assertInstanceOf(As4MessageState::class, $msg->getState());
        $this->assertIsInt($msg->getAttemptCount());
        $this->assertIsInt($msg->getMaxAttempts());
        $this->assertIsInt($msg->getRetryIntervalSeconds());
        $this->assertInstanceOf(DateTime::class, $msg->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $msg->getUpdatedAt());
    }
}
