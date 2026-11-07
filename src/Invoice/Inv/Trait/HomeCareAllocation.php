<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use App\Invoice\Inv\HomeCareCrew;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Inv\WorkerInvAccess;
use App\Invoice\InvWorkerAllocation\AllocationSendGate;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Worker\WorkerRepository as WR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * HomeCare per-side crew allocation, from inv/guest. Both routes are gated by
 * VIEW_INV (a worker's only invoice permission, which customer guests hold
 * too), so the real checks live here: only the lead may allocate; only the
 * allocated worker or the lead may settle. Anyone else, an unknown id, or
 * invalid input all get the same 404, so ids can't be probed. Both actions
 * refresh the invoice's send gate (AllocationSendGate): a draft cannot be
 * sent until its job is done.
 *
 * Requires the consuming class to expose:
 * @property \App\Service\WebControllerService $webService
 * @property \App\User\UserService $userService
 * @property \Yiisoft\Translator\TranslatorInterface $translator
 * @property \App\Invoice\Inv\AllocationCellResponder $allocationCells
 */
trait HomeCareAllocation
{
    /**
     * The lead -- or an admin, who may override everything -- allocates one side of a
     * house to a worker, or clears it (empty worker_id). Keeps the legacy single Inv.worker_id in step for a
     * whole-house allocation; a split house has no single worker, so it reads
     * null there (the worker views read the allocation table directly).
     */
    public function allocateSide(
        Request $request,
        #[RouteArgument('inv_id')] string $inv_id,
        IR $iR,
        WR $wR,
        WorkerInvAccess $access,
        InvWorkerAllocationService $allocations,
        AllocationSendGate $gate,
    ): Response {
        $user = $this->userService->getUser();
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $side = AllocationSide::tryFrom((string) ($body['side'] ?? ''));
        $workerId = (int) ($body['worker_id'] ?? 0);
        $worker = $workerId > 0 ? $wR->repoWorkerquery($workerId) : null;
        $inv = $inv_id !== '' ? $iR->repoInvUnLoadedquery((int) $inv_id) : null;
        if (null === $inv
            || null === $side
            || ($workerId > 0 && (null === $worker || !$worker->getActive()))
            || !($this->userService->hasPermission(Permissions::EDIT_INV)
                || $access->isLead($user instanceof User ? $user->reqId() : 0))
        ) {
            return $this->webService->getNotFoundResponse();
        }
        $admin = $this->fromAdminIndex($body);
        // Changing one side of a whole-house allocation first splits it into front + back for
        // the same worker, so the other side keeps its worker. A settled whole house is left alone.
        if (AllocationSide::Both !== $side && !$allocations->splitWholeHouse($inv->reqId())) {
            return $this->allocationDone($request, $inv, (int) $access->leadWorkerId(), $admin);
        }
        null === $worker
            ? $allocations->clear($inv->reqId(), $side)
            : $allocations->allocate($inv->reqId(), $side, $worker->reqId());
        $legacyWorkerId = $allocations->legacyWorkerId($inv->reqId());
        $legacyWorker = null !== $legacyWorkerId ? $wR->repoWorkerquery($legacyWorkerId) : null;
        $inv->setWorker($legacyWorker);
        // Set while ANY side is allocated (not just a whole-house one): the
        // worker grids sort by it, so split houses keep allocation order.
        $inv->setWorkerAllocatedAt($allocations->hasAllocations($inv->reqId()) ? new \DateTimeImmutable() : null);
        $gate->apply($inv);
        $iR->save($inv);
        return $this->allocationDone($request, $inv, (int) $access->leadWorkerId(), $admin, 'info', 'worker.assigned');
    }

    /**
     * The allocated worker, or the lead, settles an allocation as completed,
     * postponed or cancelled (a postpone reason is optional).
     */
    public function settleAllocation(
        Request $request,
        #[RouteArgument('allocation_id')] string $allocation_id,
        WorkerInvAccess $access,
        InvWorkerAllocationService $allocations,
        IR $iR,
        AllocationSendGate $gate,
    ): Response {
        $user = $this->userService->getUser();
        $worker = $access->workerFor($user instanceof User ? $user->reqId() : 0);
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $status = AllocationStatus::tryFrom((string) ($body['status'] ?? ''));
        $allocation = $allocation_id !== '' ? $allocations->find((int) $allocation_id) : null;
        // the allocated worker or the lead settles as themselves; an admin may settle anything,
        // recorded as the office
        $mayAsWorker = null !== $worker
            && null !== $allocation
            && $allocations->mayDecide($allocation, $worker->reqId(), $access->leadWorkerId());
        if (null === $allocation
            || null === $status
            || !$status->isDecision()
            || !($mayAsWorker || $this->userService->hasPermission(Permissions::EDIT_INV))
        ) {
            return $this->webService->getNotFoundResponse();
        }
        $admin = $this->fromAdminIndex($body);
        $inv = null;
        $flash = ['info', 'allocation.settled'];
        try {
            $reason = PostponeReason::tryFrom((string) ($body['reason'] ?? ''));
            null !== $worker && $mayAsWorker
                ? $allocations->decide($allocation, $status, $worker->reqId(), $access->leadWorkerId(), $reason)
                : $allocations->decideAsOffice($allocation, $status, $reason);
            $inv = $iR->repoInvUnLoadedquery($allocation->reqInvId());
            if (null !== $inv) {
                $gate->apply($inv);
                $iR->save($inv);
            }
        } catch (\LogicException) {
            $flash = ['warning', 'allocation.already.settled'];
        }
        if ($this->allocationCells->wants($request)) {
            // an already-settled side re-renders too, so the tapper sees the state that won
            $inv ??= $iR->repoInvUnLoadedquery($allocation->reqInvId());
            return null === $inv
                ? $this->allocationCells->nothing()
                : $this->allocationCells->cell($inv, $worker?->reqId() ?? 0, $admin);
        }
        $this->flashMessage($flash[0], $this->translator->translate($flash[1]));
        return $this->webService->getRedirectResponse($this->allocationHome($admin));
    }

