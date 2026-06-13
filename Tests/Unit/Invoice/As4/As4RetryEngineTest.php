<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Invoice\As4\As4ErrorCategory;
use App\Invoice\As4\As4ErrorSeverity;
use App\Invoice\As4\As4ErrorSignal;
use App\Invoice\As4\As4FixedIntervalRetryPolicy;
use App\Invoice\As4\As4HttpResponse;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4MessageState;
use App\Invoice\As4\As4ReceiptParserInterface;
use App\Invoice\As4\As4ReceiptSignal;
use App\Invoice\As4\As4RetryEngine;
use App\Invoice\As4\As4SenderInterface;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Groups all collaborator mocks so the factory can return them together.
 * Intersection types make both MockObject methods (method/expects) and
 * the interface's own methods visible to Psalm.
 */
final class As4RetryEngineTestFixture
{
    public function __construct(
        public readonly As4RetryEngine $engine,
        public readonly As4MessageRepositoryInterface&MockObject $repository,
        public readonly As4SenderInterface&MockObject $sender,
        public readonly As4ReceiptParserInterface&MockObject $receiptParser,
    ) {}
}

#[AllowMockObjectsWithoutExpectations]
class As4RetryEngineTest extends TestCase
{
    // ── factory ───────────────────────────────────────────────────────────────

    private function createFixture(bool $claimSucceeds = true): As4RetryEngineTestFixture
    {
        $repository    = $this->createMock(As4MessageRepositoryInterface::class);
        $sender        = $this->createMock(As4SenderInterface::class);
        $receiptParser = $this->createMock(As4ReceiptParserInterface::class);

        $repository->method('claimForRetry')->willReturn($claimSucceeds);

        return new As4RetryEngineTestFixture(
            engine: new As4RetryEngine(
                repository:    $repository,
                sender:        $sender,
                logger:        $this->createMock(LoggerInterface::class),
                receiptParser: $receiptParser,
                retryPolicy:   new As4FixedIntervalRetryPolicy(),
            ),
            repository:    $repository,
            sender:        $sender,
            receiptParser: $receiptParser,
        );
    }

    private function makeMessage(string $soapMessage = '<Envelope/>'): As4Message
    {
        return new As4Message(
            messageId:        'msg-001@as4.example.com',
            conversationId:   'conv-001',
            senderPartyId:    '0088:1234567890123',
            senderRole:       'Seller',
            receiverPartyId:  '0088:9876543210987',
            receiverRole:     'Buyer',
            service:          'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            action:           'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            receiverEndpoint: 'https://ap.example.com/as4',
            soapMessage:      $soapMessage,
        );
    }

    /**
     * Returns a message in sent state with lastAttemptAt/firstSentAt 1 hour
     * in the past so isReadyForRetry(300) returns true immediately.
     */
    private function makeSentReadyMessage(): As4Message
    {
        $message = $this->makeMessage();
        $message->markSent();
        (new \ReflectionProperty(As4Message::class, 'lastAttemptAt'))
            ->setValue($message, new DateTime('-1 hour'));
        (new \ReflectionProperty(As4Message::class, 'firstSentAt'))
            ->setValue($message, new DateTime('-1 hour'));
        return $message;
    }

    private function makeReceiptSignal(): As4ReceiptSignal
    {
        return new As4ReceiptSignal(
            messageId:      'receipt-001@ap.example.com',
            refToMessageId: 'msg-001@as4.example.com',
            timestamp:      new DateTimeImmutable(),
        );
    }

    private function makeFailureSignal(): As4ErrorSignal
    {
        return new As4ErrorSignal(
            messageId:        'err-001@ap.example.com',
            refToMessageId:   'msg-001@as4.example.com',
            timestamp:        new DateTimeImmutable(),
            category:         As4ErrorCategory::Processing,
            errorCode:        'EBMS:0202',
            severity:         As4ErrorSeverity::Failure,
            shortDescription: 'MIME_PROBLEM',
            description:      'Receiver could not decode the MIME package',
        );
    }

    private function makeWarningSignal(): As4ErrorSignal
    {
        return new As4ErrorSignal(
            messageId:        'warn-001@ap.example.com',
            refToMessageId:   'msg-001@as4.example.com',
            timestamp:        new DateTimeImmutable(),
            category:         As4ErrorCategory::Processing,
            errorCode:        'EBMS:0010',
            severity:         As4ErrorSeverity::Warning,
            shortDescription: 'InvalidHeader',
            description:      'A non-critical issue was found in the message header',
        );
    }

    // ── detectMissingReceipts ─────────────────────────────────────────────────

    public function testDetectMissingReceiptsReturnsZeroForEmptyList(): void
    {
        $f = $this->createFixture();
        $f->repository->method('findAwaitingReceipts')->willReturn([]);

        $this->assertSame(0, $f->engine->detectMissingReceipts());
    }

