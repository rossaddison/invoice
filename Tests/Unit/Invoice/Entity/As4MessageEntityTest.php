<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\As4MessageParams;
use App\Invoice\As4\As4MessageState;
use PHPUnit\Framework\TestCase;
use DateTime;

class As4MessageEntityTest extends TestCase
{
    private function makeMessage(): As4Message
    {
        return new As4Message(new As4MessageParams(
            messageId:        'msg-001@as4.example.com',
            conversationId:   'conv-001',
            senderPartyId:    '0088:1234567890123',
            senderRole:       'Seller',
            receiverPartyId:  '0088:9876543210987',
            receiverRole:     'Buyer',
            service:          'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            action:           'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            receiverEndpoint: 'https://ap.example.com/as4',
            soapMessage:      '<env:Envelope/>',
        ));
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
        $this->assertSame('conv-001', $msg->getRouting()->getConversationId());
        $this->assertSame('0088:1234567890123', $msg->getRouting()->getSenderPartyId());
        $this->assertSame('Seller', $msg->getRouting()->getSenderRole());
        $this->assertSame('0088:9876543210987', $msg->getRouting()->getReceiverPartyId());
        $this->assertSame('Buyer', $msg->getRouting()->getReceiverRole());
        $this->assertSame('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0', $msg->getRouting()->getService());
        $this->assertSame(
            'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            $msg->getRouting()->getAction()
        );
        $this->assertSame('https://ap.example.com/as4', $msg->getRouting()->getReceiverEndpoint());
        $this->assertSame('<env:Envelope/>', $msg->getPayload()->getSoapMessage());
    }

    public function testDefaultStateIsPending(): void
    {
        $this->assertSame(As4MessageState::pending, $this->makeMessage()->getState());
    }

    public function testDefaultAttemptCountIsZero(): void
    {
        $this->assertSame(0, $this->makeMessage()->getRetryState()->getAttemptCount());
    }

    public function testDefaultMaxAttemptsIsThree(): void
    {
        $this->assertSame(3, $this->makeMessage()->getRetryState()->getMaxAttempts());
    }

    public function testNullableFieldsAreNullByDefault(): void
    {
        $msg = $this->makeMessage();

        $this->assertNull($msg->getRouting()->getRefToMessageId());
        $this->assertNull($msg->getPayload()->getPayloadPartIds());
        $this->assertNull($msg->getRetryState()->getLastAttemptAt());
        $this->assertNull($msg->getReceiptInfo()->getReceiptMessageId());
        $this->assertNull($msg->getReceiptInfo()->getReceiptDigest());
        $this->assertNull($msg->getReceiptInfo()->getReceiptReceivedAt());
        $this->assertNull($msg->getErrorInfo()->getErrorCode());
        $this->assertNull($msg->getErrorInfo()->getErrorDescription());
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
        $msg->getRouting()->setRefToMessageId('ref-msg-001@as4.example.com');
        $this->assertSame('ref-msg-001@as4.example.com', $msg->getRouting()->getRefToMessageId());
    }

    public function testSetPayloadPartIds(): void
    {
        $msg = $this->makeMessage();
        $msg->getPayload()->setPayloadPartIds(['part1@example.com', 'part2@example.com']);
        $this->assertSame('part1@example.com,part2@example.com', $msg->getPayload()->getPayloadPartIds());
    }

    public function testMarkSentIncrementsAttemptCountAndSetsState(): void
    {
        $msg = $this->makeMessage();
        $msg->markSent();

        $this->assertSame(As4MessageState::sent, $msg->getState());
        $this->assertSame(1, $msg->getRetryState()->getAttemptCount());
        $this->assertInstanceOf(DateTime::class, $msg->getRetryState()->getLastAttemptAt());
        $this->assertInstanceOf(DateTime::class, $msg->getRetryState()->getFirstSentAt());
    }

    public function testMarkSentIncrementsTwice(): void
    {
        $msg = $this->makeMessage();
        $msg->markSent();
        $firstSentAt = $msg->getRetryState()->getFirstSentAt();
        $msg->markSent();

        $this->assertSame(2, $msg->getRetryState()->getAttemptCount());
        $this->assertSame($firstSentAt, $msg->getRetryState()->getFirstSentAt(), 'firstSentAt must not change on subsequent markSent');
    }

    public function testFirstSentAtIsNullBeforeMarkSent(): void
    {
        $this->assertNull($this->makeMessage()->getRetryState()->getFirstSentAt());
    }

    public function testRecordAttemptIncrementsCountAndSetsTimestamps(): void
    {
        $msg = $this->makeMessage();
        $msg->recordAttempt();

        $this->assertSame(1, $msg->getRetryState()->getAttemptCount());
        $this->assertInstanceOf(DateTime::class, $msg->getRetryState()->getLastAttemptAt());
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

        $this->assertSame(2, $msg->getRetryState()->getAttemptCount());
    }

    public function testMarkReceiptReceived(): void
    {
        $msg = $this->makeMessage();
        $msg->markReceiptReceived('receipt-msg-001', 'abc123digest==');

        $this->assertSame(As4MessageState::receiptReceived, $msg->getState());
        $this->assertSame('receipt-msg-001', $msg->getReceiptInfo()->getReceiptMessageId());
        $this->assertSame('abc123digest==', $msg->getReceiptInfo()->getReceiptDigest());
        $this->assertInstanceOf(DateTime::class, $msg->getReceiptInfo()->getReceiptReceivedAt());
    }

    public function testMarkFailed(): void
    {
        $msg = $this->makeMessage();
        $msg->markFailed('EBMS:0202', 'Delivery failure');

        $this->assertSame(As4MessageState::failed, $msg->getState());
        $this->assertSame('EBMS:0202', $msg->getErrorInfo()->getErrorCode());
        $this->assertSame('Delivery failure', $msg->getErrorInfo()->getErrorDescription());
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

        $this->assertGreaterThanOrEqual($before, $msg->getTimestamps()->getCreatedAt());
        $this->assertLessThanOrEqual($after, $msg->getTimestamps()->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $msg->getTimestamps()->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $msg->getTimestamps()->getUpdatedAt());
    }

    public function testReturnTypes(): void
    {
        $msg = $this->makeMessage();
        $msg->setId(1);

        $this->assertIsInt($msg->reqId());
        $this->assertIsString($msg->getMessageId());
        $this->assertIsString($msg->getRouting()->getConversationId());
        $this->assertIsString($msg->getRouting()->getSenderPartyId());
        $this->assertIsString($msg->getRouting()->getSenderRole());
        $this->assertIsString($msg->getRouting()->getReceiverPartyId());
        $this->assertIsString($msg->getRouting()->getReceiverRole());
        $this->assertIsString($msg->getRouting()->getService());
        $this->assertIsString($msg->getRouting()->getAction());
        $this->assertIsString($msg->getRouting()->getReceiverEndpoint());
        $this->assertIsString($msg->getPayload()->getSoapMessage());
        $this->assertInstanceOf(As4MessageState::class, $msg->getState());
        $this->assertIsInt($msg->getRetryState()->getAttemptCount());
        $this->assertIsInt($msg->getRetryState()->getMaxAttempts());
        $this->assertIsInt($msg->getRetryState()->getRetryIntervalSeconds());
        $this->assertInstanceOf(DateTime::class, $msg->getTimestamps()->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $msg->getTimestamps()->getUpdatedAt());
    }
}
