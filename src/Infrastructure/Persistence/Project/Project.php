<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Project;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Project\ProjectRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: ProjectRepository::class)]
class Project
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)] private ?int $client_id = null,
        #[Column(type: 'text', nullable: true)] private ?string $name = '',
    ) {}

    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException('Project has no ID (not persisted yet)');
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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
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
