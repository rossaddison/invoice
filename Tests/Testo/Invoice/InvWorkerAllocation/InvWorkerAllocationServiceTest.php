<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationRepositoryInterface;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * The allocation rules: one worker per (house, side); a house is whole
 * (`both`) or split (`front` + `back`), never a mix; an allocation is settled
 * once by its worker or the lead; a side is only ever re-created, never
 * duplicated.
 */
#[Test]
final class InvWorkerAllocationServiceTest
{
    private const int INV = 12;

    private function row(AllocationSide $side, int $workerId): InvWorkerAllocation
    {
        return new InvWorkerAllocation(self::INV, $side->value, $workerId, new DateTimeImmutable('2026-11-06 09:00:00'));
    }

    /**
     * @param list<InvWorkerAllocation> $existing
     */
    private function repository(array $existing): InvWorkerAllocationRepositoryInterface&m\MockInterface
    {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvquery')->with(self::INV)->andReturn($existing);
        return $repository;
    }

    public function allocatingAnEmptyHouseCreatesTheRow(): void
    {
        $repository = $this->repository([]);
        $saved = null;
        $repository->shouldReceive('save')->once()->andReturnUsing(static function (InvWorkerAllocation $a) use (&$saved): void {
            $saved = $a;
        });
        $at = new DateTimeImmutable('2026-11-06 08:00:00');

        $result = (new InvWorkerAllocationService($repository))->allocate(self::INV, AllocationSide::Front, 7, $at);

        Assert::same($result, $saved);
        Assert::same($result->reqInvId(), self::INV);
        Assert::same($result->getSide(), AllocationSide::Front);
        Assert::same($result->reqWorkerId(), 7);
        Assert::same($result->getAllocatedAt(), $at);
        Assert::same($result->getStatus(), AllocationStatus::Allocated);
    }

    public function allocatingTheWholeHouseClearsAnySplitSides(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $back = $this->row(AllocationSide::Back, 8);
        $repository = $this->repository([$front, $back]);
        $repository->shouldReceive('delete')->once()->with($front);
        $repository->shouldReceive('delete')->once()->with($back);
        $repository->shouldReceive('save')->once();

        $result = (new InvWorkerAllocationService($repository))->allocate(self::INV, AllocationSide::Both, 9);

        Assert::same($result->getSide(), AllocationSide::Both);
    }

    public function allocatingASplitSideClearsTheWholeHouseAllocation(): void
    {
        $both = $this->row(AllocationSide::Both, 7);
        $repository = $this->repository([$both]);
        $repository->shouldReceive('delete')->once()->with($both);
        $repository->shouldReceive('save')->once();

        $result = (new InvWorkerAllocationService($repository))->allocate(self::INV, AllocationSide::Back, 8);

        Assert::same($result->getSide(), AllocationSide::Back);
    }

    public function allocatingOneSplitSideLeavesTheOtherAlone(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $repository = $this->repository([$front]);
        $repository->shouldNotReceive('delete');
        $repository->shouldReceive('save')->once();

        (new InvWorkerAllocationService($repository))->allocate(self::INV, AllocationSide::Back, 8);
    }

    public function repeatingTheSameAllocationChangesNothing(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $repository = $this->repository([$front]);
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');

        $result = (new InvWorkerAllocationService($repository))->allocate(self::INV, AllocationSide::Front, 7);

        Assert::same($result, $front);
    }

    public function givingASideToAnotherWorkerReplacesTheWorkerAndRestartsIt(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $repository = $this->repository([$front]);
        $repository->shouldReceive('save')->once()->with($front);
        $at = new DateTimeImmutable('2026-11-06 11:00:00');

        $result = (new InvWorkerAllocationService($repository))->allocate(self::INV, AllocationSide::Front, 9, $at);

        Assert::same($result, $front);
        Assert::same($front->reqWorkerId(), 9);
        Assert::same($front->getAllocatedAt(), $at);
    }

