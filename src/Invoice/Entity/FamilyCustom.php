<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\FamilyCustom\FamilyCustomRepository::class)]
class FamilyCustom
{
    #[BelongsTo(target: Family::class, nullable: false)]
    private ?Family $family = null;

    #[BelongsTo(target: CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;

    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $family_id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $custom_field_id = null, #[Column(type: 'text', nullable: true)]
        private ?string $value = null)
    {
    }

    public function getFamily(): ?Family
    {
        return $this->family;
    }

    public function setFamily(?Family $family): void
    {
        $this->family = $family;
    }

    public function getCustomField(): ?CustomField
    {
        return $this->custom_field;
    }

    public function setCustomField(?CustomField $custom_field): void
    {
        $this->custom_field = $custom_field;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFamily_id(): string
    {
        return (string) $this->family_id;
    }

    public function setFamily_id(int $family_id): void
    {
        $this->family_id = $family_id;
    }

    public function getCustom_field_id(): string
    {
        return (string) $this->custom_field_id;
    }

    public function setCustom_field_id(int $custom_field_id): void
    {
        $this->custom_field_id = $custom_field_id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
