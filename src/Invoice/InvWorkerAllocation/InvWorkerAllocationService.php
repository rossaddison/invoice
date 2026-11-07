<?php

declare(strict_types=1);

namespace App\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use DateTimeImmutable;

/**
 * The rules for allocating HomeCare workers to the sides of a house and for
 * settling those allocations. Kept apart from the controllers so the rules
 * are testable against a mocked repository:
 *
 *  - exactly one worker per (house, side);
 *  - a house is allocated whole (`both`) or split (`front` + `back`), never
 *    a mix — allocating one kind clears the other;
 *  - an allocation is settled as completed, postponed or cancelled once, by
 *    the allocated worker or the lead; re-allocating the side starts it over.
 */
final readonly class InvWorkerAllocationService
{
    public function __construct(private InvWorkerAllocationRepositoryInterface $repository)
    {
    }

    /**
     * Allocates a side of a house to a worker. Any allocation on a side that
     * cannot coexist with this one is removed; an existing allocation on this
     * side is reused (and restarted when the worker changes or it was already
     * settled), so a repeat of the same call changes nothing.
     */
    public function allocate(
        int $invId,
        AllocationSide $side,
        int $workerId,
        ?DateTimeImmutable $at = null,
    ): InvWorkerAllocation {
        $at ??= new DateTimeImmutable();
        $conflicting = $side->conflictsWith();
        $existing = null;
        foreach ($this->repository->repoForInvquery($invId) as $allocation) {
            if ($allocation->getSide() === $side) {
                $existing = $allocation;
            } elseif (in_array($allocation->getSide(), $conflicting, true)) {
                $this->repository->delete($allocation);
            }
        }
        if (null === $existing) {
            $allocation = new InvWorkerAllocation($invId, $side->value, $workerId, $at);
            $this->repository->save($allocation);
            return $allocation;
        }
        if ($existing->reqWorkerId() !== $workerId || $existing->isDecided()) {
            $existing->allocateTo($workerId, $at);
            $this->repository->save($existing);
        }
        return $existing;
    }

    /**
     * Turns an open whole-house allocation into the same worker on the front AND
     * the back, so one side can then be changed without losing the other. A house
     * with no whole-house row is left alone. Returns false only when the
     * whole-house row is already settled (completed, postponed or cancelled):
     * that record must not be rewritten.
     */
    public function splitWholeHouse(int $invId): bool
    {
        $both = $this->repository->repoForInvSidequery($invId, AllocationSide::Both);
        if (null === $both) {
            return true;
        }
        if ($both->isDecided()) {
            return false;
        }
        $workerId = $both->reqWorkerId();
        // allocating the front removes the whole-house row (they conflict)
        $this->allocate($invId, AllocationSide::Front, $workerId);
        $this->allocate($invId, AllocationSide::Back, $workerId);
        return true;
    }

    /**
     * Removes the allocation on one side of a house, if there is one.
     */
    public function clear(int $invId, AllocationSide $side): void
    {
        $allocation = $this->repository->repoForInvSidequery($invId, $side);
        if (null !== $allocation) {
            $this->repository->delete($allocation);
        }
    }

    /**
     * Settles an allocation as completed, postponed or cancelled.
     *
     * @param int|null $leadWorkerId the one lead worker, who may settle anyone's allocation
     * @throws \DomainException when the actor is neither the allocated worker nor the lead
     * @throws \LogicException when already settled, or $status is not a decision
     */
    public function decide(
        InvWorkerAllocation $allocation,
        AllocationStatus $status,
        int $actorWorkerId,
        ?int $leadWorkerId,
        ?PostponeReason $reason = null,
        ?DateTimeImmutable $at = null,
    ): void {
        if (!$this->mayDecide($allocation, $actorWorkerId, $leadWorkerId)) {
            throw new \DomainException('Only the allocated worker or the lead may settle this allocation.');
        }
        $allocation->decide($status, $actorWorkerId, $at ?? new DateTimeImmutable(), $reason);
        $this->repository->save($allocation);
    }

    /**
     * An admin (the office) settles any open allocation. Recorded with no worker, as the
     * run sheet does (decided_by_worker_id null = office).
     *
     * @throws \LogicException when already settled, or $status is not a decision
     */
    public function decideAsOffice(
        InvWorkerAllocation $allocation,
        AllocationStatus $status,
        ?PostponeReason $reason = null,
        ?DateTimeImmutable $at = null,
    ): void {
        $allocation->decide($status, null, $at ?? new DateTimeImmutable(), $reason);
        $this->repository->save($allocation);
    }

    /**
     * The worker on the whole-house (`both`) allocation, if any -- what the
     * legacy single Inv.worker_id should read while the existing views still
     * use it. A split house has no single worker, so this is null for it.
     */
    public function legacyWorkerId(int $invId): ?int
    {
        $both = $this->repository->repoForInvSidequery($invId, AllocationSide::Both);
        return null === $both ? null : $both->reqWorkerId();
    }

    /**
     * Every allocation on one house, whichever side.
     *
     * @return list<InvWorkerAllocation>
     */
    public function forInv(int $invId): array
    {
        return $this->repository->repoForInvquery($invId);
    }

    public function hasAllocations(int $invId): bool
    {
        return [] !== $this->forInv($invId);
    }

    public function find(int $allocationId): ?InvWorkerAllocation
    {
        return $this->repository->repoAllocationquery($allocationId);
    }

    /**
     * True when the house is allocated side by side (front and/or back), so a
     * single worker cannot describe it.
     */
    public function isSplit(int $invId): bool
    {
        foreach ($this->forInv($invId) as $allocation) {
            if (AllocationSide::Both !== $allocation->getSide()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gives an existing whole-house allocation to another worker (a paper run
     * sheet reassigning the house). A house with no whole-house row is left
     * alone; returns whether one was reassigned.
     */
    public function reassignWholeHouse(int $invId, int $workerId): bool
    {
        if (null === $this->repository->repoForInvSidequery($invId, AllocationSide::Both)) {
            return false;
        }
        $this->allocate($invId, AllocationSide::Both, $workerId);
        return true;
    }

    /**
     * The paper run sheet, checked thoroughly before it is submitted, says the
     * house was done: complete every still-open allocation on it, recorded as
     * settled by the office (no worker). Returns how many were completed.
     */
    public function settleFromRunSheet(int $invId, ?DateTimeImmutable $at = null): int
    {
        $at ??= new DateTimeImmutable();
        $settled = 0;
        foreach ($this->forInv($invId) as $allocation) {
            if (!$allocation->isDecided()) {
                $allocation->decide(AllocationStatus::Completed, null, $at);
                $this->repository->save($allocation);
                $settled++;
            }
        }
        return $settled;
    }

    /**
     * The customer scanned their QR code and confirmed the house was cleaned: every side
     * still waiting is completed, recorded as a customer-scan confirmation (no worker).
     * Sides already completed, postponed or cancelled are left as they are. Returns how
     * many were completed.
     */
    public function confirmByCustomerScan(int $invId, ?DateTimeImmutable $at = null): int
    {
        $at ??= new DateTimeImmutable();
        $confirmed = 0;
        foreach ($this->forInv($invId) as $allocation) {
            if (!$allocation->isDecided()) {
                $allocation->decide(
                    AllocationStatus::Completed,
                    null,
                    $at,
                    null,
                    InvWorkerAllocation::SOURCE_CUSTOMER_SCAN,
                );
                $this->repository->save($allocation);
                $confirmed++;
            }
        }
        return $confirmed;
    }

    /**
     * Records that the card was left at the house (see {@see InvWorkerAllocation::markCarded()}).
     * Idempotent: a side already carded keeps its first time and is not saved again.
     *
     * @throws \LogicException when the side is postponed or cancelled
     */
    public function markCarded(InvWorkerAllocation $allocation, ?DateTimeImmutable $at = null): void
    {
        if ($allocation->isCarded()) {
            return;
        }
        $allocation->markCarded($at ?? new DateTimeImmutable());
        $this->repository->save($allocation);
    }

    public function mayDecide(InvWorkerAllocation $allocation, int $actorWorkerId, ?int $leadWorkerId): bool
    {
        return $allocation->reqWorkerId() === $actorWorkerId
            || (null !== $leadWorkerId && $leadWorkerId === $actorWorkerId);
    }

    /**
     * One-off carry-over of a house's single legacy `Inv.worker_id` into the
     * table, as a whole-house allocation. Idempotent: a house that already
     * has any allocation is left alone. Returns whether a row was created.
     */
    public function backfillLegacy(int $invId, int $workerId, ?DateTimeImmutable $allocatedAt): bool
    {
        if ([] !== $this->repository->repoForInvquery($invId)) {
            return false;
        }
        $this->repository->save(new InvWorkerAllocation($invId, AllocationSide::Both->value, $workerId, $allocatedAt));
        return true;
    }
}
