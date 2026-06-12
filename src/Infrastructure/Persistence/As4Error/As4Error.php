<?php

declare(strict_types=1);

namespace Invoice\Infrastructure\Persistence\As4Error;

use Cycle\Annotated\Annotation as Cycle;
use DateTime;

/**
 * AS4Error Infrastructure Entity
 *
 * Stores error signals received from responder
 * per eDelivery AS4 2.0 section 3.2.5.
 *
 * @Cycle\Entity(role="as4Error", table="as4_errors")
 */
#[Cycle\Entity(role: 'as4Error', table: 'as4_errors')]
class As4Error
{
    // Error Categories per spec
    public const string CATEGORY_COMMUNICATION = 'Communication';
    public const string CATEGORY_PROCESSING = 'Processing';
    public const string CATEGORY_UNPACKAGING = 'Unpackaging';

    /** @psalm-suppress PropertyNotSetInConstructor */
    #[Cycle\Column(type: 'primary')]
    private int $id;

    /** MessageId of the error signal message */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $errorMessageId;

    /** MessageId of the message that failed (RefToMessageId) */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $refToMessageId;

    /** Error code (e.g., EBMS:0202, EBMS:0303) */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $errorCode;

    /** Error category (Communication | Processing | Unpackaging) */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $category;

    /** Short error description */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $shortDescription;

    /** Detailed error message */
    #[Cycle\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** Sender of the original message */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $originSender;

    /** Receiver of the original message (sent error) */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $originReceiver;

    /** Full SOAP error signal XML */
    #[Cycle\Column(type: 'text', nullable: false)]
    private string $errorXml;

    /** Is signed with receiver's certificate */
    #[Cycle\Column(type: 'boolean', nullable: false)]
    private bool $isSigned = true;

    #[Cycle\Column(type: 'datetime')]
    private DateTime $receivedAt;

    #[Cycle\Column(type: 'datetime')]
    private DateTime $createdAt;

    public function __construct(
        string $errorMessageId,
        string $refToMessageId,
        string $errorCode,
        string $category,
        string $shortDescription,
        string $originSender,
        string $originReceiver,
        string $errorXml
    ) {
        $this->errorMessageId = $errorMessageId;
        $this->refToMessageId = $refToMessageId;
        $this->errorCode = $errorCode;
        $this->category = $category;
        $this->shortDescription = $shortDescription;
        $this->originSender = $originSender;
        $this->originReceiver = $originReceiver;
        $this->errorXml = $errorXml;
        $this->receivedAt = new DateTime();
        $this->createdAt = new DateTime();
    }

    /** @psalm-suppress RedundantPropertyInitializationCheck */
    public function reqId(): int
    {
        if (!isset($this->id)) {
            throw new \LogicException('Error not yet persisted');
        }
        return $this->id;
    }

    public function isPersisted(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return isset($this->id);
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getErrorMessageId(): string { return $this->errorMessageId; }
    public function getRefToMessageId(): string { return $this->refToMessageId; }
    public function getErrorCode(): string { return $this->errorCode; }
    public function getCategory(): string { return $this->category; }
    public function getShortDescription(): string { return $this->shortDescription; }
    public function getDescription(): ?string { return $this->description; }
    public function getOriginSender(): string { return $this->originSender; }
    public function getOriginReceiver(): string { return $this->originReceiver; }
    public function getErrorXml(): string { return $this->errorXml; }
    public function isSigned(): bool { return $this->isSigned; }
    public function getReceivedAt(): DateTime { return $this->receivedAt; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }

    public function isCritical(): bool
    {
        return in_array($this->errorCode, [
            'EBMS:0201',
            'EBMS:0202',
            'EBMS:0303',
            'EBMS:0402',
        ]);
    }

    public function isRetriable(): bool
    {
        return in_array($this->errorCode, [
            'EBMS:0202',
            'EBMS:0203',
        ]);
    }
}
