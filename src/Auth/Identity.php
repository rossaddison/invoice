<?php

declare(strict_types=1);

namespace App\Auth;

use App\User\User;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Yiisoft\Security\Random;
use Yiisoft\User\Login\Cookie\CookieLoginIdentityInterface;

#[Entity(repository: IdentityRepository::class)]
class Identity implements CookieLoginIdentityInterface
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'string(32)')]
    private string $authKey;

    #[BelongsTo(target: User::class, nullable: false, load: 'eager')]
    private ?User $user = null;

    /**
     * A #[HasOne(target: Identity::class)] relationship exists in the User table so no need for a user_id column here as
     * it gets built automatically by the User's HasOne Identity relationship.
     */
    public function __construct(
        ?User $user = null,
    ) {
        $this->authKey = $this->regenerateCookieLoginKey();
    }

    #[\Override]
    public function getId(): ?string
    {
        return (string) $this->id;
    }

    public function getUser_id(): ?string
    {
        if ($this->user) {
            return $this->user->getId();
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
     * Related logic: see src\Auth\AuthService logout function.
     */
    public function regenerateCookieLoginKey(): string
    {
        return Random::string(32);
    }
}