    public function testDetectMissingReceiptsSkipsMessageWithNullFirstSentAt(): void
    {
        $f       = $this->createFixture();
        $message = $this->makeMessage(); // pending; firstSentAt = null
        $f->repository->method('findAwaitingReceipts')->willReturn([$message]);
        $f->repository->expects($this->never())->method('save');

        $this->assertSame(0, $f->engine->detectMissingReceipts());
    }

    public function testDetectMissingReceiptsSkipsMessageWithinDeadline(): void
    {
        $f       = $this->createFixture();
        $message = $this->makeMessage();
        $message->markSent(); // firstSentAt = now; deadline is 20 min from now
        $f->repository->method('findAwaitingReceipts')->willReturn([$message]);
        $f->repository->expects($this->never())->method('save');

        $this->assertSame(0, $f->engine->detectMissingReceipts());
    }

    public function testDetectMissingReceiptsMarksFailedWhenPastDeadline(): void
    {
        $f       = $this->createFixture();
        $message = $this->makeMessage();
        $message->markSent();
        // Backdate firstSentAt 1 hour so the 20-min deadline is already past
        (new \ReflectionProperty(As4Message::class, 'firstSentAt'))
            ->setValue($message, new DateTime('-1 hour'));
        $f->repository->method('findAwaitingReceipts')->willReturn([$message]);
        $f->repository->expects($this->once())->method('save');

        $count = $f->engine->detectMissingReceipts();

        $this->assertSame(1, $count);
        $this->assertSame(As4MessageState::failed, $message->getState());
        $this->assertSame('EBMS:0301', $message->getErrorCode());
    }

    // ── processRetries — concurrency protection ───────────────────────────────

    public function testProcessRetriesSkipsMessageWhenClaimFails(): void
    {
        $f       = $this->createFixture(claimSucceeds: false);
        $message = $this->makeSentReadyMessage();
        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->sender->expects($this->never())->method('send');
        // No save() because the message was skipped entirely
        $f->repository->expects($this->never())->method('save');

        $stats = $f->engine->processRetries();

        // Message not counted — claim failure is silent, not a retry failure
        $this->assertSame(0, $stats['processed']);
        $this->assertSame(0, $stats['succeeded']);
        $this->assertSame(0, $stats['failed']);
        // State unchanged — another worker owns this message
        $this->assertSame(As4MessageState::sent, $message->getState());
    }

    // ── processRetries — skip paths ───────────────────────────────────────────

    public function testProcessRetriesReturnsZeroStatsForEmptyList(): void
    {
        $f = $this->createFixture();
        $f->repository->method('findPendingRetries')->willReturn([]);

        $this->assertSame(
            ['processed' => 0, 'succeeded' => 0, 'failed' => 0],
            $f->engine->processRetries()
        );
    }

    public function testProcessRetriesSkipsMessageNotReadyForRetry(): void
    {
        $f       = $this->createFixture();
        $message = $this->makeMessage();
        $message->markSent(); // lastAttemptAt = now → isReadyForRetry(300) = false
        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->repository->expects($this->never())->method('save');

        $stats = $f->engine->processRetries();
        $this->assertSame(0, $stats['processed']);
    }

    // ── processRetries — 200 + receipt signal ─────────────────────────────────

