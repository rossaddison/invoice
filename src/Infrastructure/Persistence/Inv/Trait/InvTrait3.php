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

    /**
     * Draft(1) -> sent(2) -> viewed(3) -> paid(4) only moves forward: once
     * an invoice is issued its ledger entries may already be exported to the
     * bookkeeping provider, so it can only be corrected with a credit note,
     * never by reverting its status. Statuses 5-13 (dunning stages) are
     * outside that sequence.
     */
    public function setStatusId(int $status_id): void
    {
        $target = !in_array($status_id, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14]) ? 1 : $status_id;
        $current = $this->status_id;
        if ($this->hasIdentity() && $current !== null && !$this->isStatusChangeAllowed($current, $target)) {
            return;
        }
        $this->status_id = $target;
    }

    /**
     * Void(14) is terminal and is only reachable from issued(2)/viewed(3);
     * the remaining rule is forward-only among draft, sent, viewed and paid.
     */
    private function isStatusChangeAllowed(int $current, int $target): bool
    {
        if ($current === 14) {
            return $target === 14;
        }
        if ($target === 14) {
            return $current === 2 || $current === 3;
        }
        return !($current >= 2 && $current <= 4 && $target < $current && $target <= 4);
    }

    public function isVoid(): bool
    {
        return $this->status_id === 14;
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

    public function getDoNotSend(): bool
    {
        return $this->do_not_send;
    }

    public function setDoNotSend(bool $do_not_send): void
    {
        $this->do_not_send = $do_not_send;
    }

    public function getDoNotSendReason(): string
    {
        return $this->do_not_send_reason;
    }

    public function setDoNotSendReason(string $do_not_send_reason): void
    {
        $this->do_not_send_reason = $do_not_send_reason;
    }

    /**
     * True when a HomeCare field worker has flagged this invoice from
     * inv/guest — every status-to-"sent" transition must check this first.
     */
    public function blocksSending(): bool
    {
        return $this->do_not_send;
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
