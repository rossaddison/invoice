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
     * The worker on the whole-house (`both`) allocation, if any -- what the
     * legacy single Inv.worker_id should read while the existing views still
     * use it. A split house has no single worker, so this is null for it.
     */
    public function legacyWorkerId(int $invId): ?int
    {
        $both = $this->repository->repoForInvSidequery($invId, AllocationSide::Both);
        return null === $both ? null : $both->reqWorkerId();
    }

    public function find(int $allocationId): ?InvWorkerAllocation
    {
        return $this->repository->repoAllocationquery($allocationId);
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
