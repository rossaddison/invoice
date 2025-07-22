<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\InvSentLog\InvSentLogRepository::class)]

class InvSentLog
{
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

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getInv_id(): int|null
    {
        return $this->inv_id;
    }

    public function setInv_id(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getClient_id(): int|null
    {
        return $this->client_id;
    }

    public function setClient_id(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getDate_sent(): DateTimeImmutable
    {
        return $this->date_sent;
    }

    public function setDate_sent(DateTimeImmutable $date_sent): void
    {
        $this->date_sent = $date_sent;
    }

    public function nullifyRelationOnChange(int $client_id, int $inv_id): void
    {
        if ($this->client_id != $client_id) {
            $this->client = null;
        }
        if ($this->inv_id != $inv_id) {
            $this->inv = null;
        }
    }
}
