<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvSentLog;

use App\Infrastructure\Persistence\{
    Client\Client, Inv\Inv, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\InvSentLog\InvSentLogRepository::class)]

class InvSentLog
{
    use RequireId;
 
    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_sent;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null,
    ) {
        $this->date_sent = new DateTimeImmutable('now');
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'InvSentLog');
    }
    
    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function reqClientId(): int
    {
        return $this->requireId($this->client_id, 'Client');
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getDateSent(): DateTimeImmutable
    {
        return $this->date_sent;
    }

    public function setDateSent(DateTimeImmutable $date_sent): void
    {
        $this->date_sent = $date_sent;
    }
}
