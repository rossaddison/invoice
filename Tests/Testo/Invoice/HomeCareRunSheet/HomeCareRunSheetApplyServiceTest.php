<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunSheet;

use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\DoNotSendReason;
use App\Invoice\Enum\HomeCareRunSheetStatus;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetApplyService;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetRepository as RSR;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\Inv\InvMarkSentService;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvWorkerAllocation\AllocationSendGate;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationRepositoryInterface;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Worker\WorkerRepository as WR;
use Mockery as m;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers HomeCareRunSheetApplyService::apply() — Step 5 of the run-sheet
 * reconciliation, and the one project_homecare_run_signoff_design memory
 * flagged as "untested" pending a real batch reaching PendingReview. Exercises
 * the guard against applying a non-PendingReview batch, the worker-reassignment
 * and do_not_send write rules from that class's own docblock, and that a
 * rejected row is left completely untouched but still included in the
 * mark-sent pass.
 */
#[Test]
final class HomeCareRunSheetApplyServiceTest
{
    private function pendingReviewRunSheet(int $id): HomeCareRunSheet
    {
        $runSheet = new HomeCareRunSheet(category_secondary_id: 9, exported_by_user_id: 1);
        $runSheet->setId($id);
        $runSheet->markScanned('scan.jpg', 1, new \DateTimeImmutable('now'));
        $runSheet->markPendingReview();
        return $runSheet;
    }

    private function worker(int $id): Worker
    {
        $worker = new Worker();
        $worker->setId($id);
        return $worker;
    }

