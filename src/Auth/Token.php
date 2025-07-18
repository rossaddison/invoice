<?php

declare(strict_types=1);

namespace App\Auth;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeImmutable;
use Yiisoft\Security\Random;

#[Entity(repository: TokenRepository::class)]

class Token
{
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

    public function getIdentity(): ?Identity
    {
        return $this->identity;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getIdentity_id(): string
    {
        return (string) $this->identity_id;
    }

    public function setIdentity_id(int $identity_id): void
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

    public function getCreated_at(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->created_at */
        return $this->created_at;
    }

    public function setCreated_at(string $created_at): void
    {
        $this->created_at = (new DateTimeImmutable())->createFromFormat('Y-m-d h:i:s', $created_at) ?: new DateTimeImmutable('now');
    }
}
