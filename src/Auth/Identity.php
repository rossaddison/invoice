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

    public function __construct()
    {
        $this->authKey = $this->regenerateCookieLoginKey();
    }

    public function getId(): ?string
    {
        if ($this->user) {
          return $this->user->getId();
        }
        return null;
    }

    public function getCookieLoginKey(): string
    {
        return $this->authKey;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function validateCookieLoginKey(string $key): bool
    {
        return $this->authKey === $key;
    }
    
    /**
     * Regenerate after logout
     * @see src\Auth\AuthService logout function 
     * @return string
     */
    public function regenerateCookieLoginKey(): string
    {
        return Random::string(32);
    }
}
