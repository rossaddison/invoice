<?php

declare(strict_types=1);

namespace App\User;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;

#[Entity(repository: RecoveryCodeRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
class RecoveryCode
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime', nullable: false)]
    private readonly \DateTimeImmutable $date_created;

    public function __construct(
        #[BelongsTo(target: User::class, nullable: false)]
        private User $user,
        #[Column(type: 'string(255)', nullable: false)]
        private string $code_hash = '',
        #[Column(type: 'bool', typecast: 'bool', default: false)]
        private bool $used = false,
    ) {
        $this->date_created = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the associated User entity.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function getCodeHash(): string
    {
        return $this->code_hash;
    }

    public function setCodeHash(string $code_hash): void
    {
        $this->code_hash = $code_hash;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): void
    {
        $this->used = $used;
    }

    public function getDateCreated(): \DateTimeImmutable
    {
        return $this->date_created;
    }
}
