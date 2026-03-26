<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\Group\GroupRepository::class)]
class Group
{
    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'string(191)', nullable: true)]
        private ?string $identifier_format = '',
        #[Column(type: 'integer', nullable: true)]
        private ?int $next_id = null,
        #[Column(type: 'integer', nullable: true, default: 0)]
        private ?int $left_pad = null,
    ) {
    }

    public function getId(): string
    {
        return (string) $this->id;
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

    public function getIdentifierFormat(): ?string
    {
        return $this->identifier_format;
    }

    public function setIdentifierFormat(string $identifier_format): void
    {
        $this->identifier_format = $identifier_format;
    }

    public function getNextId(): string
    {
        return (string) $this->next_id;
    }

    public function setNextId(int $next_id): void
    {
        $this->next_id = $next_id;
    }

    public function getLeftPad(): ?int
    {
        return $this->left_pad;
    }

    public function setLeftPad(int $left_pad): void
    {
        $this->left_pad = $left_pad;
    }
}
