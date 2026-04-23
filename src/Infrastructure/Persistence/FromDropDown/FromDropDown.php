<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\FromDropDown;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\FromDropDown\FromDropDownRepository::class)]

class FromDropDown
{
    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'text)', nullable: false)]
        private string $email = '', #[Column(type: 'bool', default: false, nullable: false)]
        private bool $include = false, #[Column(type: 'bool', default: false, nullable: false)]
        private bool $default_email = false) {}

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'FromDropDown has no ID (not persisted yet)'
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getInclude(): bool
    {
        return $this->include;
    }

    public function setInclude(bool $include): void
    {
        $this->include = $include;
    }

    public function getDefaultEmail(): bool
    {
        return $this->default_email;
    }

    public function setDefaultEmail(bool $default_email): void
    {
        $this->default_email = $default_email;
    }
}
