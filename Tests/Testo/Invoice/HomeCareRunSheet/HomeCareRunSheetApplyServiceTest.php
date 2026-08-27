<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunSheet;

use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Enum\DoNotSendReason;
use App\Invoice\Enum\HomeCareRunSheetStatus;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetApplyService;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetRepository as RSR;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\Inv\InvMarkSentService;
use App\Invoice\Inv\InvRepository as IR;
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

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker);
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

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker);
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

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker);
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

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker);
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

        $service = new HomeCareRunSheetApplyService($rsR, $rsiR, $iR, $wR, $marker);
        $service->apply($runSheet, 2);
    }
}
