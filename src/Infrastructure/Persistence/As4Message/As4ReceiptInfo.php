<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use Cycle\Annotated\Annotation as Cycle;
use DateTime;

#[Cycle\Embeddable]
class As4ReceiptInfo
{
    /** MessageId of received receipt (non-repudiation) */
    #[Cycle\Column(type: 'string', nullable: true)]
    private ?string $receiptMessageId = null;

    /** Digest from receipt (proof of reception) */
    #[Cycle\Column(type: 'text', nullable: true)]
    private ?string $receiptDigest = null;

    #[Cycle\Column(type: 'datetime', nullable: true)]
    private ?DateTime $receiptReceivedAt = null;

    public function getReceiptMessageId(): ?string { return $this->receiptMessageId; }
    public function getReceiptDigest(): ?string { return $this->receiptDigest; }
    public function getReceiptReceivedAt(): ?DateTime { return $this->receiptReceivedAt; }

    public function markReceived(string $messageId, string $digest): void
    {
        $this->receiptMessageId  = $messageId;
        $this->receiptDigest     = $digest;
        $this->receiptReceivedAt = new DateTime();
    }
}
