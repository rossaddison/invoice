<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\CustomValue\CustomValueRepository::class)]
class CustomValue
{
    #[BelongsTo(target: CustomField::class, nullable: false, fkAction: 'NO ACTION')]
    private ?CustomField $custom_field = null;

    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $custom_field_id = null, #[Column(type: 'text', nullable: false)]
        private string $value = '')
    {
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCustomField(): ?CustomField
    {
        return $this->custom_field;
    }

    public function getCustom_field_id(): ?int
    {
        return $this->custom_field_id;
    }

    public function setCustom_field_id(int $custom_field_id): void
    {
        $this->custom_field_id = $custom_field_id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