    #[ExpectException(\LogicException::class)]
    public function refusesToApplyABatchThatIsNotPendingReview(): void
    {
        $runSheet = new HomeCareRunSheet();
        $runSheet->setId(1);
        // Still Exported — never scanned or moved to PendingReview.

        /** @var RSR&m\MockInterface $rsR */
        $rsR = m::mock(RSR::class);
        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        /** @var InvMarkSentService&m\MockInterface $marker */
        $marker = m::mock(InvMarkSentService::class);

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker, $this->noAllocations(), $this->gate(0));
        $service->apply($runSheet, 2);
    }

    public function reassignsWorkerWhenDetectedWorkerDiffersFromCurrent(): void
    {
        $runSheet = $this->pendingReviewRunSheet(5);

        $currentWorker = $this->worker(1);
        $inv = new Inv();
        $inv->setId(311);
        $inv->setWorker($currentWorker);

        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 311, expected_worker_id: 1);
        $item->setDetection(2, true, null);

        $newWorker = $this->worker(2);

        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);
        $rsiR->shouldReceive('repoForRunSheetquery')->once()->with(5)->andReturn([$item]);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->once()->with(311)->andReturn($inv);
        $iR->shouldReceive('save')->once()->with($inv);

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldReceive('repoWorkerquery')->once()->with(2)->andReturn($newWorker);

        /** @var InvMarkSentService&m\MockInterface $marker */
        $marker = m::mock(InvMarkSentService::class);
        $marker->shouldReceive('markSentWithoutEmail')->once()->with([311])->andReturn([311]);

        /** @var RSR&m\MockInterface $rsR */
        $rsR = m::mock(RSR::class);
        $rsR->shouldReceive('save')->once()->with($runSheet);

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker, $this->noAllocations(), $this->gate(0));
        $flipped = $service->apply($runSheet, 2);

        Assert::same($newWorker, $inv->getWorker());
        Assert::same([311], $flipped);
        Assert::same(HomeCareRunSheetStatus::Applied, $runSheet->getStatus());
        Assert::same(2, $runSheet->getAppliedByUserId());
    }

    public function setsDoNotSendWhenDetectedIncompleteButNeverClearsIt(): void
    {
        $runSheet = $this->pendingReviewRunSheet(5);

        $inv = new Inv();
        $inv->setId(312);
        Assert::false($inv->getDoNotSend());

        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 312, expected_worker_id: null);
        $item->setDetection(null, false, DoNotSendReason::JobIncomplete->value);

        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);
        $rsiR->shouldReceive('repoForRunSheetquery')->once()->with(5)->andReturn([$item]);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->once()->with(312)->andReturn($inv);
        $iR->shouldReceive('save')->once()->with($inv);

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldNotReceive('repoWorkerquery');

        /** @var InvMarkSentService&m\MockInterface $marker */
        $marker = m::mock(InvMarkSentService::class);
        $marker->shouldReceive('markSentWithoutEmail')->once()->with([312])->andReturn([]);

        /** @var RSR&m\MockInterface $rsR */
        $rsR = m::mock(RSR::class);
        $rsR->shouldReceive('save')->once()->with($runSheet);

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker, $this->noAllocations(), $this->gate(0));
        $service->apply($runSheet, 2);

        Assert::true($inv->getDoNotSend());
        Assert::same(DoNotSendReason::JobIncomplete->value, $inv->getDoNotSendReason());
    }

    public function leavesARejectedRowsInvoiceUntouchedButStillIncludesItInMarkSent(): void
    {
        $runSheet = $this->pendingReviewRunSheet(5);

        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 313, expected_worker_id: 1);
        $item->setDetection(2, false, DoNotSendReason::JobIncomplete->value);
        $item->setAccepted(false);

        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);
        $rsiR->shouldReceive('repoForRunSheetquery')->once()->with(5)->andReturn([$item]);

        // Rejected: applyItem() must never even look the invoice up.
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoInvUnLoadedquery');
        $iR->shouldNotReceive('save');

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldNotReceive('repoWorkerquery');

        /** @var InvMarkSentService&m\MockInterface $marker */
        $marker = m::mock(InvMarkSentService::class);
        $marker->shouldReceive('markSentWithoutEmail')->once()->with([313])->andReturn([313]);

        /** @var RSR&m\MockInterface $rsR */
        $rsR = m::mock(RSR::class);
        $rsR->shouldReceive('save')->once()->with($runSheet);

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker, $this->noAllocations(), $this->gate(0));
        $flipped = $service->apply($runSheet, 2);

        Assert::same([313], $flipped);
    }

    public function skipsAnItemWhoseInvoiceNoLongerExists(): void
    {
        $runSheet = $this->pendingReviewRunSheet(5);

        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 999, expected_worker_id: 1);
        $item->setDetection(2, true, null);

        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);
        $rsiR->shouldReceive('repoForRunSheetquery')->once()->with(5)->andReturn([$item]);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->once()->with(999)->andReturn(null);
        $iR->shouldNotReceive('save');

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldNotReceive('repoWorkerquery');

        /** @var InvMarkSentService&m\MockInterface $marker */
        $marker = m::mock(InvMarkSentService::class);
        $marker->shouldReceive('markSentWithoutEmail')->once()->with([999])->andReturn([]);

        /** @var RSR&m\MockInterface $rsR */
        $rsR = m::mock(RSR::class);
        $rsR->shouldReceive('save')->once()->with($runSheet);

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker, $this->noAllocations(), $this->gate(0));
        $service->apply($runSheet, 2);
    }

    /**
     * Allocation service over a repository where no invoice has any allocation
     * (the paper-only world every test above runs in).
     */
    private function noAllocations(): InvWorkerAllocationService
    {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvquery')->andReturn([]);
        $repository->shouldReceive('repoForInvSidequery')->andReturn(null);
        return new InvWorkerAllocationService($repository);
    }

    /**
     * @param list<InvWorkerAllocation> $rows
     * @return array{InvWorkerAllocationService, InvWorkerAllocationRepositoryInterface&m\MockInterface}
     */
    private function allocationsOf(array $rows): array
    {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvquery')->andReturn($rows);
        $both = null;
        foreach ($rows as $row) {
            AllocationSide::Both === $row->getSide() && $both = $row;
        }
        $repository->shouldReceive('repoForInvSidequery')->andReturn($both);
        return [new InvWorkerAllocationService($repository), $repository];
    }

    private function gate(int $times): AllocationSendGate&m\MockInterface
    {
        /** @var AllocationSendGate&m\MockInterface $gate */
        $gate = m::mock(AllocationSendGate::class);
        $gate->shouldReceive('apply')->times($times)->andReturn(false);
        return $gate;
    }

    private function openAllocation(AllocationSide $side, int $workerId, int $invId): InvWorkerAllocation
    {
        return new InvWorkerAllocation($invId, $side->value, $workerId);
    }

    /**
     * @param array<array-key, mixed> $log
     */
    private function applyHarness(
        int $invId,
        HomeCareRunSheetItem $item,
        Inv $inv,
        InvWorkerAllocationService $allocations,
        AllocationSendGate $gate,
        array &$log,
        int $invLookups,
        ?Worker $worker = null,
    ): array {
        $runSheet = $this->pendingReviewRunSheet(5);

        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);
        $rsiR->shouldReceive('repoForRunSheetquery')->once()->with(5)->andReturn([$item]);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->times($invLookups)->with($invId)->andReturn($inv);
        $iR->shouldReceive('save')->andReturnUsing(static function () use (&$log): void {
            $log[] = 'save';
        });

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        null === $worker
            ? $wR->shouldNotReceive('repoWorkerquery')
            : $wR->shouldReceive('repoWorkerquery')->andReturn($worker);

        /** @var InvMarkSentService&m\MockInterface $marker */
        $marker = m::mock(InvMarkSentService::class);
        $marker->shouldReceive('markSentWithoutEmail')->once()->with([$invId])->andReturnUsing(
            static function () use (&$log, $invId): array {
                $log[] = 'mark-sent';
                return [$invId];
            }
        );

        /** @var RSR&m\MockInterface $rsR */
        $rsR = m::mock(RSR::class);
        $rsR->shouldReceive('save')->once()->with($runSheet);

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker, $allocations, $gate);
        return $service->apply($runSheet, 2);
    }

    private function cleanItem(int $invId): HomeCareRunSheetItem
    {
        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: $invId, expected_worker_id: 1);
        $item->setDetection(1, true, null);
        return $item;
    }

    public function aCleanRowCompletesItsOpenAllocationsRecordedAsTheOfficeBeforeMarkingSent(): void
    {
        $inv = new Inv();
        $inv->setId(321);
        $allocation = $this->openAllocation(AllocationSide::Both, 1, 321);
        [$allocations, $repository] = $this->allocationsOf([$allocation]);
        $repository->shouldReceive('save')->once()->with($allocation);
        $log = [];

        $flipped = $this->applyHarness(321, $this->cleanItem(321), $inv, $allocations, $this->gate(1), $log, 1);

        Assert::same(AllocationStatus::Completed, $allocation->getStatus());
        Assert::null($allocation->getDecidedByWorkerId());
        Assert::same([321], $flipped);
        // the gate refresh is saved BEFORE the mark-sent pass, or the gate would skip the invoice
        Assert::same(['save', 'mark-sent'], $log);
    }

    public function aDoNotSendInvoiceKeepsItsAllocationsOpen(): void
    {
        $inv = new Inv();
        $inv->setId(322);
        $inv->setDoNotSend(true);
        $allocation = $this->openAllocation(AllocationSide::Both, 1, 322);
        [$allocations, $repository] = $this->allocationsOf([$allocation]);
        $repository->shouldNotReceive('save');
        $log = [];

        $this->applyHarness(322, $this->cleanItem(322), $inv, $allocations, $this->gate(0), $log, 1);

        Assert::same(AllocationStatus::Allocated, $allocation->getStatus());
        Assert::same(['mark-sent'], $log);
    }

    public function aRowTheSheetSaysIsIncompleteKeepsItsAllocationsOpen(): void
    {
        $inv = new Inv();
        $inv->setId(323);
        $allocation = $this->openAllocation(AllocationSide::Both, 1, 323);
        [$allocations, $repository] = $this->allocationsOf([$allocation]);
        $repository->shouldNotReceive('save');
        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 323, expected_worker_id: 1);
        $item->setDetection(null, false, DoNotSendReason::JobIncomplete->value);
        $log = [];

        // looked up twice: once to set do-not-send, once to decide whether to settle
        $this->applyHarness(323, $item, $inv, $allocations, $this->gate(0), $log, 2);

        Assert::true($inv->getDoNotSend());
        Assert::same(AllocationStatus::Allocated, $allocation->getStatus());
    }

    public function aRejectedRowStillCompletesItsAllocationsBecauseTheInvoiceIsMarkedSent(): void
    {
        $inv = new Inv();
        $inv->setId(324);
        $allocation = $this->openAllocation(AllocationSide::Front, 1, 324);
        [$allocations, $repository] = $this->allocationsOf([$allocation]);
        $repository->shouldReceive('save')->once()->with($allocation);
        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 324, expected_worker_id: 1);
        $item->setDetection(2, false, DoNotSendReason::JobIncomplete->value);
        $item->setAccepted(false);
        $log = [];

        $this->applyHarness(324, $item, $inv, $allocations, $this->gate(1), $log, 1);

        Assert::false($inv->getDoNotSend());
        Assert::same(AllocationStatus::Completed, $allocation->getStatus());
    }

    public function aPaperReassignmentOfAWholeHouseMovesItsAllocationToTheNewWorker(): void
    {
        $inv = new Inv();
        $inv->setId(325);
        $inv->setWorker($this->worker(1));
        $allocation = $this->openAllocation(AllocationSide::Both, 1, 325);
        [$allocations, $repository] = $this->allocationsOf([$allocation]);
        $repository->shouldReceive('save')->with($allocation);
        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 325, expected_worker_id: 1);
        $item->setDetection(2, true, null);
        $log = [];

        $this->applyHarness(325, $item, $inv, $allocations, $this->gate(1), $log, 2, $this->worker(2));

        Assert::same(2, $inv->getWorker()?->reqId());
        Assert::same(2, $allocation->reqWorkerId());
    }

    public function aPaperReassignmentIsIgnoredForAHouseAllocatedSideBySide(): void
    {
        $inv = new Inv();
        $inv->setId(326);
        $inv->setWorker($this->worker(1));
        $front = $this->openAllocation(AllocationSide::Front, 4, 326);
        $back = $this->openAllocation(AllocationSide::Back, 5, 326);
        [$allocations, $repository] = $this->allocationsOf([$front, $back]);
        $repository->shouldReceive('save');
        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 326, expected_worker_id: 1);
        $item->setDetection(2, true, null);
        $log = [];

        $this->applyHarness(326, $item, $inv, $allocations, $this->gate(1), $log, 2, $this->worker(2));

        // the paper cannot say which side the new worker took
        Assert::same(1, $inv->getWorker()?->reqId());
        Assert::same(4, $front->reqWorkerId());
        Assert::same(5, $back->reqWorkerId());
    }
}
