<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\WorkerInvAccess;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationRepositoryInterface;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Worker\WorkerRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * allocateSide() (lead only) and settleAllocation() (allocated worker or
 * lead) -- both routes are gated by VIEW_INV, which customer guests hold, so
 * the real authorisation is inside the actions. Anyone else, an unknown id or
 * invalid input all get the same 404.
 */
#[Test]
final class HomeCareAllocationActionsTest
{
    private const int USER = 55;
    private const int INV = 12;

    /** @var \ArrayObject<int, array{string, string}> */
    private \ArrayObject $flashes;

    public function __construct()
    {
        $this->flashes = new \ArrayObject();
    }

    private function harness(WebControllerService&m\MockInterface $webService): HomeCareAllocationTestHarness
    {
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn(self::USER);
        $userService->shouldReceive('getUser')->andReturn($user);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(false);

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        $this->flashes = new \ArrayObject();
        $flashes = $this->flashes;
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->andReturnUsing(static function (string $key, string $message) use ($flashes): void {
            $flashes->append([$key, $message]);
        });

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        return new HomeCareAllocationTestHarness(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash,
        );
    }

    /**
     * @return array{WebControllerService&m\MockInterface, Response&m\MockInterface, Response&m\MockInterface}
     */
    private function webService(): array
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);
        $webService->shouldReceive('getRedirectResponse')->with('inv/guest')->andReturn($redirect);
        return [$webService, $notFound, $redirect];
    }

    /**
     * @param array<string, string> $body
     */
    private function request(array $body): ServerRequestInterface&m\MockInterface
    {
        /** @var ServerRequestInterface&m\MockInterface $request */
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn($body);
        return $request;
    }

    private function worker(int $id, bool $active = true): Worker
    {
        $worker = new Worker('Dave', 'Smith', $active);
        $worker->setId($id);
        return $worker;
    }

    private function inv(): Inv
    {
        $inv = new Inv();
        $inv->setId(self::INV);
        return $inv;
    }

    private function access(?Worker $worker, ?int $leadId, bool $isLead): WorkerInvAccess&m\MockInterface
    {
        /** @var WorkerInvAccess&m\MockInterface $access */
        $access = m::mock(WorkerInvAccess::class);
        $access->shouldReceive('isLead')->andReturn($isLead);
        $access->shouldReceive('workerFor')->andReturn($worker);
        $access->shouldReceive('leadWorkerId')->andReturn($leadId);
        return $access;
    }

    private function invRepository(?Inv $inv): InvRepository&m\MockInterface
    {
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->andReturn($inv);
        return $iR;
    }

    private function workerRepository(?Worker $worker, int $id): WorkerRepository&m\MockInterface
    {
        /** @var WorkerRepository&m\MockInterface $wR */
        $wR = m::mock(WorkerRepository::class);
        $wR->shouldReceive('repoWorkerquery')->with($id)->andReturn($worker);
        return $wR;
    }

    private function allocations(): InvWorkerAllocationService&m\MockInterface
    {
        /** @var InvWorkerAllocationService&m\MockInterface $allocations */
        $allocations = m::mock(InvWorkerAllocationService::class);
        return $allocations;
    }

    public function aNonLeadCannotAllocateAndNothingChanges(): void
    {
        [$webService, $notFound] = $this->webService();
        $iR = $this->invRepository($this->inv());
        $iR->shouldNotReceive('save');
        $allocations = $this->allocations();
        $allocations->shouldNotReceive('allocate');
        $allocations->shouldNotReceive('clear');

        $result = $this->harness($webService)->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '7']),
            '12',
            $iR,
            $this->workerRepository($this->worker(7), 7),
            $this->access($this->worker(3), 1, false),
            $allocations,
        );

        Assert::same($result, $notFound);
    }

    public function anUnknownInvoiceOrInvalidSideOrWorkerAll404(): void
    {
        [$webService, $notFound] = $this->webService();
        $harness = $this->harness($webService);
        $allocations = $this->allocations();
        $allocations->shouldNotReceive('allocate');
        $lead = $this->access($this->worker(1), 1, true);

        $unknownInv = $harness->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '7']),
            '999',
            $this->invRepository(null),
            $this->workerRepository($this->worker(7), 7),
            $lead,
            $allocations,
        );
        $badSide = $harness->allocateSide(
            $this->request(['side' => 'roof', 'worker_id' => '7']),
            '12',
            $this->invRepository($this->inv()),
            $this->workerRepository($this->worker(7), 7),
            $lead,
            $allocations,
        );
        $unknownWorker = $harness->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '7']),
            '12',
            $this->invRepository($this->inv()),
            $this->workerRepository(null, 7),
            $lead,
            $allocations,
        );
        $inactiveWorker = $harness->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '7']),
            '12',
            $this->invRepository($this->inv()),
            $this->workerRepository($this->worker(7, false), 7),
            $lead,
            $allocations,
        );

        Assert::same($unknownInv, $notFound);
        Assert::same($badSide, $notFound);
        Assert::same($unknownWorker, $notFound);
        Assert::same($inactiveWorker, $notFound);
    }

    public function theLeadAllocatingASplitSideLeavesTheLegacySingleWorkerEmptyButStampsTheAllocationTime(): void
    {
        [$webService, , $redirect] = $this->webService();
        $inv = $this->inv();
        $iR = $this->invRepository($inv);
        $iR->shouldReceive('save')->once()->with($inv);
        $allocations = $this->allocations();
        $allocations->shouldReceive('allocate')->once()->with(self::INV, AllocationSide::Front, 7);
        $allocations->shouldReceive('legacyWorkerId')->with(self::INV)->andReturn(null);
        $allocations->shouldReceive('hasAllocations')->with(self::INV)->andReturn(true);

        $result = $this->harness($webService)->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '7']),
            '12',
            $iR,
            $this->workerRepository($this->worker(7), 7),
            $this->access($this->worker(1), 1, true),
            $allocations,
        );

        Assert::same($result, $redirect);
        Assert::null($inv->getWorkerId());
        // the worker grids sort by this, so a split house keeps allocation order
        Assert::true($inv->getWorkerAllocatedAt() instanceof \DateTimeImmutable);
    }

    public function theLeadAllocatingTheWholeHouseKeepsTheLegacySingleWorkerInStep(): void
    {
        [$webService, , $redirect] = $this->webService();
        $inv = $this->inv();
        $iR = $this->invRepository($inv);
        $iR->shouldReceive('save')->once()->with($inv);
        $allocations = $this->allocations();
        $allocations->shouldReceive('allocate')->once()->with(self::INV, AllocationSide::Both, 7);
        $allocations->shouldReceive('legacyWorkerId')->with(self::INV)->andReturn(7);
        $allocations->shouldReceive('hasAllocations')->with(self::INV)->andReturn(true);

        $result = $this->harness($webService)->allocateSide(
            $this->request(['side' => 'both', 'worker_id' => '7']),
            '12',
            $iR,
            $this->workerRepository($this->worker(7), 7),
            $this->access($this->worker(1), 1, true),
            $allocations,
        );

        Assert::same($result, $redirect);
        Assert::same($inv->getWorker()?->reqId(), 7);
        Assert::true($inv->getWorkerAllocatedAt() instanceof \DateTimeImmutable);
    }

    public function anEmptyWorkerClearsTheSide(): void
    {
        [$webService, , $redirect] = $this->webService();
        $inv = $this->inv();
        $iR = $this->invRepository($inv);
        $iR->shouldReceive('save')->once()->with($inv);
        $allocations = $this->allocations();
        $allocations->shouldReceive('clear')->once()->with(self::INV, AllocationSide::Back);
        $allocations->shouldNotReceive('allocate');
        $allocations->shouldReceive('legacyWorkerId')->andReturn(null);
        $allocations->shouldReceive('hasAllocations')->andReturn(false);
        $inv->setWorkerAllocatedAt(new \DateTimeImmutable('2026-11-06 09:00:00'));

        $result = $this->harness($webService)->allocateSide(
            $this->request(['side' => 'back', 'worker_id' => '']),
            '12',
            $iR,
            $this->workerRepository(null, 0),
            $this->access($this->worker(1), 1, true),
            $allocations,
        );

        Assert::same($result, $redirect);
        Assert::null($inv->getWorkerAllocatedAt());
    }

    /**
     * @return array{InvWorkerAllocationService, InvWorkerAllocationRepositoryInterface&m\MockInterface}
     */
    private function realService(?InvWorkerAllocation $allocation): array
    {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoAllocationquery')->andReturn($allocation);
        return [new InvWorkerAllocationService($repository), $repository];
    }

    private function allocation(int $workerId): InvWorkerAllocation
    {
        return new InvWorkerAllocation(self::INV, AllocationSide::Front->value, $workerId);
    }

    public function theAllocatedWorkerCanCompleteTheirOwnSide(): void
    {
        [$webService, , $redirect] = $this->webService();
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldReceive('save')->once()->with($allocation);
        $harness = $this->harness($webService);

        $result = $harness->settleAllocation(
            $this->request(['status' => 'completed']),
            '3',
            $this->access($this->worker(7), 1, false),
            $service,
        );

        Assert::same($result, $redirect);
        Assert::same($allocation->getStatus(), AllocationStatus::Completed);
        Assert::same($allocation->getDecidedByWorkerId(), 7);
        Assert::same($this->flashes->getArrayCopy(), [['info', 'allocation.settled']]);
    }

    public function theLeadCanPostponeAnotherWorkersSideWithAReason(): void
    {
        [$webService, , $redirect] = $this->webService();
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldReceive('save')->once()->with($allocation);

        $result = $this->harness($webService)->settleAllocation(
            $this->request(['status' => 'postponed', 'reason' => 'gate_locked']),
            '3',
            $this->access($this->worker(1), 1, true),
            $service,
        );

        Assert::same($result, $redirect);
        Assert::same($allocation->getStatus(), AllocationStatus::Postponed);
        Assert::same($allocation->getPostponeReason(), PostponeReason::GateLocked);
        Assert::same($allocation->getDecidedByWorkerId(), 1);
    }

    public function aCustomerGuestWithNoWorkerRowCannotSettleAnything(): void
    {
        [$webService, $notFound] = $this->webService();
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldNotReceive('save');

        $result = $this->harness($webService)->settleAllocation(
            $this->request(['status' => 'completed']),
            '3',
            $this->access(null, 1, false),
            $service,
        );

        Assert::same($result, $notFound);
        Assert::same($allocation->getStatus(), AllocationStatus::Allocated);
    }

    public function anotherWorkerThatIsNotTheLeadCannotSettleSomeoneElsesSide(): void
    {
        [$webService, $notFound] = $this->webService();
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldNotReceive('save');

        $result = $this->harness($webService)->settleAllocation(
            $this->request(['status' => 'completed']),
            '3',
            $this->access($this->worker(8), 1, false),
            $service,
        );

        Assert::same($result, $notFound);
        Assert::same($allocation->getStatus(), AllocationStatus::Allocated);
    }

    public function anUnknownAllocationAnInvalidStatusAndAllocatedAsAStatusAll404(): void
    {
        [$webService, $notFound] = $this->webService();
        $harness = $this->harness($webService);
        $worker = $this->access($this->worker(7), 1, false);
        [$unknownService] = $this->realService(null);
        [$service, $repository] = $this->realService($this->allocation(7));
        $repository->shouldNotReceive('save');

        $unknown = $harness->settleAllocation($this->request(['status' => 'completed']), '999', $worker, $unknownService);
        $invalid = $harness->settleAllocation($this->request(['status' => 'finished']), '3', $worker, $service);
        $notADecision = $harness->settleAllocation($this->request(['status' => 'allocated']), '3', $worker, $service);

        Assert::same($unknown, $notFound);
        Assert::same($invalid, $notFound);
        Assert::same($notADecision, $notFound);
    }

    public function settlingAnAlreadySettledSideWarnsAndRedirectsInsteadOf404(): void
    {
        [$webService, , $redirect] = $this->webService();
        $allocation = $this->allocation(7);
        $allocation->decide(AllocationStatus::Completed, 7, new \DateTimeImmutable());
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldNotReceive('save');

        $result = $this->harness($webService)->settleAllocation(
            $this->request(['status' => 'cancelled']),
            '3',
            $this->access($this->worker(7), 1, false),
            $service,
        );

        Assert::same($result, $redirect);
        Assert::same($allocation->getStatus(), AllocationStatus::Completed);
        Assert::same($this->flashes->getArrayCopy(), [['warning', 'allocation.already.settled']]);
    }
}
