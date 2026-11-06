<?php

declare(strict_types=1);

namespace App\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationStatus;

/**
 * Whether a draft invoice's allocations let it be sent. The business rule: a
 * draft is sent only once the job is done.
 *
 *  - no allocations: not gated (non-HomeCare invoices, or houses not
 *    allocated through the per-side controls) -- sendable as before;
 *  - any side still `allocated` (undecided): blocked -- the job is unsettled;
 *  - every side decided, but none `completed` (all postponed / cancelled):
 *    blocked -- nothing was done, so there is nothing to invoice;
 *  - every side decided and at least one `completed`: sendable. A postponed
 *    side (e.g. a locked side gate) does not hold back the completed side's
 *    invoice.
 */
final class SendReadiness
{
    /**
     * @param list<InvWorkerAllocation> $allocations every allocation on one invoice
     */
    public static function blocksSending(array $allocations): bool
    {
        if ([] === $allocations) {
            return false;
        }
        $anyCompleted = false;
        foreach ($allocations as $allocation) {
            $status = $allocation->getStatus();
            if (AllocationStatus::Allocated === $status) {
                return true;
            }
            $anyCompleted = $anyCompleted || AllocationStatus::Completed === $status;
        }
        return !$anyCompleted;
    }
}
