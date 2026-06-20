<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PeppolMessage\Trait;

use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait PeppolMessageTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'PeppolMessage');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getMessageId(): ?string
    {
        return $this->message_id;
    }

    public function setMessageId(string $message_id): void
    {
        $this->message_id = $message_id;
    }

    public function getRecipientId(): ?string
    {
        return $this->recipient_id;
    }

    public function setRecipientId(string $recipient_id): void
    {
        $this->recipient_id = $recipient_id;
    }

    public function getDocumentTypeId(): ?string
    {
        return $this->document_type_id;
    }

    public function setDocumentTypeId(string $document_type_id): void
    {
        $this->document_type_id = $document_type_id;
    }

    public function getProcessId(): ?string
    {
        return $this->process_id;
    }

    public function setProcessId(string $process_id): void
    {
        $this->process_id = $process_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getSentAt(): ?DateTimeImmutable
    {
        return $this->sent_at;
    }
}
