<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UserRbacLink;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\UserInv\UserRbacLinkRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;

/**
 * Bridge table linking userinv.user_id (INT) to yii_rbac_assignment.user_id (VARCHAR).
 * The BelongsTo relation causes Cycle ORM to create a DB-level FK:
 * user_rbac_link.user_id → userinv.user_id ON DELETE RESTRICT
 * so deletion of a UserInv with an active RBAC link is blocked at the DB layer.
 */
#[Entity(repository: UserRbacLinkRepository::class)]
#[Index(columns: ['user_id'], unique: true)]
#[Index(columns: ['rbac_user_id'], unique: true)]
class UserRbacLink
{
    use RequireId;

    #[BelongsTo(target: UserInv::class, nullable: false, fkAction: 'RESTRICT', innerKey: 'user_id', outerKey: 'user_id')]
    private ?UserInv $user_inv = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer', nullable: false)]
        private ?int $user_id = null,
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
        return $this->user_id;
    }

    public function getRbacUserId(): ?string
    {
        return $this->rbac_user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function setRbacUserId(?string $rbac_user_id): void
    {
        $this->rbac_user_id = $rbac_user_id;
    }
}
