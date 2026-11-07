<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

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
 */
trait HomeCareAllocation
{
    /**
     * The lead allocates one side of a house to a worker, or clears it
     * (empty worker_id). Keeps the legacy single Inv.worker_id in step for a
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
            || !$access->isLead($user instanceof User ? $user->reqId() : 0)
        ) {
            return $this->webService->getNotFoundResponse();
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
        $this->flashMessage('info', $this->translator->translate('worker.assigned'));
        return $this->webService->getRedirectResponse('inv/guest');
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
        if (null === $worker
            || null === $allocation
            || null === $status
            || !$status->isDecision()
            || !$allocations->mayDecide($allocation, $worker->reqId(), $access->leadWorkerId())
        ) {
            return $this->webService->getNotFoundResponse();
        }
        try {
            $allocations->decide(
                $allocation,
                $status,
                $worker->reqId(),
                $access->leadWorkerId(),
                PostponeReason::tryFrom((string) ($body['reason'] ?? '')),
            );
            $inv = $iR->repoInvUnLoadedquery($allocation->reqInvId());
            if (null !== $inv) {
                $gate->apply($inv);
                $iR->save($inv);
            }
            $this->flashMessage('info', $this->translator->translate('allocation.settled'));
        } catch (\LogicException) {
            $this->flashMessage('warning', $this->translator->translate('allocation.already.settled'));
        }
        return $this->webService->getRedirectResponse('inv/guest');
    }

    /**
     * The lead saves today's crew (two or three workers, the lead included)
     * and the Front/Back mode that drive the one-tap chips on inv/guest.
     * Lead only; anyone else gets the same 404.
     */
    public function setCrew(
        Request $request,
        WorkerInvAccess $access,
        WR $wR,
        HomeCareCrew $crew,
    ): Response {
        $user = $this->userService->getUser();
        if (!$access->isLead($user instanceof User ? $user->reqId() : 0)) {
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
        $this->flashMessage('info', $this->translator->translate('homecare.crew.saved'));
        return $this->webService->getRedirectResponse('inv/guest');
    }
}
