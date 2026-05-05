<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UserClient;

use App\Infrastructure\Persistence\{
    Client\Client, Trait\RequireId, User\User
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\UserClient\UserClientRepository::class)]
class UserClient
{
    use RequireId;
 
    #[BelongsTo(target: User::class, nullable: false)]
    private ?User $user = null;

    #[BelongsTo(target: Client::class, nullable: false)]
    private ?Client $client = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $user_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null)
    {
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'UserClient');
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqUserId(): int
    {
        return $this->requireId($this->user_id, 'User');
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function reqClientId(): int
    {
        return $this->requireId($this->client_id, 'Client');
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }
}
