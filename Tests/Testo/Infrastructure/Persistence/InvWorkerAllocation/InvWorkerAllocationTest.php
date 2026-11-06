<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\InvWorkerAllocation;

use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use DateTimeImmutable;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * One worker's allocation to one side of one house, and its settle-once
 * lifecycle: allocated -> completed | postponed | cancelled.
 */
#[Test]
final class InvWorkerAllocationTest
{
    private function allocation(): InvWorkerAllocation
    {
        return new InvWorkerAllocation(12, AllocationSide::Front->value, 7, new DateTimeImmutable('2026-11-06 09:00:00'));
    }

    public function startsUnpersistedAndUndecided(): void
    {
        $allocation = $this->allocation();

        Assert::false($allocation->isPersisted());
        Assert::same($allocation->reqInvId(), 12);
        Assert::same($allocation->reqWorkerId(), 7);
        Assert::same($allocation->getSide(), AllocationSide::Front);
        Assert::same($allocation->getStatus(), AllocationStatus::Allocated);
        Assert::false($allocation->isDecided());
        Assert::null($allocation->getDecidedAt());
        Assert::null($allocation->getDecidedByWorkerId());
        Assert::null($allocation->getPostponeReason());
    }

    public function isPersistedOnceItHasAnId(): void
    {
        $allocation = $this->allocation();
        $allocation->setId(5);

        Assert::true($allocation->isPersisted());
        Assert::same($allocation->reqId(), 5);
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        $this->allocation()->reqId();
    }

    #[ExpectException(\LogicException::class)]
    public function reqInvIdThrowsWhenNotSet(): void
    {
        (new InvWorkerAllocation())->reqInvId();
    }

    #[ExpectException(\LogicException::class)]
    public function reqWorkerIdThrowsWhenNotSet(): void
    {
        (new InvWorkerAllocation())->reqWorkerId();
    }

    public function defaultsToAWholeHouseAllocatedNow(): void
    {
        $allocation = new InvWorkerAllocation(1, worker_id: 2);

        Assert::same($allocation->getSide(), AllocationSide::Both);
        Assert::same($allocation->getStatus(), AllocationStatus::Allocated);
        Assert::true($allocation->getAllocatedAt() <= new DateTimeImmutable());
    }

    public function sideRoundTripsThroughItsStoredString(): void
    {
        $allocation = $this->allocation();
        $allocation->setSide(AllocationSide::Back);

        Assert::same($allocation->getSide(), AllocationSide::Back);
    }

    public function completingRecordsWhoAndWhen(): void
    {
        $allocation = $this->allocation();
        $at = new DateTimeImmutable('2026-11-06 10:15:00');

        $allocation->decide(AllocationStatus::Completed, 7, $at);

        Assert::same($allocation->getStatus(), AllocationStatus::Completed);
        Assert::true($allocation->isDecided());
        Assert::same($allocation->getDecidedAt(), $at);
        Assert::same($allocation->getDecidedByWorkerId(), 7);
    }

    public function postponingKeepsTheReason(): void
    {
        $allocation = $this->allocation();

        $allocation->decide(AllocationStatus::Postponed, 3, new DateTimeImmutable(), PostponeReason::GateLocked);

        Assert::same($allocation->getStatus(), AllocationStatus::Postponed);
        Assert::same($allocation->getPostponeReason(), PostponeReason::GateLocked);
        Assert::same($allocation->getDecidedByWorkerId(), 3);
    }

    public function aReasonIsDroppedUnlessPostponing(): void
    {
        $completed = $this->allocation();
        $completed->decide(AllocationStatus::Completed, 7, new DateTimeImmutable(), PostponeReason::NoAccess);
        $cancelled = $this->allocation();
        $cancelled->decide(AllocationStatus::Cancelled, 7, new DateTimeImmutable(), PostponeReason::NoAccess);

        Assert::null($completed->getPostponeReason());
        Assert::null($cancelled->getPostponeReason());
        Assert::same($cancelled->getStatus(), AllocationStatus::Cancelled);
    }

    #[ExpectException(\LogicException::class)]
    public function aSettledAllocationCannotBeSettledAgain(): void
    {
        $allocation = $this->allocation();
        $allocation->decide(AllocationStatus::Completed, 7, new DateTimeImmutable());

        $allocation->decide(AllocationStatus::Cancelled, 7, new DateTimeImmutable());
    }

    #[ExpectException(\LogicException::class)]
    public function allocatedIsNotADecision(): void
    {
        $this->allocation()->decide(AllocationStatus::Allocated, 7, new DateTimeImmutable());
    }

    public function reallocatingAfterADecisionStartsTheSideOver(): void
    {
        $allocation = $this->allocation();
        $allocation->decide(AllocationStatus::Postponed, 7, new DateTimeImmutable(), PostponeReason::NoAccess);
        $at = new DateTimeImmutable('2026-11-13 09:00:00');

        $allocation->allocateTo(9, $at);

        Assert::same($allocation->reqWorkerId(), 9);
        Assert::same($allocation->getAllocatedAt(), $at);
        Assert::same($allocation->getStatus(), AllocationStatus::Allocated);
        Assert::false($allocation->isDecided());
        Assert::null($allocation->getDecidedAt());
        Assert::null($allocation->getDecidedByWorkerId());
        Assert::null($allocation->getPostponeReason());
    }

    public function aWholeHouseConflictsWithBothSplitSidesAndEachSplitSideWithTheWholeHouse(): void
    {
        Assert::same(AllocationSide::Both->conflictsWith(), [AllocationSide::Front, AllocationSide::Back]);
        Assert::same(AllocationSide::Front->conflictsWith(), [AllocationSide::Both]);
        Assert::same(AllocationSide::Back->conflictsWith(), [AllocationSide::Both]);
    }

    public function onlyCompletedPostponedAndCancelledAreDecisions(): void
    {
        Assert::false(AllocationStatus::Allocated->isDecision());
        Assert::true(AllocationStatus::Completed->isDecision());
        Assert::true(AllocationStatus::Postponed->isDecision());
        Assert::true(AllocationStatus::Cancelled->isDecision());
    }
}
