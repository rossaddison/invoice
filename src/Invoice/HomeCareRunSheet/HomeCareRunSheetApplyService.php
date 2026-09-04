<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet;

use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Invoice\Enum\HomeCareRunSheetStatus;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\Inv\InvMarkSentService;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Worker\WorkerRepository as WR;
use DateTimeImmutable;
use LogicException;

/**
 * Step 5 of the run-sheet reconciliation, and the last one (see
 * project_homecare_run_signoff_design memory): writes every accepted
 * adjustment from the staging screen onto Inv, then marks every invoice in
 * the run sent (status flip only — see InvMarkSentService's own docblock for
 * why that's not the email path) and closes the batch.
 *
 * Deliberately narrow about what "applying an adjustment" writes:
 *  - worker reassignment — always applied when accepted and a worker was
 *    actually detected (never guessed on illegible handwriting).
 *  - do_not_send — only ever SET (never cleared) by this step, and only when
 *    detected_completed is false. A row detected complete only means "no
 *    incomplete-reason was written on the paper"; it says nothing about
 *    whether some other channel (a worker's own inv/guest flag) had already
 *    marked the invoice do-not-send for an unrelated reason, and this
 *    reconciliation has no business silently reversing that.
 * A rejected row (accepted === false) is left completely untouched — but
 * its invoice is still included in the final mark-sent pass, since
 * rejecting a mis-read adjustment doesn't mean the invoice shouldn't be
 * sent, just that the detected change itself wasn't real.
 */
final readonly class HomeCareRunSheetApplyService
{
    public function __construct(
        private HomeCareRunSheetRepository $rsR,
        private RSIR $rsiR,
        private IR $iR,
        private WR $wR,
        private InvMarkSentService $invMarkSentService,
    ) {
    }

    /**
     * @return list<int> invoice ids actually flipped to sent — see
     *                    InvMarkSentService::markSentWithoutEmail()
     */
    public function apply(HomeCareRunSheet $runSheet, int $byUserId): array
    {
        if ($runSheet->getStatus() !== HomeCareRunSheetStatus::PendingReview) {
            throw new LogicException(
                'HomeCareRunSheet #' . $runSheet->reqId() . ' is not PendingReview — cannot apply.',
            );
        }

        $items = $this->rsiR->repoForRunSheetquery($runSheet->reqId());
        $invIds = [];
        foreach ($items as $item) {
            if ($item->hasDetectedChange() && $item->getAccepted()) {
                $this->applyItem($item);
            }
            $invIds[] = $item->reqInvId();
        }

        $flipped = $this->invMarkSentService->markSentWithoutEmail($invIds);

        $runSheet->markApplied($byUserId, new DateTimeImmutable('now'));
        $this->rsR->save($runSheet);

        return $flipped;
    }

    private function applyItem(HomeCareRunSheetItem $item): void
    {
        $inv = $this->iR->repoInvUnLoadedquery($item->reqInvId());
        if ($inv === null) {
            return;
        }

        $detectedWorkerId = $item->getDetectedWorkerId();
        if ($detectedWorkerId !== null && $detectedWorkerId !== $inv->getWorker()?->reqId()) {
            $worker = $this->wR->repoWorkerquery($detectedWorkerId);
            if ($worker !== null) {
                $inv->setWorker($worker);
                $inv->setWorkerAllocatedAt(new DateTimeImmutable('now'));
            }
        }

        if ($item->getDetectedCompleted() === false) {
            $inv->setDoNotSend(true);
            $inv->setDoNotSendReason($item->getDetectedReasonCode() ?? '');
        }

        $this->iR->save($inv);
    }
}
