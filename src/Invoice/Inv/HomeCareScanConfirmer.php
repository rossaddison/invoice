<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\HomeCareVisit\HomeCareVisit;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\HomeCareVisit\HomeCareVisitRepositoryInterface;
use App\Invoice\InvWorkerAllocation\AllocationSendGate;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use DateTimeImmutable;

/**
 * A customer's QR scan confirming a planned visit was done: the safety net for a worker
 * who forgot to mark a side complete. When the client has a planned DRAFT invoice (dated
 * today or earlier) with a side still waiting, the scan page asks "has your home been
 * cleaned?" and only the customer's button (a POST) calls {@see self::confirm()}:
 * every waiting side is completed (recorded as a customer-scan confirmation, never as a
 * worker's work) and the draft is marked Sent without emailing anyone. No second invoice
 * is generated for that visit.
 */
final readonly class HomeCareScanConfirmer
{
    public function __construct(
        private InvRepository $iR,
        private InvWorkerAllocationService $allocations,
        private AllocationSendGate $gate,
        private InvMarkSentService $markSent,
        private HomeCareVisitRepositoryInterface $visits,
    ) {
    }

    /**
     * The client's planned visit a scan could confirm, if any. Only an established customer
     * (at least one paid invoice) can use the scan at all.
     */
    public function pendingDraft(int $clientId, DateTimeImmutable $today): ?Inv
    {
        return $this->iR->repoClientHasPaidInvoicequery($clientId)
            ? $this->iR->repoClientPendingAllocatedDraftquery($clientId, $today)
            : null;
    }

    /**
     * Completes the waiting sides, refreshes the send gate, marks the invoice Sent (status
     * flip and sent-log only, no email) and records the visit. If the invoice cannot be marked
     * sent (for example it is flagged do-not-send) the sides are still completed and it stays a
     * draft for the office; the visit's reason says so. A postponed side does not block sending.
     * Returns whether the invoice was marked Sent.
     */
    public function confirm(Inv $inv, HomeCareVisit $visit): bool
    {
        $invId = $inv->reqId();
        $this->allocations->confirmByCustomerScan($invId);
        $this->gate->apply($inv);
        $this->iR->save($inv);
        $sent = [] !== $this->markSent->markSentWithoutEmail([$invId]);

        $visit->setOutcome('confirmed');
        $visit->setInvoiceId($invId);
        $visit->setReason($sent
            ? 'Customer confirmed the visit by QR scan; sides completed and the invoice marked sent (no email).'
            : 'Customer confirmed the visit by QR scan; sides completed but the invoice could not be marked sent and stays a draft.');
        $this->visits->save($visit);
        return $sent;
    }
}
