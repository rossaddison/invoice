<?php

declare(strict_types=1);

namespace App\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\Inv\Inv;

/**
 * Keeps Inv::$allocation_blocks_sending in step with an invoice's allocations
 * (see {@see SendReadiness} for the rule). Call after allocations change; the
 * caller saves the invoice. Only a draft is ever gated: once an invoice has
 * been sent, re-allocating or re-settling a side must not stop it being
 * emailed again.
 */
final readonly class AllocationSendGate
{
    private const int STATUS_DRAFT = 1;

    public function __construct(private InvWorkerAllocationRepositoryInterface $repository)
    {
    }

    /**
     * Recomputes and sets the flag on $inv (no save); returns the new value.
     */
    public function apply(Inv $inv): bool
    {
        $blocks = self::STATUS_DRAFT === $inv->reqStatusId()
            && SendReadiness::blocksSending($this->repository->repoForInvquery($inv->reqId()));
        $inv->setAllocationBlocksSending($blocks);
        return $blocks;
    }
}
