<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\GentorRelation;

use App\Infrastructure\Persistence\{
    Gentor\Gentor, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\GeneratorRelation\GeneratorRelationRepository::class)]
class GentorRelation
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: Gentor::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Gentor $gentor = null;

    public function __construct(#[Column(type: 'text', nullable: true)]
        private ?string $lowercasename = '', #[Column(type: 'text', nullable: true)]
        private ?string $camelcasename = '', #[Column(type: 'text', nullable: true)]
        private ?string $view_field_name = '', #[Column(type: 'integer(11)', nullable: true, default: null)]
        private ?int $gentor_id = null, #[Column(type: 'text', nullable: true)]
        private ?string $foreign_key_column = '')
    {
    }

    public function reqRelationId(): int
    {
        return $this->requireId($this->id, 'Gentor Relation');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    //relation $gentor
    public function getGentor(): ?Gentor
    {
        return $this->gentor;
    }

    public function getLowercaseName(): ?string
    {
        return $this->lowercasename;
    }

    public function setLowercaseName(string $lowercasename): void
    {
        $this->lowercasename = $lowercasename;
    }

    public function getCamelcaseName(): ?string
    {
        return $this->camelcasename;
    }

    public function setCamelcaseName(string $camelcasename): void
    {
        $this->camelcasename = $camelcasename;
    }

    public function getViewFieldName(): ?string
    {
        return $this->view_field_name;
    }

    public function setViewFieldName(string $view_field_name): void
    {
        $this->view_field_name = $view_field_name;
    }

    public function reqGentorId(): ?int
    {
        return $this->gentor_id;
    }

    public function setGentorId(int $gentor_id): void
    {
        $this->gentor_id = $gentor_id;
    }

    // Explicit override of the real source FK column name on the owning
    // entity's table for this relation, used by the Entity.php generator
    // template when the target's primary key isn't an auto-increment int
    // -- see docs/CYCLE_ORM_GENTOR_RELATION_KEY_RESOLUTION_NOVEMBER_2026.md.
    // Left blank, the generator falls back to its conventional
    // '{relation}_id' guess, which only applies to auto-increment int
    // targets.
    public function getForeignKeyColumn(): ?string
    {
        return $this->foreign_key_column;
    }

    public function setForeignKeyColumn(string $foreign_key_column): void
    {
        $this->foreign_key_column = $foreign_key_column;
    }
}
