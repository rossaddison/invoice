<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UserCustom;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\UserCustom\UserCustomRepository;
use App\User\User;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: UserCustomRepository::class)]
class UserCustom
{
    use RequireId;

    #[BelongsTo(target: User::class, nullable: false)]
    private ?User $user = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $user_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $fieldid = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $fieldvalue = '',
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'UserCustom');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getFieldid(): ?int
    {
        return $this->fieldid;
    }

    public function setFieldid(int $fieldid): void
    {
        $this->fieldid = $fieldid;
    }

    public function getFieldvalue(): ?string
    {
        return $this->fieldvalue;
    }

    public function setFieldvalue(string $fieldvalue): void
    {
        $this->fieldvalue = $fieldvalue;
    }
}
