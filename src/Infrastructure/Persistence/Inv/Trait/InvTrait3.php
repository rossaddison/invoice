<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Inv\Trait;

use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvTrait3
{

    public function getContractId(): ?int
    {
        return $this->contract_id;
    }

    public function setContractId(int $contract_id): void
    {
        $this->contract_id = $contract_id;
    }

    public function reqStatusId(): int
    {
        return $this->requireId($this->status_id, 'Status');
    }

    public function setStatusId(int $status_id): void
    {
        $this->status_id = (!in_array($status_id,
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]) ? 1 : $status_id);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deleted_at;
    }

    public function restore(): void
    {
        $this->deleted_at = null;
    }

    public function getIsReadOnly(): bool
    {
        return $this->is_read_only ? true : false;
    }

    public function setIsReadOnly(bool $is_read_only): void
    {
        $this->is_read_only = $is_read_only;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Same as date issued
     * @return DateTimeImmutable
     */
    public function getDateCreated(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_created */
        return $this->date_created;
    }

    public function setDateCreated(string $date_created): void
    {
        $this->date_created =  new DateTimeImmutable()
        ->createFromFormat('Y-m-d', $date_created)
        ?: new DateTimeImmutable('now');
    }

    public function setTimeCreated(string $time_created): void
    {
        $this->time_created = $time_created;
    }

    public function getTimeCreated(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->time_created */
        return $this->time_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }
}
