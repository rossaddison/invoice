<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\CategoryPrimary\CategoryPrimaryRepository::class)]
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

    public function getId(): int|null
    {
        return $this->id;
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
