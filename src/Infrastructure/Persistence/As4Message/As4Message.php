<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use App\Invoice\As4\As4MessageState;
use Cycle\Annotated\Annotation as Cycle;
use DateTime;

/**
 * AS4Message Infrastructure Entity
 *
 * Persists outbound AS4 messages with reception awareness state tracking
 * per eDelivery AS4 2.0 section 3.3.2 (Reliable Messaging and Non-Repudiation).
 *
 * @Cycle\Entity(role="as4Message", table="as4_messages")
 */
#[Cycle\Entity(role: 'as4Message', table: 'as4_messages')]
class As4Message
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    #[Cycle\Column(type: 'primary')]
    private int $id;

    /** RFC 2822 compliant MessageId */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $messageId;

    /** ConversationId for message correlation */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $conversationId;

    /** RefToMessageId for Two-Way exchanges (response to request) */
    #[Cycle\Column(type: 'string', nullable: true)]
    private ?string $refToMessageId = null;

    /** Sender party GLN (ISO 6523 code 0088) */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $senderPartyId;

    /** Sender role (e.g., "Seller") */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $senderRole;

    /** Receiver party GLN */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $receiverPartyId;

    /** Receiver role (e.g., "Buyer") */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $receiverRole;

    /** AS4 Service identifier */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $service;

    /** AS4 Action identifier */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $action;

    /** Receiver AS4 endpoint URL (HTTPS) */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $receiverEndpoint;

    /** Serialized AS4 XML message (without payloads) */
    #[Cycle\Column(type: 'text', nullable: false)]
    private string $soapMessage;

    /** Comma-separated list of payload MIME part IDs (cid:...) */
    #[Cycle\Column(type: 'text', nullable: true)]
    private ?string $payloadPartIds = null;

    /** Current message lifecycle state. */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $state;

    /** Number of transmission attempts */
    #[Cycle\Column(type: 'integer', nullable: false)]
    private int $attemptCount = 0;

    /** Maximum retry attempts before failure */
    #[Cycle\Column(type: 'integer', nullable: false)]
    private int $maxAttempts = 3;

    /** Seconds to wait between retries */
    #[Cycle\Column(type: 'integer', nullable: false)]
    private int $retryIntervalSeconds = 300;

    /** Timestamp of last transmission attempt */
    #[Cycle\Column(type: 'datetime', nullable: true)]
    private ?DateTime $lastAttemptAt = null;

    /** MessageId of received receipt (non-repudiation) */
    #[Cycle\Column(type: 'string', nullable: true)]
    private ?string $receiptMessageId = null;

    /** Digest from receipt (proof of reception) */
    #[Cycle\Column(type: 'text', nullable: true)]
    private ?string $receiptDigest = null;

    /** Timestamp when receipt received */
    #[Cycle\Column(type: 'datetime', nullable: true)]
    private ?DateTime $receiptReceivedAt = null;

    /** Timestamp of the very first transmission attempt (receipt deadline anchor). */
    #[Cycle\Column(type: 'datetime', nullable: true)]
    private ?DateTime $firstSentAt = null;

    /** Error code (if failed) */
    #[Cycle\Column(type: 'string', nullable: true)]
    private ?string $errorCode = null;

    /** Error description */
    #[Cycle\Column(type: 'text', nullable: true)]
    private ?string $errorDescription = null;

    #[Cycle\Column(type: 'datetime')]
    private DateTime $createdAt;

    #[Cycle\Column(type: 'datetime')]
    private DateTime $updatedAt;

    public function __construct(
        string $messageId,
        string $conversationId,
        string $senderPartyId,
        string $senderRole,
        string $receiverPartyId,
        string $receiverRole,
        string $service,
        string $action,
        string $receiverEndpoint,
        string $soapMessage
    ) {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
        $this->senderPartyId = $senderPartyId;
        $this->senderRole = $senderRole;
        $this->receiverPartyId = $receiverPartyId;
        $this->receiverRole = $receiverRole;
        $this->service = $service;
        $this->action = $action;
        $this->receiverEndpoint = $receiverEndpoint;
        $this->soapMessage = $soapMessage;
        $this->state       = As4MessageState::pending->value;
        $this->createdAt   = new DateTime();
        $this->updatedAt   = new DateTime();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /** @psalm-suppress RedundantPropertyInitializationCheck */
    public function reqId(): int
    {
        if (!isset($this->id)) {
            throw new \LogicException('Message not yet persisted');
        }
        return $this->id;
    }

    public function getMessageId(): string { return $this->messageId; }
    public function getConversationId(): string { return $this->conversationId; }
    public function getRefToMessageId(): ?string { return $this->refToMessageId; }
    public function getSenderPartyId(): string { return $this->senderPartyId; }
    public function getSenderRole(): string { return $this->senderRole; }
    public function getReceiverPartyId(): string { return $this->receiverPartyId; }
    public function getReceiverRole(): string { return $this->receiverRole; }
    public function getService(): string { return $this->service; }
    public function getAction(): string { return $this->action; }
    public function getReceiverEndpoint(): string { return $this->receiverEndpoint; }
    public function getSoapMessage(): string { return $this->soapMessage; }
    public function getPayloadPartIds(): ?string { return $this->payloadPartIds; }
    public function getState(): As4MessageState { return As4MessageState::from($this->state); }
    public function getAttemptCount(): int { return $this->attemptCount; }
    public function getMaxAttempts(): int { return $this->maxAttempts; }
    public function getRetryIntervalSeconds(): int { return $this->retryIntervalSeconds; }
    public function getLastAttemptAt(): ?DateTime { return $this->lastAttemptAt; }
    public function getReceiptMessageId(): ?string { return $this->receiptMessageId; }
    public function getReceiptDigest(): ?string { return $this->receiptDigest; }
    public function getReceiptReceivedAt(): ?DateTime { return $this->receiptReceivedAt; }
    public function getFirstSentAt(): ?DateTime { return $this->firstSentAt; }
    public function getErrorCode(): ?string { return $this->errorCode; }
    public function getErrorDescription(): ?string { return $this->errorDescription; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }

    public function isPersisted(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return isset($this->id);
    }

    public function setRefToMessageId(string $refToMessageId): self
    {
        $this->refToMessageId = $refToMessageId;
        return $this;
    }

    /**
     * @param string[] $partIds
     */
    public function setPayloadPartIds(array $partIds): self
    {
        $this->payloadPartIds = implode(',', $partIds);
        return $this;
    }

    public function markSent(): self
    {
        if ($this->firstSentAt === null) {
            $this->firstSentAt = new DateTime();
        }
        $this->state         = As4MessageState::sent->value;
        $this->lastAttemptAt = new DateTime();
        $this->attemptCount++;
        $this->updatedAt     = new DateTime();
        return $this;
    }

    /**
     * Records a transmission attempt without changing the message state.
     * Call this before each send — ensures the attempt counter and lastAttemptAt
     * are persisted even when the HTTP response is a failure or exception.
     */
    public function recordAttempt(): self
    {
        $this->attemptCount++;
        $this->lastAttemptAt = new DateTime();
        $this->updatedAt     = new DateTime();
        return $this;
    }

    public function markReceiptReceived(string $receiptMessageId, string $digest = ''): self
    {
        $this->state = As4MessageState::receiptReceived->value;
        $this->receiptMessageId = $receiptMessageId;
        $this->receiptDigest = $digest;
        $this->receiptReceivedAt = new DateTime();
        $this->updatedAt = new DateTime();
        return $this;
    }

    public function markFailed(string $errorCode, string $errorDescription): self
    {
        $this->state = As4MessageState::failed->value;
        $this->errorCode = $errorCode;
        $this->errorDescription = $errorDescription;
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * @param int $intervalSeconds Override the wait interval (0 = use the message's own retryIntervalSeconds).
     *                             Pass the value computed by As4RetryPolicyInterface::delaySeconds() to enable
     *                             exponential back-off or other custom policies.
     */
    public function isReadyForRetry(int $intervalSeconds = 0): bool
    {
        if ($this->state !== As4MessageState::sent->value) {
            return false;
        }
        if ($this->attemptCount >= $this->maxAttempts) {
            return false;
        }
        if ($this->lastAttemptAt === null) {
            return true;
        }

        $delay     = $intervalSeconds > 0 ? $intervalSeconds : $this->retryIntervalSeconds;
        $nextRetry = (clone $this->lastAttemptAt)->modify("+{$delay} seconds");
        return new DateTime() >= $nextRetry;
    }

    public function getNextRetryIn(): ?int
    {
        if (!$this->isReadyForRetry() && $this->lastAttemptAt !== null) {
            $nextRetry = clone $this->lastAttemptAt;
            $nextRetry->modify("+{$this->retryIntervalSeconds} seconds");
            $now = new DateTime();
            $diff = $nextRetry->diff($now);
            return max(0, (int) $diff->format('%s'));
        }
        return null;
    }
}