    /**
     * The card was left at the house: the evidence it was cleaned. The allocated worker, the
     * lead or an admin may record it, on a waiting or a completed side. Idempotent, and a side
     * that cannot be carded (postponed or cancelled) just re-renders unchanged.
     */
    public function cardAllocation(
        Request $request,
        #[RouteArgument('allocation_id')] string $allocation_id,
        WorkerInvAccess $access,
        InvWorkerAllocationService $allocations,
        IR $iR,
    ): Response {
        $user = $this->userService->getUser();
        $worker = $access->workerFor($user instanceof User ? $user->reqId() : 0);
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $allocation = $allocation_id !== '' ? $allocations->find((int) $allocation_id) : null;
        $mayAsWorker = null !== $worker
            && null !== $allocation
            && $allocations->mayDecide($allocation, $worker->reqId(), $access->leadWorkerId());
        if (null === $allocation
            || !($mayAsWorker || $this->userService->hasPermission(Permissions::EDIT_INV))
        ) {
            return $this->webService->getNotFoundResponse();
        }
        $inv = $iR->repoInvUnLoadedquery($allocation->reqInvId());
        if (null === $inv) {
            return $this->webService->getNotFoundResponse();
        }
        $flash = ['info', 'allocation.card.recorded'];
        try {
            $allocations->markCarded($allocation);
        } catch (\LogicException) {
            $flash = ['warning', 'allocation.card.not.possible'];
        }
        $admin = $this->fromAdminIndex($body);
        if ($this->allocationCells->wants($request)) {
            return $this->allocationCells->cell($inv, $worker?->reqId() ?? 0, $admin);
        }
        $this->flashMessage($flash[0], $this->translator->translate($flash[1]));
        return $this->webService->getRedirectResponse($this->allocationHome($admin));
    }

    /**
     * Ends an allocation post. An htmx request (the page stays put) gets just the
     * invoice's re-rendered cell, with no flash banner (the changed cell is the
     * feedback); an ordinary post flashes, if there is a message, and redirects.
     */
    private function allocationDone(
        Request $request,
        Inv $inv,
        int $viewerWorkerId,
        bool $admin,
        ?string $flashType = null,
        ?string $flashKey = null,
    ): Response {
        if ($this->allocationCells->wants($request)) {
            return $this->allocationCells->cell($inv, $viewerWorkerId, $admin);
        }
        if (null !== $flashType && null !== $flashKey) {
            $this->flashMessage($flashType, $this->translator->translate($flashKey));
        }
        return $this->webService->getRedirectResponse($this->allocationHome($admin));
    }

    /**
     * True when an admin posted from the admin inv/index grid (its forms carry context=index).
     * Only the page that is rendered and redirected to follows this; the permission to act is
     * checked separately, so a forged field gains nothing.
     *
     * @param array<array-key, mixed> $body
     */
    private function fromAdminIndex(array $body): bool
    {
        return 'index' === (string) ($body['context'] ?? '')
            && $this->userService->hasPermission(Permissions::EDIT_INV);
    }

    private function allocationHome(bool $admin): string
    {
        return $admin ? 'inv/index' : 'inv/guest';
    }

    /**
     * The lead saves today's crew (two or three workers, the lead included)
     * and the Front/Back mode that drive the one-tap chips on inv/guest.
     * The lead or an admin only; anyone else gets the same 404.
     */
    public function setCrew(
        Request $request,
        WorkerInvAccess $access,
        WR $wR,
        HomeCareCrew $crew,
    ): Response {
        $user = $this->userService->getUser();
        if (!($this->userService->hasPermission(Permissions::EDIT_INV)
            || $access->isLead($user instanceof User ? $user->reqId() : 0))
        ) {
            return $this->webService->getNotFoundResponse();
        }
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $activeIds = [];
        foreach ($wR->findAllActive() as $worker) {
            $activeIds[] = $worker->reqId();
        }
        $crew->save(
            array_values(array_map('intval', (array) ($body['crew'] ?? []))),
            '1' === (string) ($body['front_back'] ?? ''),
            $activeIds,
        );
        if ($this->allocationCells->wants($request)) {
            // every row's chips change, so the page script refreshes the grid; no reload
            return $this->allocationCells->nothing();
        }
        $this->flashMessage('info', $this->translator->translate('homecare.crew.saved'));
        return $this->webService->getRedirectResponse($this->allocationHome($this->fromAdminIndex($body)));
    }
}
