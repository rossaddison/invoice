<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\ItemLookup\ItemLookupRepository::class)]
class ItemLookup
{
    public function __construct(#[Column(type: 'primary')]
    private ?int $id = null, #[Column(type: 'string(100)', nullable:false)]
    private string $name = '', #[Column(type: 'longText', nullable:false)]
    private string $description = '', #[Column(type: 'decimal(10,2)', nullable:false)]
    private ?float $price = null)
    {
    }

    public function getId(): string
    {
        return (string)$this->id;
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

    public function getPrice(): float|null
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }
}
