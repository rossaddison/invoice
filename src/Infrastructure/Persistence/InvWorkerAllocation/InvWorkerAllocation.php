<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvWorkerAllocation;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use DateTimeImmutable;

/**
 * One worker's allocation to one side of one house (invoice) on a HomeCare
 * window-cleaning run: exactly one worker per (inv_id, side). A house is
 * allocated either whole (side `both`) or split into `front` and `back`,
 * never a mix — see {@see AllocationSide::conflictsWith()}, enforced by
 * InvWorkerAllocationService.
 *
 * The worker settles an allocation as completed, postponed or cancelled
 * ({@see decide()}); only the allocated worker or the lead may do so. There
 * is deliberately no timing data beyond the decision time — completion is an
 * attribution record (who cleaned which side), not a performance measure.
 *
 * inv_id and the two worker ids are plain scalar FKs, deliberately not Cycle
 * `#[BelongsTo]` relations, matching Dwelling/Client::$dwelling_id.
 *
 * No `#[Index]` yet, deliberately: with MergeIndexes registered in STAGE_INDEX
 * (rossaddison/yii-cycle-1, see docs/CYCLE_ORM_GENTOR_RELATION_KEY_RESOLUTION_
 * NOVEMBER_2026.md) an index on a table that does not exist in the database
 * yet fails the whole schema build with "Undefined column" -- which would
 * take the app down on a fresh `rm runtime/schema.php`. One worker per
 * (inv_id, side) is enforced by InvWorkerAllocationService::allocate() for
 * now; add `#[Index(columns: ['inv_id', 'side'], unique: true)]` and
 * `#[Index(columns: ['worker_id'])]` once the table exists (or the ordering
 * is fixed in the fork).
 */
#[Entity(repository: InvWorkerAllocationRepository::class)]
class InvWorkerAllocation
{
    use RequireId;

    /** decision_source of a side the customer confirmed by scanning their QR code. */
    public const string SOURCE_CUSTOMER_SCAN = 'customer_scan';

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        /** An {@see AllocationSide} value. */
        #[Column(type: 'string(5)', nullable: false)]
        private string $side = 'both',
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $worker_id = null,
        #[Column(type: 'datetime', nullable: false, typecast: 'datetime')]
        private ?DateTimeImmutable $allocated_at = null,
        /** An {@see AllocationStatus} value. */
        #[Column(type: 'string(10)', nullable: false, default: 'allocated')]
        private string $status = 'allocated',
        #[Column(type: 'datetime', nullable: true, typecast: 'datetime')]
        private ?DateTimeImmutable $decided_at = null,
        /** Who settled it: the allocated worker, the lead, or null for the office (run sheet). */
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $decided_by_worker_id = null,
        /** A {@see PostponeReason} value; only ever set on a postponed row. */
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $postpone_reason = null,
        /**
         * How it was settled when not by a worker, the lead or an admin: null, or
         * {@see self::SOURCE_CUSTOMER_SCAN}. Lets the grid say "confirmed by customer scan"
         * so a completion nobody on the crew recorded is never mistaken for one.
         */
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $decision_source = null,
    ) {
        $this->allocated_at ??= new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'InvWorkerAllocation');
    }

    public function isPersisted(): bool
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

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getSide(): AllocationSide
    {
        return AllocationSide::from($this->side);
    }

    public function setSide(AllocationSide $side): void
    {
        $this->side = $side->value;
    }

    public function reqWorkerId(): int
    {
        return $this->requireId($this->worker_id, 'Worker');
    }

    public function setWorkerId(int $worker_id): void
    {
        $this->worker_id = $worker_id;
    }

    public function getAllocatedAt(): DateTimeImmutable
    {
        return $this->allocated_at ?? new DateTimeImmutable();
    }

    public function getStatus(): AllocationStatus
    {
        return AllocationStatus::from($this->status);
    }

    public function getDecidedAt(): ?DateTimeImmutable
    {
        return $this->decided_at;
    }

    public function getDecidedByWorkerId(): ?int
    {
        return $this->decided_by_worker_id;
    }

    public function getPostponeReason(): ?PostponeReason
    {
        return $this->postpone_reason === null ? null : PostponeReason::from($this->postpone_reason);
    }

    public function getDecisionSource(): ?string
    {
        return $this->decision_source;
    }

    public function isConfirmedByCustomerScan(): bool
    {
        return self::SOURCE_CUSTOMER_SCAN === $this->decision_source;
    }

    public function isDecided(): bool
    {
        return $this->getStatus()->isDecision();
    }

    /**
     * (Re)allocates this side to a worker: a fresh, undecided allocation, so
     * a worker change after a decision starts the side over.
     */
    public function allocateTo(int $workerId, DateTimeImmutable $at): void
    {
        $this->worker_id = $workerId;
        $this->allocated_at = $at;
        $this->status = AllocationStatus::Allocated->value;
        $this->decided_at = null;
        $this->decided_by_worker_id = null;
        $this->postpone_reason = null;
        $this->decision_source = null;
    }

    /**
     * Settles the allocation. Only an undecided allocation can be settled,
     * and only into completed, postponed or cancelled; a reason is kept only
     * when postponing. $byWorkerId is null when the office settled it (the
     * paper run sheet's Apply step), not a worker. $source says how, when not a person:
     * see {@see self::SOURCE_CUSTOMER_SCAN}.
     *
     * @throws \LogicException when already decided, or $status is not a decision
     */
    public function decide(
        AllocationStatus $status,
        ?int $byWorkerId,
        DateTimeImmutable $at,
        ?PostponeReason $reason = null,
        ?string $source = null,
    ): void {
        if (!$status->isDecision()) {
            throw new \LogicException('An allocation can only be completed, postponed or cancelled.');
        }
        if ($this->isDecided()) {
            throw new \LogicException('This allocation has already been ' . $this->status . '.');
        }
        $this->status = $status->value;
        $this->decided_at = $at;
        $this->decided_by_worker_id = $byWorkerId;
        $this->postpone_reason = $status === AllocationStatus::Postponed ? $reason?->value : null;
        $this->decision_source = $source;
    }
}
