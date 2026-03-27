<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\Unit\UnitRepository::class)]
class Unit
{
    public function __construct(#[Column(type: 'primary')]
        public ?int $id = null, #[Column(type: 'string(50)')]
        private string $unit_name = '', #[Column(type: 'string(50)')]
        private string $unit_name_plrl = '')
    {
    }

    public function getUnitId(): ?int
    {
        return $this->id;
    }

    public function setUnitId(int $id): void
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
