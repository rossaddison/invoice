<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Identity;

use App\Auth\IdentityRepository;
use App\Infrastructure\Persistence\Trait\RequireId;
use App\Infrastructure\Persistence\User\User;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Yiisoft\Security\Random;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;

#[Entity(repository: IdentityRepository::class)]
class Identity implements CookieLoginIdentityInterface
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'string(32)')]
    private string $authKey;

    #[BelongsTo(target: User::class, nullable: false, load: 'eager')]
    private ?User $user = null;

    /**
     * A #[HasOne(target: Identity::class)] relationship exists in the User
     *  table so no need for a user_id column here as
     * it gets built automatically by the User's HasOne Identity relationship
     */
    public function __construct()
    {
        $this->authKey = $this->regenerateCookieLoginKey();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Identity');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    #[\Override]
    public function getId(): ?string
    {
        return $this->hasIdentity() ? (string) $this->reqId() : null;
    }

    public function getUserId(): ?int
    {
        if ($this->user) {
            return $this->user->reqId();
        }
        return null;
    }

    public function generateAuthKey(): void
    {
        $this->authKey = Random::string(32);
    }

    #[\Override]
    public function getCookieLoginKey(): string
    {
        return $this->authKey;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    #[\Override]
    public function validateCookieLoginKey(string $key): bool
    {
        return $this->authKey === $key;
    }

    /**
     * Regenerate after logout / new Identity() after signing up
     * Related logic: see src\Auth\AuthService logout function
     */
    public function regenerateCookieLoginKey(): string
    {
        return Random::string(32);
    }
}
