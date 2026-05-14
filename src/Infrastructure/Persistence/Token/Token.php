<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Token;

use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;
use Yiisoft\Security\Random;

#[Entity(repository: TokenRepository::class)]
class Token
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: Identity::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Identity $identity = null;

    #[Column(type: 'string(32)', nullable: false)]
    private ?string $token = null;

    #[Column(type: 'datetime)', nullable: false)]
    private DateTimeImmutable $created_at;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $identity_id = null,
        #[Column(type: 'string(50)', nullable: false)]
        private ?string $type = '',
    ) {
        $this->token = Random::string(32);
        $this->created_at = new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Token');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function getIdentity(): ?Identity
    {
        return $this->identity;
    }

    public function getIdentityId(): ?int
    {
        return $this->identity_id;
    }

    public function setIdentityId(int $identity_id): void
    {
        $this->identity_id = $identity_id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = new DateTimeImmutable()->createFromFormat('Y-m-d h:i:s', $created_at) ?: new DateTimeImmutable('now');
    }
}
