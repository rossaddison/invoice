<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use App\Invoice\As4\As4MessageState;
use Cycle\Annotated\Annotation as Cycle;

#[Cycle\Entity(role: 'as4Message', table: 'as4_messages')]
class As4Message
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    #[Cycle\Column(type: 'primary')]
    private int $id;

    /** RFC 2822 compliant MessageId */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $messageId;

    /** Current message lifecycle state. */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $state;

    #[Cycle\Relation\Embedded(target: As4Routing::class)]
    private As4Routing $routing;

    #[Cycle\Relation\Embedded(target: As4Payload::class)]
    private As4Payload $payload;

    #[Cycle\Relation\Embedded(target: As4RetryState::class)]
    private As4RetryState $retryState;

    #[Cycle\Relation\Embedded(target: As4ReceiptInfo::class)]
    private As4ReceiptInfo $receiptInfo;

    #[Cycle\Relation\Embedded(target: As4ErrorInfo::class)]
    private As4ErrorInfo $errorInfo;

    #[Cycle\Relation\Embedded(target: As4Timestamps::class)]
    private As4Timestamps $timestamps;

    public function __construct(As4MessageParams $p)
    {
        $this->messageId   = $p->messageId;
        $this->state       = As4MessageState::pending->value;
        $this->routing     = new As4Routing(new As4RoutingParams(
            conversationId:   $p->conversationId,
            senderPartyId:    $p->senderPartyId,
            senderRole:       $p->senderRole,
            receiverPartyId:  $p->receiverPartyId,
            receiverRole:     $p->receiverRole,
            service:          $p->service,
            action:           $p->action,
            receiverEndpoint: $p->receiverEndpoint,
        ));
        $this->payload     = new As4Payload($p->soapMessage);
        $this->retryState  = new As4RetryState();
        $this->receiptInfo = new As4ReceiptInfo();
        $this->errorInfo   = new As4ErrorInfo();
        $this->timestamps  = new As4Timestamps();
    }

    public function setId(int $id): void { $this->id = $id; }

    /** @psalm-suppress RedundantPropertyInitializationCheck */
    public function reqId(): int
    {
        if (!isset($this->id)) {
            throw new \LogicException('Message not yet persisted');
        }
        return $this->id;
    }

    public function isPersisted(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return isset($this->id);
    }

    public function getMessageId(): string { return $this->messageId; }
    public function getState(): As4MessageState { return As4MessageState::from($this->state); }
    public function getRouting(): As4Routing { return $this->routing; }
    public function getPayload(): As4Payload { return $this->payload; }
    public function getRetryState(): As4RetryState { return $this->retryState; }
    public function getReceiptInfo(): As4ReceiptInfo { return $this->receiptInfo; }
    public function getErrorInfo(): As4ErrorInfo { return $this->errorInfo; }
    public function getTimestamps(): As4Timestamps { return $this->timestamps; }

    public function markSent(): self
    {
        $this->retryState->recordSent();
        $this->state = As4MessageState::sent->value;
        $this->timestamps->touch();
        return $this;
    }

    /**
     * Records a transmission attempt without changing the message state.
     * Call this before each send — ensures the attempt counter and lastAttemptAt
     * are persisted even when the HTTP response is a failure or exception.
     */
    public function recordAttempt(): self
    {
        $this->retryState->recordAttempt();
        $this->timestamps->touch();
        return $this;
    }

    public function markReceiptReceived(string $receiptMessageId, string $digest = ''): self
    {
        $this->state = As4MessageState::receiptReceived->value;
        $this->receiptInfo->markReceived($receiptMessageId, $digest);
        $this->timestamps->touch();
        return $this;
    }

    public function markReceived(): self
    {
        $this->state = As4MessageState::received->value;
        $this->timestamps->touch();
        return $this;
    }

    public function markFailed(string $errorCode, string $errorDescription): self
    {
        $this->state = As4MessageState::failed->value;
        $this->errorInfo->set($errorCode, $errorDescription);
        $this->timestamps->touch();
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
        return $this->retryState->isEligible($intervalSeconds);
    }

    public function getNextRetryIn(): ?int
    {
        if (!$this->isReadyForRetry() && $this->retryState->getLastAttemptAt() !== null) {
            return $this->retryState->secondsUntilNextRetry();
        }
        return null;
    }
}
