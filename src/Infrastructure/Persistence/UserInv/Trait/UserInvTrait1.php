<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UserInv\Trait;

use DateTimeImmutable;
use App\Infrastructure\Persistence\User\User;
use Yiisoft\Translator\TranslatorInterface as Translator;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait UserInvTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'UserInv');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Use the getUser relation to retrieve the User Table email field
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function reqUserId(): int
    {
        return $this->requireId($this->user_id, 'User');
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function getActiveLabel(Translator $translator): string
    {
        return $this->active ? '<span class="label active">' . $translator->translate('yes') . '</span>' : '<span class="label inactive">' . $translator->translate('no') . '</span>';
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }
}
