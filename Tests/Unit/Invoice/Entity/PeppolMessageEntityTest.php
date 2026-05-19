<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PeppolMessageEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $message = new PeppolMessage();
        $this->assertFalse($message->isPersisted());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $message = new PeppolMessage();
        $this->expectException(\LogicException::class);
        $message->reqId();
    }

    public function testIsPersistedReturnsTrueAfterSetId(): void
    {
        $message = new PeppolMessage();
        $message->setId(1);
        $this->assertTrue($message->isPersisted());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $message = new PeppolMessage();
        $message->setId(42);
        $this->assertIsInt($message->reqId());
        $this->assertSame(42, $message->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $message = new PeppolMessage();

        $this->assertFalse($message->isPersisted());
        $this->assertNull($message->getInvId());
        $this->assertNull($message->getMessageId());
        $this->assertNull($message->getRecipientId());
        $this->assertSame(
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            $message->getDocumentTypeId()
        );
        $this->assertSame(
            'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            $message->getProcessId()
        );
        $this->assertSame('QUEUED', $message->getStatus());
        $this->assertNull($message->getSentAt());
        $this->assertNull($message->getDeliveredAt());
        $this->assertNull($message->getErrorMessage());
        $this->assertSame(0, $message->getRetryCount());
        $this->assertInstanceOf(DateTimeImmutable::class, $message->getCreatedAt());
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = new PeppolMessage(
            inv_id: 234,
            recipient_id: '0088:1234567890123',
            document_type_id: 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            process_id: 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            status: 'QUEUED',
        );
        $message->setId(1);

        $this->assertSame(1, $message->reqId());
        $this->assertSame(234, $message->getInvId());
        $this->assertSame('0088:1234567890123', $message->getRecipientId());
        $this->assertSame(
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            $message->getDocumentTypeId()
        );
        $this->assertSame(
            'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            $message->getProcessId()
        );
        $this->assertSame('QUEUED', $message->getStatus());
    }

    public function testInvIdSetterAndGetter(): void
    {
        $message = new PeppolMessage();
        $message->setInvId(999);
        $this->assertSame(999, $message->getInvId());
    }

    public function testMessageIdSetterAndGetter(): void
    {
        $message = new PeppolMessage();
        $message->setMessageId('mock-msg-00000001');
        $this->assertSame('mock-msg-00000001', $message->getMessageId());
    }

    public function testRecipientIdSetterAndGetter(): void
    {
        $message = new PeppolMessage();
        $message->setRecipientId('0192:987654321');
        $this->assertSame('0192:987654321', $message->getRecipientId());
    }

    public function testDocumentTypeIdSetterAndGetter(): void
    {
        $message = new PeppolMessage();
        $message->setDocumentTypeId('urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2');
        $this->assertSame(
            'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2',
            $message->getDocumentTypeId()
        );
    }

    public function testProcessIdSetterAndGetter(): void
    {
        $message = new PeppolMessage();
        $message->setProcessId('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');
        $this->assertSame(
            'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            $message->getProcessId()
        );
    }

    public function testStatusSetterAndGetter(): void
    {
        $message = new PeppolMessage();

        $message->setStatus('SENT');
        $this->assertSame('SENT', $message->getStatus());

        $message->setStatus('FAILED');
        $this->assertSame('FAILED', $message->getStatus());

        $message->setStatus('RETRYING');
        $this->assertSame('RETRYING', $message->getStatus());

        $message->setStatus('DELIVERED');
        $this->assertSame('DELIVERED', $message->getStatus());
    }

    public function testSentAtSetterAndGetter(): void
    {
        $message = new PeppolMessage();
        $this->assertNull($message->getSentAt());

        $sentAt = new DateTimeImmutable('2026-05-19 10:00:00');
        $message->setSentAt($sentAt);
        $this->assertSame($sentAt, $message->getSentAt());
    }

    public function testDeliveredAtSetterAndGetter(): void
    {
        $message = new PeppolMessage();
        $this->assertNull($message->getDeliveredAt());

        $deliveredAt = new DateTimeImmutable('2026-05-19 10:05:00');
        $message->setDeliveredAt($deliveredAt);
        $this->assertSame($deliveredAt, $message->getDeliveredAt());
    }

    public function testErrorMessageSetterAndGetter(): void
    {
        $message = new PeppolMessage();
        $this->assertNull($message->getErrorMessage());

        $message->setErrorMessage('Connection refused');
        $this->assertSame('Connection refused', $message->getErrorMessage());
    }

    public function testRetryCountDefaultsToZero(): void
    {
        $message = new PeppolMessage();
        $this->assertSame(0, $message->getRetryCount());
    }

    public function testIncrementRetryCount(): void
    {
        $message = new PeppolMessage();

        $message->incrementRetryCount();
        $this->assertSame(1, $message->getRetryCount());

        $message->incrementRetryCount();
        $this->assertSame(2, $message->getRetryCount());

        $message->incrementRetryCount();
        $this->assertSame(3, $message->getRetryCount());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new DateTimeImmutable();
        $message = new PeppolMessage();
        $after = new DateTimeImmutable();

        $createdAt = $message->getCreatedAt();
        $this->assertInstanceOf(DateTimeImmutable::class, $createdAt);
        $this->assertGreaterThanOrEqual($before, $createdAt);
        $this->assertLessThanOrEqual($after, $createdAt);
    }

    public function testStatusLifecycleQueuedToSent(): void
    {
        $message = new PeppolMessage(inv_id: 1, recipient_id: '0088:test');

        $this->assertSame('QUEUED', $message->getStatus());

        $message->setStatus('SENT');
        $message->setMessageId('msg-abc-123');
        $message->setSentAt(new DateTimeImmutable());

        $this->assertSame('SENT', $message->getStatus());
        $this->assertSame('msg-abc-123', $message->getMessageId());
        $this->assertInstanceOf(DateTimeImmutable::class, $message->getSentAt());
    }

    public function testStatusLifecycleQueuedToFailed(): void
    {
        $message = new PeppolMessage(inv_id: 1, recipient_id: '0088:test');

        $this->assertSame('QUEUED', $message->getStatus());

        $message->setStatus('FAILED');
        $message->setErrorMessage('HTTP 503 Service Unavailable');

        $this->assertSame('FAILED', $message->getStatus());
        $this->assertSame('HTTP 503 Service Unavailable', $message->getErrorMessage());
        $this->assertSame(0, $message->getRetryCount());
    }

    public function testStatusLifecycleFailedToRetrying(): void
    {
        $message = new PeppolMessage(inv_id: 1, recipient_id: '0088:test');
        $message->setStatus('FAILED');
        $message->setErrorMessage('Timeout');

        $message->incrementRetryCount();
        $message->setStatus('RETRYING');

        $this->assertSame('RETRYING', $message->getStatus());
        $this->assertSame(1, $message->getRetryCount());
    }

    public function testSentToDeliveredLifecycle(): void
    {
        $message = new PeppolMessage(inv_id: 1, recipient_id: '0088:test');
        $message->setStatus('SENT');
        $message->setMessageId('msg-xyz-456');
        $message->setSentAt(new DateTimeImmutable());

        $deliveredAt = new DateTimeImmutable();
        $message->setStatus('DELIVERED');
        $message->setDeliveredAt($deliveredAt);

        $this->assertSame('DELIVERED', $message->getStatus());
        $this->assertSame($deliveredAt, $message->getDeliveredAt());
        $this->assertSame('msg-xyz-456', $message->getMessageId());
    }

    public function testReturnTypes(): void
    {
        $message = new PeppolMessage(
            inv_id: 1,
            message_id: 'msg-001',
            recipient_id: '0088:1234567890123',
            document_type_id: 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            process_id: 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            status: 'QUEUED',
        );
        $message->setId(1);

        $this->assertIsInt($message->reqId());
        $this->assertIsInt($message->getInvId());
        $this->assertIsString($message->getMessageId());
        $this->assertIsString($message->getRecipientId());
        $this->assertIsString($message->getDocumentTypeId());
        $this->assertIsString($message->getProcessId());
        $this->assertIsString($message->getStatus());
        $this->assertIsInt($message->getRetryCount());
        $this->assertInstanceOf(DateTimeImmutable::class, $message->getCreatedAt());
    }

    public function testPeppolParticipantIdFormats(): void
    {
        $message = new PeppolMessage();

        // GLN (GS1)
        $message->setRecipientId('0088:1234567890123');
        $this->assertSame('0088:1234567890123', $message->getRecipientId());

        // Norwegian org number
        $message->setRecipientId('0192:987654321');
        $this->assertSame('0192:987654321', $message->getRecipientId());

        // Italian IPA code
        $message->setRecipientId('0201:ABCDEF');
        $this->assertSame('0201:ABCDEF', $message->getRecipientId());
    }
}
