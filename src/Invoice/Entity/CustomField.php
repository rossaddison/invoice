<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\CustomField\CustomFieldRepository::class)]
class CustomField
{
    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'string(50)', nullable: true)]
        private ?string $table = '', #[Column(type: 'string(50)', nullable: true)]
        private ?string $label = '', #[Column(type: 'string(151)', nullable: false, default: 'TEXT')]
        private string $type = '', #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $location = null, #[Column(type: 'integer(11)', nullable: true, default: 999)]
        private ?int $order = null, #[Column(type: 'bool', default: true)]
        private bool $required = false) {}

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getLocation(): ?int
    {
        return $this->location;
    }

    public function setLocation(int $location): void
    {
        $this->location = $location;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }
}
