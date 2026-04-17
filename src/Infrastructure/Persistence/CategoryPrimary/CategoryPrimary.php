<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CategoryPrimary;

use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as CPR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: CPR::class)]
class CategoryPrimary
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'text', nullable: true)]
        public ?string $name = '',
    ) {
        $this->name = $name;
    }
    
    /**
     * Returns the database identifier for this CategoryPrimary
     *
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'CategoryPrimary has no ID (not persisted yet)'
            );
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
