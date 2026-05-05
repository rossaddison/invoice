<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UnitPeppol;

use App\Infrastructure\Persistence\{
   Unit\Unit, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\UnitPeppol\UnitPeppolRepository::class)]

class UnitPeppol
{
    use RequireId;
    
    #[BelongsTo(target: Unit::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Unit $unit = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $unit_id = null,
        #[Column(type: 'string(3)', nullable: false)]
        private string $code = '',
        #[Column(type: 'string(120)', nullable: false)]
        private string $name = '',
        #[Column(type: 'longText', nullable: false)]
        private string $description = '')
    {
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): void
    {
        $this->unit = $unit;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'UnitPeppol');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqUnitId(): int
    {
        return $this->requireId($this->unit_id, 'Unit');
    }

    public function setUnitId(int $unit_id): void
    {
        $this->unit_id = $unit_id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
