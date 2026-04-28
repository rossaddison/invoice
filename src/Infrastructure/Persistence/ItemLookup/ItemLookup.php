<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ItemLookup;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\ItemLookup\ItemLookupRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: ItemLookupRepository::class)]
class ItemLookup
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'string(100)', nullable: false)]
        private string $name = '',
        #[Column(type: 'longText', nullable: false)]
        private string $description = '',
        #[Column(type: 'decimal(10,2)', nullable: false)]
        private ?float $price = null,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'ItemLookup');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }
}
