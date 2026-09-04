<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\InvSentLog\InvSentLog;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;
use App\Invoice\Setting\SettingRepository as SR;
use DateTimeImmutable;

/**
 * The "mark as sent" half of the HomeCare run-sheet Apply step: flips an
 * invoice's status so it becomes visible on the customer's side, without
 * going through InvBatchEmailService — that path resolves an email template
 * and actually sends mail, which the run-sheet reconciliation deliberately
 * doesn't want (see project_homecare_run_signoff_design memory: "mark as
 * sent" was confirmed to mean the status flip only).
 *
 * Delegates the actual flip to SettingRepository::invoiceMarkSent() — the
 * existing draft(1)->sent(2) transition already used by InvPdfService, guard
 * and all (only flips from status 1, honours blocksSending(), applies the
 * read_only_toggle setting) — rather than reimplementing it here. This class
 * only adds the InvSentLog write InvBatchEmailService's own sent-path
 * produces, which invoiceMarkSent() itself doesn't do (its callers so far
 * haven't needed the audit trail); the guard check is duplicated by
 * necessity (one line) purely to know whether a flip happened and a log
 * entry is warranted.
 */
final readonly class InvMarkSentService
{
    public function __construct(
        private InvRepository $iR,
        private ISLR $islR,
        private SR $sR,
    ) {
    }

    /**
     * @param list<int> $invIds
     * @return list<int> the ids actually flipped — callers that need to know
     *                    which ones were skipped (already past draft, or
     *                    blocksSending()) diff this against the input list.
     */
    public function markSentWithoutEmail(array $invIds): array
    {
        $flipped = [];
        foreach ($invIds as $invId) {
            $inv = $this->iR->repoInvLoadedquery($invId);
            if ($inv === null || $inv->reqStatusId() !== 1 || $inv->blocksSending()) {
                continue;
            }
            $clientId = $inv->reqClientId();
            $this->sR->invoiceMarkSent($invId, $this->iR);
            $this->logSent($invId, $clientId);
            $flipped[] = $invId;
        }
        return $flipped;
    }

    private function logSent(int $invId, int $clientId): void
    {
        $log = new InvSentLog();
        $log->setInvId($invId);
        $log->setClientId($clientId);
        $log->setDateSent(new DateTimeImmutable('now'));
        $this->islR->save($log);
    }
}
