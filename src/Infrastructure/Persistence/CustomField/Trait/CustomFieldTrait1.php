<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CustomField\Trait;

use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait CustomFieldTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'CustomField');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
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

    public function getEmailMinLength(): ?int
    {
        return $this->email_min_length;
    }
}
