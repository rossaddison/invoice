<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\FamilyCustom;

use App\Infrastructure\Persistence\{
    CustomField\CustomField,
    Family\Family,
    Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\FamilyCustom\FamilyCustomRepository::class)]
class FamilyCustom
{
    use RequireId;

    #[BelongsTo(target: Family::class, nullable: false)]
    private ?Family $family = null;

    #[BelongsTo(target: CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $family_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $custom_field_id = null,
        #[Column(type: 'text', nullable: true)]
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

    public function reqId(): int
    {
        return $this->requireId($this->id, 'FamilyCustom');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    public function reqFamilyId(): int
    {
        return $this->requireId($this->family_id, 'Family');
    }

    public function setFamilyId(int $family_id): void
    {
        $this->family_id = $family_id;
    }

    public function reqCustomFieldId(): int
    {
        return $this->requireId($this->custom_field_id, 'Custom Field');
    }

    public function setCustomFieldId(int $custom_field_id): void
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