    public function reallocatingASettledSideToTheSameWorkerStartsItOver(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $front->decide(AllocationStatus::Postponed, 7, new DateTimeImmutable(), PostponeReason::GateLocked);
        $repository = $this->repository([$front]);
        $repository->shouldReceive('save')->once()->with($front);

        (new InvWorkerAllocationService($repository))->allocate(self::INV, AllocationSide::Front, 7);

        Assert::same($front->getStatus(), AllocationStatus::Allocated);
        Assert::null($front->getPostponeReason());
    }

    public function clearingASideDeletesItsAllocation(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvSidequery')->with(self::INV, AllocationSide::Front)->andReturn($front);
        $repository->shouldReceive('delete')->once()->with($front);

        (new InvWorkerAllocationService($repository))->clear(self::INV, AllocationSide::Front);
    }

    public function clearingASideWithNoAllocationDoesNothing(): void
    {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvSidequery')->andReturn(null);
        $repository->shouldNotReceive('delete');

        (new InvWorkerAllocationService($repository))->clear(self::INV, AllocationSide::Front);
    }

    public function theAllocatedWorkerCanSettleTheirOwnSide(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $repository = $this->repository([]);
        $repository->shouldReceive('save')->once()->with($front);
        $at = new DateTimeImmutable('2026-11-06 10:30:00');

        (new InvWorkerAllocationService($repository))
            ->decide($front, AllocationStatus::Completed, 7, 1, null, $at);

        Assert::same($front->getStatus(), AllocationStatus::Completed);
        Assert::same($front->getDecidedByWorkerId(), 7);
        Assert::same($front->getDecidedAt(), $at);
    }

    public function theLeadCanSettleAnotherWorkersSideAndIsRecordedAsWhoDidIt(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $repository = $this->repository([]);
        $repository->shouldReceive('save')->once()->with($front);

        (new InvWorkerAllocationService($repository))
            ->decide($front, AllocationStatus::Postponed, 1, 1, PostponeReason::GateLocked);

        Assert::same($front->getStatus(), AllocationStatus::Postponed);
        Assert::same($front->getDecidedByWorkerId(), 1);
        Assert::same($front->getPostponeReason(), PostponeReason::GateLocked);
    }

    public function anotherWorkerCannotSettleAndNothingIsSaved(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $repository = $this->repository([]);
        $repository->shouldNotReceive('save');

        $threw = false;
        try {
            (new InvWorkerAllocationService($repository))
                ->decide($front, AllocationStatus::Completed, 8, 1);
        } catch (\DomainException) {
            $threw = true;
        }

        Assert::true($threw);
        Assert::same($front->getStatus(), AllocationStatus::Allocated);
    }

    #[ExpectException(\DomainException::class)]
    public function withNoLeadConfiguredOnlyTheAllocatedWorkerMaySettle(): void
    {
        $front = $this->row(AllocationSide::Front, 7);

        (new InvWorkerAllocationService($this->repository([])))
            ->decide($front, AllocationStatus::Completed, 1, null);
    }

    #[ExpectException(\LogicException::class)]
    public function aSettledSideCannotBeSettledAgain(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        $front->decide(AllocationStatus::Completed, 7, new DateTimeImmutable());
        $repository = $this->repository([]);
        $repository->shouldNotReceive('save');

        (new InvWorkerAllocationService($repository))->decide($front, AllocationStatus::Cancelled, 7, 1);
    }

    public function mayDecideIsTheAllocatedWorkerOrTheLeadOnly(): void
    {
        $service = new InvWorkerAllocationService($this->repository([]));
        $front = $this->row(AllocationSide::Front, 7);

        Assert::true($service->mayDecide($front, 7, 1));
        Assert::true($service->mayDecide($front, 1, 1));
        Assert::false($service->mayDecide($front, 8, 1));
        Assert::false($service->mayDecide($front, 1, null));
    }

