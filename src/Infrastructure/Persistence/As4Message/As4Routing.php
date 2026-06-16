<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use Cycle\Annotated\Annotation as Cycle;

#[Cycle\Embeddable]
class As4Routing
{
    #[Cycle\Column(type: 'string', nullable: false)]
    private string $conversationId;

    #[Cycle\Column(type: 'string', nullable: true)]
    private ?string $refToMessageId = null;

    #[Cycle\Column(type: 'string', nullable: false)]
    private string $senderPartyId;

    #[Cycle\Column(type: 'string', nullable: false)]
    private string $senderRole;

    #[Cycle\Column(type: 'string', nullable: false)]
    private string $receiverPartyId;

    #[Cycle\Column(type: 'string', nullable: false)]
    private string $receiverRole;

    #[Cycle\Column(type: 'string', nullable: false)]
    private string $service;

    #[Cycle\Column(type: 'string', nullable: false)]
    private string $action;

    #[Cycle\Column(type: 'string', nullable: false)]
    private string $receiverEndpoint;

    public function __construct(
        string $conversationId,
        string $senderPartyId,
        string $senderRole,
        string $receiverPartyId,
        string $receiverRole,
        string $service,
        string $action,
        string $receiverEndpoint,
    ) {
        $this->conversationId   = $conversationId;
        $this->senderPartyId    = $senderPartyId;
        $this->senderRole       = $senderRole;
        $this->receiverPartyId  = $receiverPartyId;
        $this->receiverRole     = $receiverRole;
        $this->service          = $service;
        $this->action           = $action;
        $this->receiverEndpoint = $receiverEndpoint;
    }

    public function getConversationId(): string { return $this->conversationId; }
    public function getRefToMessageId(): ?string { return $this->refToMessageId; }
    public function getSenderPartyId(): string { return $this->senderPartyId; }
    public function getSenderRole(): string { return $this->senderRole; }
    public function getReceiverPartyId(): string { return $this->receiverPartyId; }
    public function getReceiverRole(): string { return $this->receiverRole; }
    public function getService(): string { return $this->service; }
    public function getAction(): string { return $this->action; }
    public function getReceiverEndpoint(): string { return $this->receiverEndpoint; }

    public function setRefToMessageId(string $refToMessageId): void
    {
        $this->refToMessageId = $refToMessageId;
    }
}
