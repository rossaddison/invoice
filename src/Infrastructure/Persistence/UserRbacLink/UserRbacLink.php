<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UserRbacLink;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\UserInv\UserRbacLinkRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: UserRbacLinkRepository::class)]
class UserRbacLink
{
    use RequireId;

    #[BelongsTo(target: UserInv::class, nullable: false)]
    private ?UserInv $user_inv = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer', nullable: false)]
        private ?int $user_inv_id = null,
        #[Column(type: 'string(126)', nullable: false)]
        private ?string $rbac_user_id = null,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'UserRbacLink');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function getUserInv(): ?UserInv
    {
        return $this->user_inv;
    }

    public function setUserInv(?UserInv $user_inv): void
    {
        $this->user_inv = $user_inv;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getUserId(): ?int
    {
        return $this->user_inv?->reqUserId();
    }

    public function getRbacUserId(): ?string
    {
        return $this->rbac_user_id;
    }

    public function setUserInvId(?int $user_inv_id): void
    {
        $this->user_inv_id = $user_inv_id;
    }

    public function setRbacUserId(?string $rbac_user_id): void
    {
        $this->rbac_user_id = $rbac_user_id;
    }
}