    public function theLegacySingleWorkerIsTheWholeHouseWorkerOrNullWhenSplitOrUnallocated(): void
    {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvSidequery')->with(self::INV, AllocationSide::Both)
            ->andReturn($this->row(AllocationSide::Both, 7), null);
        $service = new InvWorkerAllocationService($repository);

        Assert::same($service->legacyWorkerId(self::INV), 7);
        Assert::null($service->legacyWorkerId(self::INV));
    }

    public function aHouseHasAllocationsWhileAnySideIsAllocated(): void
    {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvquery')->with(self::INV)
            ->andReturn([$this->row(AllocationSide::Back, 8)], []);
        $service = new InvWorkerAllocationService($repository);

        Assert::true($service->hasAllocations(self::INV));
        Assert::false($service->hasAllocations(self::INV));
    }

    public function anAllocationIsFoundByItsId(): void
    {
        $front = $this->row(AllocationSide::Front, 7);
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoAllocationquery')->with(3)->andReturn($front);
        $repository->shouldReceive('repoAllocationquery')->with(4)->andReturn(null);
        $service = new InvWorkerAllocationService($repository);

        Assert::same($service->find(3), $front);
        Assert::null($service->find(4));
    }

    public function aHouseIsSplitWhenAnySideIsFrontOrBackAndNotWhenWhole(): void
    {
        $split = $this->repository([$this->row(AllocationSide::Front, 7)]);
        $whole = $this->repository([$this->row(AllocationSide::Both, 7)]);
        $none = $this->repository([]);

        Assert::true((new InvWorkerAllocationService($split))->isSplit(self::INV));
        Assert::false((new InvWorkerAllocationService($whole))->isSplit(self::INV));
        Assert::false((new InvWorkerAllocationService($none))->isSplit(self::INV));
    }

    public function reassigningAWholeHouseMovesItsWholeHouseRowToTheNewWorker(): void
    {
        $both = $this->row(AllocationSide::Both, 7);
        $repository = $this->repository([$both]);
        $repository->shouldReceive('repoForInvSidequery')->with(self::INV, AllocationSide::Both)->andReturn($both);
        $repository->shouldReceive('save')->once()->with($both);

        $moved = (new InvWorkerAllocationService($repository))->reassignWholeHouse(self::INV, 9);

        Assert::true($moved);
        Assert::same($both->reqWorkerId(), 9);
    }

    public function reassigningAHouseWithNoWholeHouseRowDoesNothing(): void
    {
        $repository = $this->repository([$this->row(AllocationSide::Front, 7)]);
        $repository->shouldReceive('repoForInvSidequery')->with(self::INV, AllocationSide::Both)->andReturn(null);
        $repository->shouldNotReceive('save');

        Assert::false((new InvWorkerAllocationService($repository))->reassignWholeHouse(self::INV, 9));
    }

    public function settlingFromTheRunSheetCompletesOnlyOpenRowsAsTheOffice(): void
    {
        $open = $this->row(AllocationSide::Front, 7);
        $alreadyPostponed = $this->row(AllocationSide::Back, 8);
        $alreadyPostponed->decide(AllocationStatus::Postponed, 8, new DateTimeImmutable(), PostponeReason::GateLocked);
        $repository = $this->repository([$open, $alreadyPostponed]);
        $repository->shouldReceive('save')->once()->with($open);
        $at = new DateTimeImmutable('2026-11-07 17:00:00');

        $settled = (new InvWorkerAllocationService($repository))->settleFromRunSheet(self::INV, $at);

        Assert::same($settled, 1);
        Assert::same($open->getStatus(), AllocationStatus::Completed);
        Assert::null($open->getDecidedByWorkerId());
        Assert::same($open->getDecidedAt(), $at);
        Assert::same($alreadyPostponed->getStatus(), AllocationStatus::Postponed);
    }

