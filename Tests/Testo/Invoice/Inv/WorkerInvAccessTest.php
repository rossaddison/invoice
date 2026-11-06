<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Inv\WorkerInvAccess;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\Worker\WorkerRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * A worker is an active UserInv plus a Worker row, not an RBAC role --
 * customer guests hold the same view.inv permission -- so "is this invoice
 * the signed-in worker's" must be answered from those two rows and the
 * invoice's worker_id.
 */
#[Test]
final class WorkerInvAccessTest
{
    private function worker(int $id): Worker
    {
        $worker = new Worker('Dave', 'Smith');
        $worker->setId($id);
        return $worker;
    }

    private function inv(?int $workerId): Inv
    {
        $inv = new Inv();
        $inv->setWorkerId($workerId);
        return $inv;
    }

    private function access(bool $hasUserInv, bool $userInvActive, ?Worker $worker): WorkerInvAccess
    {
        /** @var UserInv&m\MockInterface $userInv */
        $userInv = m::mock(UserInv::class);
        $userInv->shouldReceive('getActive')->andReturn($userInvActive);

        /** @var UserInvRepository&m\MockInterface $uiR */
        $uiR = m::mock(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdcount')->andReturn($hasUserInv ? 1 : 0);
        $uiR->shouldReceive('repoUserInvUserIdquery')->andReturn($hasUserInv ? $userInv : null);

        /** @var WorkerRepository&m\MockInterface $wR */
        $wR = m::mock(WorkerRepository::class);
        $wR->shouldReceive('findByUserId')->andReturn($worker);

        return new WorkerInvAccess($uiR, $wR);
    }

    public function allocatedWorkerOwnsTheirInvoice(): void
    {
        $access = $this->access(true, true, $this->worker(7));

        Assert::true($access->isAllocated($this->inv(7), 55));
    }

    public function anotherWorkersInvoiceIsNotTheirs(): void
    {
        $access = $this->access(true, true, $this->worker(7));

        Assert::false($access->isAllocated($this->inv(8), 55));
    }

    public function anUnallocatedInvoiceIsNotAnyWorkers(): void
    {
        $access = $this->access(true, true, $this->worker(7));

        Assert::false($access->isAllocated($this->inv(null), 55));
    }

    public function aCustomerGuestWithNoWorkerRowOwnsNothing(): void
    {
        $access = $this->access(true, true, null);

        Assert::false($access->isAllocated($this->inv(7), 55));
        Assert::same($access->workerFor(55), null);
    }

    public function anInactiveUserInvIsNotAWorker(): void
    {
        $access = $this->access(true, false, $this->worker(7));

        Assert::same($access->workerFor(55), null);
        Assert::false($access->isAllocated($this->inv(7), 55));
    }

    public function noUserInvMeansNoWorker(): void
    {
        $access = $this->access(false, false, $this->worker(7));

        Assert::same($access->workerFor(55), null);
    }

    public function aSignedOutUserOwnsNothingAndNothingIsQueried(): void
    {
        /** @var UserInvRepository&m\MockInterface $uiR */
        $uiR = m::mock(UserInvRepository::class);
        $uiR->shouldNotReceive('repoUserInvUserIdcount');
        /** @var WorkerRepository&m\MockInterface $wR */
        $wR = m::mock(WorkerRepository::class);
        $wR->shouldNotReceive('findByUserId');
        $access = new WorkerInvAccess($uiR, $wR);

        Assert::false($access->isAllocated($this->inv(7), 0));
    }
}
