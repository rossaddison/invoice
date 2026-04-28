<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Group;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\Group\GroupRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: GroupRepository::class)]
class Group
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
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

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Group');
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

    public function getIdentifierFormat(): ?string
    {
        return $this->identifier_format;
    }

    public function setIdentifierFormat(string $identifier_format): void
    {
        $this->identifier_format = $identifier_format;
    }

    public function getNextId(): ?int
    {
        return $this->next_id;
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
