<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CategoryPrimary;

use App\Infrastructure\Persistence\{Trait\RequireId};
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as CPR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: CPR::class)]
class CategoryPrimary
{
    use RequireId;
    
    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'text', nullable: true)]
        public ?string $name = '',
    ) {
        $this->name = $name;
    }
    
    public function reqId(): int
    {
        return $this->requireId($this->id, 'CategoryPrimary');
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
