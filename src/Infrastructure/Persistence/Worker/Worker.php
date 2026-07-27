<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Worker;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Worker\WorkerRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: WorkerRepository::class)]
class Worker
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    /**
     * The worker's own login, once the admin links one via the userinv/index
     * "Worker" role-assignment column — see UserInvRoleTrait::assignWorkerRole().
     * Left null for a worker who hasn't signed up / been linked yet.
     */
    #[BelongsTo(target: User::class, nullable: true, fkAction: 'NO ACTION')]
    private ?User $user = null;

    public function __construct(
        #[Column(type: 'string(100)')]
        private string $firstname = '',
        #[Column(type: 'string(100)')]
        private string $lastname = '',
        #[Column(type: 'bool', typecast: 'bool', default: true)]
        private bool $active = true,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Worker');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUserId(): ?int
    {
        return $this->user?->reqId();
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}
