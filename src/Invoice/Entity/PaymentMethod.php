<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\PaymentMethod\PaymentMethodRepository::class)]
class PaymentMethod
{
    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'text', nullable: true)]
        private ?string $name = '', #[Column(type: 'bool', default: true)]
        private bool $active = true)
    {
    }

    public function getId(): string
    {
        return (string)$this->id;
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

    public function getActive(): bool|null
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