    public function backfillCarriesALegacyWorkerOverAsAWholeHouseAllocationKeepingItsTime(): void
    {
        $repository = $this->repository([]);
        $saved = null;
        $repository->shouldReceive('save')->once()->andReturnUsing(static function (InvWorkerAllocation $a) use (&$saved): void {
            $saved = $a;
        });
        $original = new DateTimeImmutable('2026-10-01 08:00:00');

        $created = (new InvWorkerAllocationService($repository))->backfillLegacy(self::INV, 7, $original);

        Assert::true($created);
        Assert::same($saved?->getSide(), AllocationSide::Both);
        Assert::same($saved?->reqWorkerId(), 7);
        Assert::same($saved?->getAllocatedAt(), $original);
        Assert::same($saved?->getStatus(), AllocationStatus::Allocated);
    }

    public function backfillIsIdempotentAndSkipsAnyHouseThatAlreadyHasAnAllocation(): void
    {
        $repository = $this->repository([$this->row(AllocationSide::Front, 7)]);
        $repository->shouldNotReceive('save');

        $created = (new InvWorkerAllocationService($repository))->backfillLegacy(self::INV, 7, null);

        Assert::false($created);
    }

    public function splittingAnOpenWholeHouseGivesTheSameWorkerTheFrontAndTheBack(): void
    {
        $both = $this->row(AllocationSide::Both, 7);
        $repository = $this->repository([$both]);
        $repository->shouldReceive('repoForInvSidequery')->with(self::INV, AllocationSide::Both)->andReturn($both);
        // the mock lists the house the same way each call, so the whole-house row is seen (and
        // deleted) once per side; the real repository would no longer list it for the second
        $repository->shouldReceive('delete')->atLeast()->once()->with($both);
        $saved = [];
        $repository->shouldReceive('save')->twice()->andReturnUsing(static function (InvWorkerAllocation $a) use (&$saved): void {
            $saved[$a->getSide()->value] = $a->reqWorkerId();
        });

        Assert::true((new InvWorkerAllocationService($repository))->splitWholeHouse(self::INV));
        Assert::same($saved, ['front' => 7, 'back' => 7]);
    }

    public function aSettledWholeHouseIsNeverSplit(): void
    {
        $both = $this->row(AllocationSide::Both, 7);
        $both->decide(AllocationStatus::Completed, 7, new DateTimeImmutable());
        $repository = $this->repository([$both]);
        $repository->shouldReceive('repoForInvSidequery')->with(self::INV, AllocationSide::Both)->andReturn($both);
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');

        Assert::false((new InvWorkerAllocationService($repository))->splitWholeHouse(self::INV));
    }

    public function aHouseWithNoWholeHouseRowSplitsToNothingAndIsReadyToChange(): void
    {
        $repository = $this->repository([$this->row(AllocationSide::Front, 7)]);
        $repository->shouldReceive('repoForInvSidequery')->with(self::INV, AllocationSide::Both)->andReturn(null);
        $repository->shouldNotReceive('save');

        Assert::true((new InvWorkerAllocationService($repository))->splitWholeHouse(self::INV));
    }

    public function theOfficeCanSettleAnyonesOpenSideWithNoWorkerRecorded(): void
    {
        $row = $this->row(AllocationSide::Front, 7);
        $repository = $this->repository([$row]);
        $repository->shouldReceive('save')->once()->with($row);

        (new InvWorkerAllocationService($repository))->decideAsOffice($row, AllocationStatus::Postponed, PostponeReason::NoAccess);

        Assert::same($row->getStatus(), AllocationStatus::Postponed);
        Assert::same($row->getPostponeReason(), PostponeReason::NoAccess);
        Assert::null($row->getDecidedByWorkerId());
    }

    public function theOfficeCannotSettleASideTwice(): void
    {
        $row = $this->row(AllocationSide::Front, 7);
        $row->decide(AllocationStatus::Completed, 7, new DateTimeImmutable());
        $repository = $this->repository([$row]);
        $repository->shouldNotReceive('save');

        try {
            (new InvWorkerAllocationService($repository))->decideAsOffice($row, AllocationStatus::Cancelled);
            Assert::true(false);
        } catch (\LogicException) {
            Assert::same($row->getStatus(), AllocationStatus::Completed);
        }
    }
}
