<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PeppolMessage;

use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Peppol\PeppolMessageRepository::class)]
#[Behavior\CreatedAt(field: 'created_at', column: 'created_at')]
class PeppolMessage
{
    use RequireId;

    private const string COL_STR255 = 'string(255)';

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $created_at;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $message_id = null,
        #[Column(type: self::COL_STR255, nullable: false)]
        private ?string $recipient_id = null,
        #[Column(type: self::COL_STR255, nullable: false)]
        private ?string $document_type_id =
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        #[Column(type: self::COL_STR255, nullable: false)]
        private ?string $process_id =
            'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
        #[Column(type: 'string(20)', nullable: false)]
        private string $status = 'QUEUED',
        #[Column(type: 'datetime', nullable: true)]
        private ?DateTimeImmutable $sent_at = null,
        #[Column(type: 'datetime', nullable: true)]
        private ?DateTimeImmutable $delivered_at = null,
        #[Column(type: 'string(1000)', nullable: true)]
        private ?string $error_message = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private int $retry_count = 0,
    ) {
        $this->created_at = new DateTimeImmutable();
    }

    #[Column(type: 'text', nullable: true)]
    private ?string $ubl_xml = null;

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

    public function setSentAt(DateTimeImmutable $sent_at): void
    {
        $this->sent_at = $sent_at;
    }

    public function getDeliveredAt(): ?DateTimeImmutable
    {
        return $this->delivered_at;
    }

    public function setDeliveredAt(DateTimeImmutable $delivered_at): void
    {
        $this->delivered_at = $delivered_at;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(string $error_message): void
    {
        $this->error_message = $error_message;
    }

    public function getRetryCount(): int
    {
        return $this->retry_count;
    }

    public function incrementRetryCount(): void
    {
        $this->retry_count++;
    }

    public function getUblXml(): ?string
    {
        return $this->ubl_xml;
    }

    public function setUblXml(string $ubl_xml): void
    {
        $this->ubl_xml = $ubl_xml;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }
}
