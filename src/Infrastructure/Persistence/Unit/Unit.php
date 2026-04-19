<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Unit;

use App\Invoice\Unit\UnitRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: UnitRepository::class)]
class Unit
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'string(50)')]
        private string $unit_name = '',
        #[Column(type: 'string(50)')]
        private string $unit_name_plrl = '',
    ) {
    }

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException('Unit has no ID (not persisted yet)');
        }
        return $this->id;
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