    public function testProcessRetriesSucceedsWithReceiptSignal(): void
    {
        $f        = $this->createFixture();
        $message  = $this->makeSentReadyMessage();
        $response = new As4HttpResponse(200, '<soap/>', 'application/soap+xml');

        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->sender->method('send')->willReturn($response);
        $f->receiptParser->method('parse')->willReturn($this->makeReceiptSignal());
        // save() called twice: recordAttempt() pre-send + markReceiptReceived() post-send
        $f->repository->expects($this->exactly(2))->method('save');

        $stats = $f->engine->processRetries();

        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['succeeded']);
        $this->assertSame(0, $stats['failed']);
        $this->assertSame(As4MessageState::receiptReceived, $message->getState());
        $this->assertSame('receipt-001@ap.example.com', $message->getReceiptMessageId());
    }

    // ── processRetries — 200 + failure error signal ───────────────────────────

    public function testProcessRetriesFailsWithFailureErrorSignal(): void
    {
        $f        = $this->createFixture();
        $message  = $this->makeSentReadyMessage();
        $response = new As4HttpResponse(200, '<soap/>');

        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->sender->method('send')->willReturn($response);
        $f->receiptParser->method('parse')->willReturn($this->makeFailureSignal());
        // save() called twice: recordAttempt() + markFailed()
        $f->repository->expects($this->exactly(2))->method('save');

        $stats = $f->engine->processRetries();

        $this->assertSame(1, $stats['processed']);
        $this->assertSame(0, $stats['succeeded']);
        $this->assertSame(1, $stats['failed']);
        $this->assertSame(As4MessageState::failed, $message->getState());
        $this->assertSame('EBMS:0202', $message->getErrorCode());
    }

    // ── processRetries — 200 + warning signal ────────────────────────────────

    public function testProcessRetriesStaysSentWithWarningSignal(): void
    {
        $f        = $this->createFixture();
        $message  = $this->makeSentReadyMessage();
        $before   = $message->getAttemptCount(); // 1 from initial markSent()
        $response = new As4HttpResponse(200, '<soap/>');

        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->sender->method('send')->willReturn($response);
        $f->receiptParser->method('parse')->willReturn($this->makeWarningSignal());
        // save() called once only: recordAttempt() pre-send; no second save on warning path
        $f->repository->expects($this->once())->method('save');

        $stats = $f->engine->processRetries();

        $this->assertSame(1, $stats['succeeded']);
        $this->assertSame(As4MessageState::sent, $message->getState());
        // Attempt count incremented exactly once by recordAttempt() — NOT double-incremented
        $this->assertSame($before + 1, $message->getAttemptCount());
    }

    // ── processRetries — 202 + no signal ─────────────────────────────────────

    public function testProcessRetriesStaysSentWithNullSignal(): void
    {
        $f        = $this->createFixture();
        $message  = $this->makeSentReadyMessage();
        $before   = $message->getAttemptCount();
        $response = new As4HttpResponse(202, '');

        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->sender->method('send')->willReturn($response);
        $f->receiptParser->method('parse')->willReturn(null);
        // save() called once: recordAttempt() pre-send
        $f->repository->expects($this->once())->method('save');

        $stats = $f->engine->processRetries();

        $this->assertSame(1, $stats['succeeded']);
        $this->assertSame(As4MessageState::sent, $message->getState());
        $this->assertSame($before + 1, $message->getAttemptCount());
    }

    // ── processRetries — retriable HTTP error ────────────────────────────────

    public function testProcessRetriesStaysSentForRetriableHttpError(): void
    {
        $f        = $this->createFixture();
        $message  = $this->makeSentReadyMessage();
        $response = new As4HttpResponse(503, '');

        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->sender->method('send')->willReturn($response);
        // save() called once: recordAttempt() pre-send; no additional save for retriable errors
        $f->repository->expects($this->once())->method('save');

        $stats = $f->engine->processRetries();

        $this->assertSame(1, $stats['failed']);
        $this->assertSame(As4MessageState::sent, $message->getState()); // not permanently failed
    }

    // ── processRetries — non-retriable HTTP error ─────────────────────────────

    public function testProcessRetriesMarksFailedForNonRetriableHttpError(): void
    {
        $f        = $this->createFixture();
        $message  = $this->makeSentReadyMessage();
        $response = new As4HttpResponse(400, '');

        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->sender->method('send')->willReturn($response);
        // save() called twice: recordAttempt() + markFailed()
        $f->repository->expects($this->exactly(2))->method('save');

        $stats = $f->engine->processRetries();

        $this->assertSame(1, $stats['failed']);
        $this->assertSame(As4MessageState::failed, $message->getState());
        $this->assertSame('HTTP_400', $message->getErrorCode());
    }

    // ── processRetries — malformed stored SOAP ────────────────────────────────

    public function testProcessRetriesMarksSoapFailedForMalformedXml(): void
    {
        $f = $this->createFixture();
        // Truncated tag — DOMDocument::loadXML returns false (not well-formed)
        $message = $this->makeMessage('<truncated');
        $message->markSent();
        (new \ReflectionProperty(As4Message::class, 'lastAttemptAt'))
            ->setValue($message, new DateTime('-1 hour'));
        (new \ReflectionProperty(As4Message::class, 'firstSentAt'))
            ->setValue($message, new DateTime('-1 hour'));

        $f->repository->method('findPendingRetries')->willReturn([$message]);
        $f->sender->expects($this->never())->method('send');
        $f->repository->expects($this->once())->method('save');

        $stats = $f->engine->processRetries();

        $this->assertSame(1, $stats['failed']);
        $this->assertSame(As4MessageState::failed, $message->getState());
        $this->assertSame('MALFORMED_SOAP', $message->getErrorCode());
    }

    // ── getNextRetryDelay ─────────────────────────────────────────────────────

    public function testGetNextRetryDelayReturnsNullForUnsentMessage(): void
    {
        $f       = $this->createFixture();
        $message = $this->makeMessage();
        // pending state, never sent — getNextRetryIn() returns null
        $this->assertNull($f->engine->getNextRetryDelay($message));
    }
}
