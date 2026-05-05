<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\DeliveryParty;

use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\DeliveryParty\DeliveryPartyRepository::class)]

class DeliveryParty
{
    use RequireId;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $party_name = ''
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'DeliveryParty');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPartyName(): ?string
    {
        return $this->party_name;
    }

    public function setPartyName(string $party_name): void
    {
        $this->party_name = $party_name;
    }
}
