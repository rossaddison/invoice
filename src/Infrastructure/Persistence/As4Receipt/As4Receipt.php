<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Receipt;

use Cycle\Annotated\Annotation as Cycle;
use DateTime;

/**
 * AS4Receipt Infrastructure Entity
 *
 * Stores Non-Repudiation Receipts received from responder
 * per eDelivery AS4 2.0 section 3.3.2.
 *
 * @Cycle\Entity(role="as4Receipt", table="as4_receipts")
 * @psalm-suppress UnusedClass
 */
#[Cycle\Entity(role: 'as4Receipt', table: 'as4_receipts')]
class As4Receipt
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    #[Cycle\Column(type: 'primary')]
    private int $id;

    /** MessageId of the receipt signal message */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $receiptMessageId;

    /** MessageId of the original user message being acknowledged */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $refToMessageId;

    /** SHA-256 digest of original message (proof of reception) */
    #[Cycle\Column(type: 'text', nullable: false)]
    private string $digestValue;

    /** Sender of the original message */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $originSender;

    /** Receiver of the original message (sent receipt) */
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $originReceiver;

    /** Full SOAP receipt XML for non-repudiation */
    #[Cycle\Column(type: 'text', nullable: false)]
    private string $receiptXml;

    /** Is signed with receiver's certificate */
    #[Cycle\Column(type: 'boolean', nullable: false)]
    private bool $isSigned = true;

    #[Cycle\Column(type: 'datetime')]
    private DateTime $receivedAt;

    #[Cycle\Column(type: 'datetime')]
    private DateTime $createdAt;

    public function __construct(
        string $receiptMessageId,
        string $refToMessageId,
        string $digestValue,
        string $originSender,
        string $originReceiver,
        string $receiptXml
    ) {
        $this->receiptMessageId = $receiptMessageId;
        $this->refToMessageId = $refToMessageId;
        $this->digestValue = $digestValue;
        $this->originSender = $originSender;
        $this->originReceiver = $originReceiver;
        $this->receiptXml = $receiptXml;
        $this->receivedAt = new DateTime();
        $this->createdAt = new DateTime();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /** @psalm-suppress RedundantPropertyInitializationCheck */
    public function reqId(): int
    {
        if (!isset($this->id)) {
            throw new \LogicException('Receipt not yet persisted');
        }
        return $this->id;
    }

    public function isPersisted(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return isset($this->id);
    }

    public function getReceiptMessageId(): string { return $this->receiptMessageId; }
    public function getRefToMessageId(): string { return $this->refToMessageId; }
    public function getDigestValue(): string { return $this->digestValue; }
    public function getOriginSender(): string { return $this->originSender; }
    public function getOriginReceiver(): string { return $this->originReceiver; }
    public function getReceiptXml(): string { return $this->receiptXml; }
    public function isSigned(): bool { return $this->isSigned; }
    public function getReceivedAt(): DateTime { return $this->receivedAt; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
}
