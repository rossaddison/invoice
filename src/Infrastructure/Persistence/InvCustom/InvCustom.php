<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvCustom;

use App\Infrastructure\Persistence\{
    CustomField\CustomField, Inv\Inv, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\InvCustom\InvCustomRepository::class)]
class InvCustom
{
    use RequireId;
 
    #[BelongsTo(target: Inv::class, nullable: false)]
    private ?Inv $inv = null;

    #[BelongsTo(target: CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $custom_field_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $value = '')
    {
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
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
        return $this->requireId($this->id, 'InvCustom');
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
