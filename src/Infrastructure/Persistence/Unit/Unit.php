<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Unit;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\Unit\UnitRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: UnitRepository::class)]
class Unit
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'string(50)')]
        private string $unit_name = '',
        #[Column(type: 'string(50)')]
        private string $unit_name_plrl = '',
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Unit');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUnitName(): string
    {
        return $this->unit_name;
    }

    public function setUnitName(string $unit_name): void
    {
        $this->unit_name = $unit_name;
    }

    public function getUnitNamePlrl(): string
    {
        return $this->unit_name_plrl;
    }

    public function setUnitNamePlrl(string $unit_name_plrl): void
    {
        $this->unit_name_plrl = $unit_name_plrl;
    }
}
