<?php

declare(strict_types=1);

namespace App\User;

use App\Auth\Identity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use Yiisoft\Security\PasswordHasher;

#[Entity(repository: UserRepository::class)]
#[Index(columns: ['login'], unique: true)]
#[Behavior\CreatedAt(field: 'created_at', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updated_at', column: 'updated_at')]
class User
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'string')]
    private string $passwordHash = '';

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $created_at;

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $updated_at;

    #[HasOne(target: Identity::class)]
    private readonly Identity $identity;

    #[Column(type: 'bool', default: false)]
    private bool $tfa_enabled = false;

    #[Column(type: 'string', nullable: true)]
    private ?string $totpSecret = '';

    public function __construct
    (
        #[Column(type: 'string(48)')] private string $login,
        #[Column(type: 'string(254)')] private readonly string $email,
        string $password,
    ) {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
        $this->setPassword($password);
        // Generate a new auth key on signup
        $this->identity = new Identity();
    }

    /**
     * @return numeric-string|null
     */
    public function getId(): string|null
    {
        return $this->id === null ? null : (string) $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function validatePassword(string $password): bool
    {
        return (new PasswordHasher())->validate($password, $this->passwordHash);
    }

    public function setPassword(string $password): void
    {
        $this->passwordHash = (new PasswordHasher())->hash($password);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function getIdentity(): Identity
    {
        return $this->identity;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $secret): void
    {
        $this->totpSecret = $secret;
    }

    public function is2FAEnabled(): bool
    {
        return $this->tfa_enabled ;
    }

    public function set2FAEnabled(bool $enabled): void
    {
        $this->tfa_enabled = $enabled;
    }
}
