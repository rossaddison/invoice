<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Worker\Worker;
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
 * let any signed-in customer act on any invoice id.
 */
final readonly class WorkerInvAccess
{
    public function __construct(
        private UIR $uiR,
        private WR $wR,
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

    public function isAllocated(Inv $inv, int $userId): bool
    {
        $worker = $this->workerFor($userId);
        return null !== $worker && $inv->getWorkerId() === $worker->reqId();
    }
}
