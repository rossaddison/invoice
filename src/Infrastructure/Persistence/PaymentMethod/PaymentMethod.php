<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PaymentMethod;

use App\Infrastructure\Persistence\{Trait\RequireId};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\PaymentMethod\PaymentMethodRepository::class)]
class PaymentMethod
{
    use RequireId;
    
    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'bool', default: true)]
        private bool $active = true)
    {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'PaymentMethod');
    }

    public function hasIdentity(): bool
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

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
