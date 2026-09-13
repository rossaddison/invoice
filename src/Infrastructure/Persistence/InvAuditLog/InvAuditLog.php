<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvAuditLog;

use App\Infrastructure\Persistence\{
    Inv\Inv, Trait\RequireId, User\User
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use DateTimeImmutable;

/**
 * One row per InvService::saveInv() call that either creates an invoice or
 * changes one of its audit-worthy fields (see
 * InvService::AUDITED_SNAPSHOT_FIELDS).
 *
 * $user is the actual signed-in staff member who triggered the save,
 * resolved via UserService::getUser() at save time -- deliberately NOT the
 * same thing as Inv::user_id, which represents the client's own linked
 * portal account (InvController::activeUser()), not an editor. Nullable
 * because system-triggered saves (e.g. the recurring-invoice cron) have no
 * interactively signed-in user to attribute the change to.
 *
 * $changed_fields is a JSON-encoded map of {field: {old, new}} for the
 * fields that actually differed on this save; null for a 'created' row,
 * since there is no prior state to diff against. The invoice's own
 * password field's VALUE is deliberately never recorded here -- see
 * InvService::snapshotAuditFields() -- only whether it changed, so a
 * shareable-link password can't leak into a log other staff can read.
 */
#[Entity(repository: \App\Invoice\InvAuditLog\InvAuditLogRepository::class)]
#[Index(columns: ['inv_id'])]
#[Index(columns: ['user_id'])]
#[Index(columns: ['changed_at'])]
class InvAuditLog
{
    use RequireId;

    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'CASCADE')]
    private ?Inv $inv = null;

    #[BelongsTo(target: User::class, nullable: true, fkAction: 'SET NULL')]
    private ?User $user = null;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $changed_at;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $user_id = null,
        // 'created' | 'updated' | 'deleted' | 'restored' -- a plain
        // string column, matching this codebase's general preference for
        // status-style columns (e.g. Inv::status_id) over a dedicated
        // Cycle-mapped PHP enum.
        #[Column(type: 'string(16)', nullable: false)]
        private string $action = 'updated',
        #[Column(type: 'text', nullable: true)]
        private ?string $changed_fields = null,
    ) {
        $this->changed_at = new DateTimeImmutable('now');
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'InvAuditLog');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getChangedAt(): DateTimeImmutable
    {
        return $this->changed_at;
    }

    public function getChangedFields(): ?string
    {
        return $this->changed_fields;
    }
}
