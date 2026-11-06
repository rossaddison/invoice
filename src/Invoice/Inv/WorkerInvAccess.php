<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Invoice\Worker\WorkerRepository as WR;

/**
 * Who the signed-in HomeCare worker is, and whether an invoice is theirs.
 *
 * A worker is not an RBAC role: Trait\Guest::resolveGuestAccess() identifies
 * one as an active UserInv plus a Worker row for the user, and customer
 * guests hold the same view.inv permission the worker routes are gated by.
 * So any action a worker may take on an invoice must also check, here, that
 * the invoice is allocated to this worker -- the route permission alone would
 * let any signed-in customer act on any invoice id. The lead (exactly one
 * worker, set in the HomeCare settings) is the only one who may allocate.
 */
final readonly class WorkerInvAccess
{
    public function __construct(
        private UIR $uiR,
        private WR $wR,
        private SR $sR,
    ) {
    }

    public function workerFor(int $userId): ?Worker
    {
        if ($userId <= 0 || $this->uiR->repoUserInvUserIdcount($userId) === 0) {
            return null;
        }
        $userInv = $this->uiR->repoUserInvUserIdquery($userId);
        return null !== $userInv && $userInv->getActive()
            ? $this->wR->findByUserId($userId)
            : null;
    }

    /**
     * The one worker (Worker id) who allocates the run -- the
     * homecare_lead_worker_id setting -- or null when none is configured.
     */
    public function leadWorkerId(): ?int
    {
        $id = (int) $this->sR->getSetting('homecare_lead_worker_id');
        return $id > 0 ? $id : null;
    }

    public function isLead(int $userId): bool
    {
        $worker = $this->workerFor($userId);
        $leadId = $this->leadWorkerId();
        return null !== $worker && null !== $leadId && $worker->reqId() === $leadId;
    }

    public function isAllocated(Inv $inv, int $userId): bool
    {
        $worker = $this->workerFor($userId);
        return null !== $worker && $inv->getWorkerId() === $worker->reqId();
    }
}
