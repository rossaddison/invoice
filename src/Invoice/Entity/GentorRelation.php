<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\GeneratorRelation\GeneratorRelationRepository::class)]
class GentorRelation
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: Gentor::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Gentor $gentor = null;

    public function __construct(#[Column(type: 'text', nullable: true)]
        private ?string $lowercasename = '', #[Column(type: 'text', nullable: true)]
        private ?string $camelcasename = '', #[Column(type: 'text', nullable: true)]
        private ?string $view_field_name = '', #[Column(type: 'integer(11)', nullable: true, default: null)]
        private ?int $gentor_id = null)
    {
    }

    public function getRelationId(): string
    {
        return (string) $this->id;
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

    public function getGentorId(): ?int
    {
        return $this->gentor_id;
    }

    public function setGentorId(int $gentor_id): void
    {
        $this->gentor_id = $gentor_id;
    }
}
