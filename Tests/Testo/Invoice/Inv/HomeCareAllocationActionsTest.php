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
use App\Invoice\Inv\AllocationCellResponder;
use App\Invoice\Inv\HomeCareCrew;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\WorkerInvAccess;
use App\Invoice\InvWorkerAllocation\AllocationSendGate;
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

    private function harness(WebControllerService&m\MockInterface $webService, bool $admin = false): HomeCareAllocationTestHarness
    {
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $user = m::mock(User::class);
        $user->shouldReceive('reqId')->andReturn(self::USER);
        $userService->shouldReceive('getUser')->andReturn($user);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn($admin);

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

        $harness = new HomeCareAllocationTestHarness(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash,
        );
        // an ordinary (non-htmx) post unless a test says otherwise
        $harness->allocationCells = $this->responder(false);
        return $harness;
    }

    private function responder(bool $htmx): AllocationCellResponder&m\MockInterface
    {
        /** @var AllocationCellResponder&m\MockInterface $responder */
        $responder = m::mock(AllocationCellResponder::class);
        $responder->shouldReceive('wants')->andReturn($htmx);
        return $responder;
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
     * @param array<string, string|list<string>> $body
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

    /**
     * The send gate: expect it to refresh the invoice $times times.
     */
    private function gate(int $times): AllocationSendGate&m\MockInterface
    {
        /** @var AllocationSendGate&m\MockInterface $gate */
        $gate = m::mock(AllocationSendGate::class);
        $gate->shouldReceive('apply')->times($times)->andReturn(false);
        return $gate;
    }

    /**
     * Invoice repository for settleAllocation(): expect $times load+save of the invoice.
     */
    private function settleInvRepository(int $times): InvRepository&m\MockInterface
    {
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(self::INV)->times($times)->andReturn($this->inv());
        $iR->shouldReceive('save')->times($times);
        return $iR;
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
            $this->gate(0),
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
            $this->gate(0),
        );
        $badSide = $harness->allocateSide(
            $this->request(['side' => 'roof', 'worker_id' => '7']),
            '12',
            $this->invRepository($this->inv()),
            $this->workerRepository($this->worker(7), 7),
            $lead,
            $allocations,
            $this->gate(0),
        );
        $unknownWorker = $harness->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '7']),
            '12',
            $this->invRepository($this->inv()),
            $this->workerRepository(null, 7),
            $lead,
            $allocations,
            $this->gate(0),
        );
        $inactiveWorker = $harness->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '7']),
            '12',
            $this->invRepository($this->inv()),
            $this->workerRepository($this->worker(7, false), 7),
            $lead,
            $allocations,
            $this->gate(0),
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
        $allocations->shouldReceive('splitWholeHouse')->once()->with(self::INV)->andReturn(true);
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
            $this->gate(1),
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
        $allocations->shouldNotReceive('splitWholeHouse');
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
            $this->gate(1),
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
        $allocations->shouldReceive('splitWholeHouse')->once()->with(self::INV)->andReturn(true);
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
            $this->gate(1),
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
            $this->settleInvRepository(1),
            $this->gate(1),
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
            $this->settleInvRepository(1),
            $this->gate(1),
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
            $this->settleInvRepository(0),
            $this->gate(0),
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
            $this->settleInvRepository(0),
            $this->gate(0),
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

        $unknown = $harness->settleAllocation($this->request(['status' => 'completed']), '999', $worker, $unknownService, $this->settleInvRepository(0), $this->gate(0));
        $invalid = $harness->settleAllocation($this->request(['status' => 'finished']), '3', $worker, $service, $this->settleInvRepository(0), $this->gate(0));
        $notADecision = $harness->settleAllocation($this->request(['status' => 'allocated']), '3', $worker, $service, $this->settleInvRepository(0), $this->gate(0));

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
            $this->settleInvRepository(0),
            $this->gate(0),
        );

        Assert::same($result, $redirect);
        Assert::same($allocation->getStatus(), AllocationStatus::Completed);
        Assert::same($this->flashes->getArrayCopy(), [['warning', 'allocation.already.settled']]);
    }

    private function activeWorkers(): WorkerRepository&m\MockInterface
    {
        /** @var WorkerRepository&m\MockInterface $wR */
        $wR = m::mock(WorkerRepository::class);
        $wR->shouldReceive('findAllActive')->andReturn([$this->worker(1), $this->worker(2), $this->worker(3)]);
        return $wR;
    }

    public function theLeadSavesTheCrewAndFrontBackModeFilteredToActiveWorkers(): void
    {
        [$webService, , $redirect] = $this->webService();
        /** @var HomeCareCrew&m\MockInterface $crew */
        $crew = m::mock(HomeCareCrew::class);
        $crew->shouldReceive('save')->once()->with([2, 1, 99], true, [1, 2, 3]);

        $result = $this->harness($webService)->setCrew(
            $this->request(['crew' => ['2', '1', '99'], 'front_back' => '1']),
            $this->access($this->worker(1), 1, true),
            $this->activeWorkers(),
            $crew,
        );

        Assert::same($result, $redirect);
    }

    public function anotherWorkerCannotSetTheCrew(): void
    {
        [$webService, $notFound] = $this->webService();
        /** @var HomeCareCrew&m\MockInterface $crew */
        $crew = m::mock(HomeCareCrew::class);
        $crew->shouldNotReceive('save');

        $result = $this->harness($webService)->setCrew(
            $this->request([]),
            $this->access($this->worker(7), 1, false),
            $this->activeWorkers(),
            $crew,
        );

        Assert::same($result, $notFound);
    }

    public function aSettledWholeHouseIsLeftAloneWhenASideIsTapped(): void
    {
        [$webService, , $redirect] = $this->webService();
        $inv = $this->inv();
        $iR = $this->invRepository($inv);
        $iR->shouldNotReceive('save');
        $allocations = $this->allocations();
        $allocations->shouldReceive('splitWholeHouse')->once()->with(self::INV)->andReturn(false);
        $allocations->shouldNotReceive('allocate');
        $allocations->shouldNotReceive('clear');

        $result = $this->harness($webService)->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '']),
            '12',
            $iR,
            $this->workerRepository(null, 0),
            $this->access($this->worker(1), 1, true),
            $allocations,
            $this->gate(0),
        );

        Assert::same($result, $redirect);
    }

    public function anHtmxAllocationReturnsTheReRenderedCellWithNoFlashAndNoRedirect(): void
    {
        [$webService] = $this->webService();
        $inv = $this->inv();
        $iR = $this->invRepository($inv);
        $iR->shouldReceive('save')->once()->with($inv);
        $allocations = $this->allocations();
        $allocations->shouldReceive('splitWholeHouse')->once()->with(self::INV)->andReturn(true);
        $allocations->shouldReceive('allocate')->once()->with(self::INV, AllocationSide::Front, 7);
        $allocations->shouldReceive('legacyWorkerId')->andReturn(null);
        $allocations->shouldReceive('hasAllocations')->andReturn(true);
        /** @var Response&m\MockInterface $cell */
        $cell = m::mock(Response::class);
        $responder = $this->responder(true);
        // the lead (worker 1) is the viewer
        $responder->shouldReceive('cell')->once()->with($inv, 1, false)->andReturn($cell);
        $harness = $this->harness($webService);
        $harness->allocationCells = $responder;

        $result = $harness->allocateSide(
            $this->request(['side' => 'front', 'worker_id' => '7']),
            '12',
            $iR,
            $this->workerRepository($this->worker(7), 7),
            $this->access($this->worker(1), 1, true),
            $allocations,
            $this->gate(1),
        );

        Assert::same($result, $cell);
        Assert::same($this->flashes->getArrayCopy(), []);
    }

    public function anHtmxSettleReturnsTheCellEvenWhenTheSideWasAlreadySettled(): void
    {
        [$webService] = $this->webService();
        $allocation = $this->allocation(7);
        $allocation->decide(AllocationStatus::Completed, 7, new \DateTimeImmutable());
        [$service] = $this->realService($allocation);
        $inv = $this->inv();
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(self::INV)->andReturn($inv);
        /** @var Response&m\MockInterface $cell */
        $cell = m::mock(Response::class);
        $responder = $this->responder(true);
        $responder->shouldReceive('cell')->once()->with($inv, 7, false)->andReturn($cell);
        $harness = $this->harness($webService);
        $harness->allocationCells = $responder;

        $result = $harness->settleAllocation(
            $this->request(['status' => 'cancelled']),
            '3',
            $this->access($this->worker(7), 1, false),
            $service,
            $iR,
            $this->gate(0),
        );

        Assert::same($result, $cell);
        Assert::same($this->flashes->getArrayCopy(), []);
    }

    public function anHtmxCrewSaveReturnsNoContentSoThePageScriptRefreshesTheGrid(): void
    {
        [$webService] = $this->webService();
        /** @var HomeCareCrew&m\MockInterface $crew */
        $crew = m::mock(HomeCareCrew::class);
        $crew->shouldReceive('save')->once();
        /** @var Response&m\MockInterface $none */
        $none = m::mock(Response::class);
        $responder = $this->responder(true);
        $responder->shouldReceive('nothing')->once()->andReturn($none);
        $harness = $this->harness($webService);
        $harness->allocationCells = $responder;

        $result = $harness->setCrew(
            $this->request(['crew' => ['1'], 'front_back' => '']),
            $this->access($this->worker(1), 1, true),
            $this->activeWorkers(),
            $crew,
        );

        Assert::same($result, $none);
        Assert::same($this->flashes->getArrayCopy(), []);
    }

    public function anAdminWhoIsNotTheLeadCanAllocateASideFromInvIndexAndReturnsThere(): void
    {
        [$webService] = $this->webService();
        /** @var Response&m\MockInterface $indexRedirect */
        $indexRedirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->with('inv/index')->andReturn($indexRedirect);
        $inv = $this->inv();
        $iR = $this->invRepository($inv);
        $iR->shouldReceive('save')->once()->with($inv);
        $allocations = $this->allocations();
        $allocations->shouldReceive('splitWholeHouse')->once()->with(self::INV)->andReturn(true);
        $allocations->shouldReceive('allocate')->once()->with(self::INV, AllocationSide::Back, 7);
        $allocations->shouldReceive('legacyWorkerId')->andReturn(null);
        $allocations->shouldReceive('hasAllocations')->andReturn(true);

        // not the lead (isLead false) and no worker row of their own: admin permission is enough
        $result = $this->harness($webService, true)->allocateSide(
            $this->request(['side' => 'back', 'worker_id' => '7', 'context' => 'index']),
            '12',
            $iR,
            $this->workerRepository($this->worker(7), 7),
            $this->access(null, 1, false),
            $allocations,
            $this->gate(1),
        );

        Assert::same($result, $indexRedirect);
        Assert::same($this->flashes->getArrayCopy(), [['info', 'worker.assigned']]);
    }

    public function anAdminPostingWithoutTheIndexContextStillGoesBackToInvGuest(): void
    {
        [$webService, , $redirect] = $this->webService();
        $inv = $this->inv();
        $iR = $this->invRepository($inv);
        $iR->shouldReceive('save')->once()->with($inv);
        $allocations = $this->allocations();
        $allocations->shouldReceive('splitWholeHouse')->andReturn(true);
        $allocations->shouldReceive('allocate')->once();
        $allocations->shouldReceive('legacyWorkerId')->andReturn(null);
        $allocations->shouldReceive('hasAllocations')->andReturn(true);

        $result = $this->harness($webService, true)->allocateSide(
            $this->request(['side' => 'back', 'worker_id' => '7']),
            '12',
            $iR,
            $this->workerRepository($this->worker(7), 7),
            $this->access(null, 1, false),
            $allocations,
            $this->gate(1),
        );

        Assert::same($result, $redirect);
    }

    public function theIndexContextAloneGrantsNothingToANonAdmin(): void
    {
        [$webService, $notFound] = $this->webService();
        $allocations = $this->allocations();
        $allocations->shouldNotReceive('allocate');

        $result = $this->harness($webService)->allocateSide(
            $this->request(['side' => 'back', 'worker_id' => '7', 'context' => 'index']),
            '12',
            $this->invRepository($this->inv()),
            $this->workerRepository($this->worker(7), 7),
            $this->access(null, 1, false),
            $allocations,
            $this->gate(0),
        );

        Assert::same($result, $notFound);
    }

    public function anHtmxAdminAllocationRendersTheAdminCell(): void
    {
        [$webService] = $this->webService();
        $inv = $this->inv();
        $iR = $this->invRepository($inv);
        $iR->shouldReceive('save')->once();
        $allocations = $this->allocations();
        $allocations->shouldReceive('splitWholeHouse')->andReturn(true);
        $allocations->shouldReceive('allocate')->once();
        $allocations->shouldReceive('legacyWorkerId')->andReturn(null);
        $allocations->shouldReceive('hasAllocations')->andReturn(true);
        /** @var Response&m\MockInterface $cell */
        $cell = m::mock(Response::class);
        $responder = $this->responder(true);
        $responder->shouldReceive('cell')->once()->with($inv, 1, true)->andReturn($cell);
        $harness = $this->harness($webService, true);
        $harness->allocationCells = $responder;

        $result = $harness->allocateSide(
            $this->request(['side' => 'back', 'worker_id' => '7', 'context' => 'index']),
            '12',
            $iR,
            $this->workerRepository($this->worker(7), 7),
            $this->access(null, 1, false),
            $allocations,
            $this->gate(1),
        );

        Assert::same($result, $cell);
    }

    public function anAdminCanSettleAnyonesSideRecordedAsTheOfficeAndReturnsToInvIndex(): void
    {
        [$webService] = $this->webService();
        /** @var Response&m\MockInterface $indexRedirect */
        $indexRedirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->with('inv/index')->andReturn($indexRedirect);
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldReceive('save')->once()->with($allocation);

        // no worker row for the admin, and the side belongs to worker 7
        $result = $this->harness($webService, true)->settleAllocation(
            $this->request(['status' => 'postponed', 'reason' => 'no_access', 'context' => 'index']),
            '3',
            $this->access(null, 1, false),
            $service,
            $this->settleInvRepository(1),
            $this->gate(1),
        );

        Assert::same($result, $indexRedirect);
        Assert::same($allocation->getStatus(), AllocationStatus::Postponed);
        Assert::same($allocation->getPostponeReason(), PostponeReason::NoAccess);
        // decided by the office, not by a worker
        Assert::null($allocation->getDecidedByWorkerId());
    }

    public function aLeadWhoIsAlsoAnAdminStillSettlesUnderTheirOwnWorkerId(): void
    {
        [$webService, , $redirect] = $this->webService();
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldReceive('save')->once()->with($allocation);

        $result = $this->harness($webService, true)->settleAllocation(
            $this->request(['status' => 'completed']),
            '3',
            $this->access($this->worker(1), 1, true),
            $service,
            $this->settleInvRepository(1),
            $this->gate(1),
        );

        Assert::same($result, $redirect);
        Assert::same($allocation->getDecidedByWorkerId(), 1);
    }

    public function anAdminCanSaveTheCrewAndReturnsToInvIndex(): void
    {
        [$webService] = $this->webService();
        /** @var Response&m\MockInterface $indexRedirect */
        $indexRedirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->with('inv/index')->andReturn($indexRedirect);
        /** @var HomeCareCrew&m\MockInterface $crew */
        $crew = m::mock(HomeCareCrew::class);
        $crew->shouldReceive('save')->once();

        $result = $this->harness($webService, true)->setCrew(
            $this->request(['crew' => ['1'], 'front_back' => '1', 'context' => 'index']),
            $this->access(null, 1, false),
            $this->activeWorkers(),
            $crew,
        );

        Assert::same($result, $indexRedirect);
    }

    public function theAllocatedWorkerCanCardTheirOwnSide(): void
    {
        [$webService, , $redirect] = $this->webService();
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldReceive('save')->once()->with($allocation);

        $result = $this->harness($webService)->cardAllocation(
            $this->request([]),
            '3',
            $this->access($this->worker(7), 1, false),
            $service,
            $this->invRepository($this->inv()),
        );

        Assert::same($result, $redirect);
        Assert::true($allocation->isCarded());
        Assert::same($this->flashes->getArrayCopy(), [['info', 'allocation.card.recorded']]);
    }

    public function anotherWorkerWhoIsNotTheLeadCannotCardSomeoneElsesSide(): void
    {
        [$webService, $notFound] = $this->webService();
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldNotReceive('save');

        $result = $this->harness($webService)->cardAllocation(
            $this->request([]),
            '3',
            $this->access($this->worker(8), 1, false),
            $service,
            $this->invRepository($this->inv()),
        );

        Assert::same($result, $notFound);
        Assert::false($allocation->isCarded());
    }

    public function anAdminWithNoWorkerRowCanCardAnySideAndReturnsToInvIndex(): void
    {
        [$webService] = $this->webService();
        /** @var Response&m\MockInterface $indexRedirect */
        $indexRedirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->with('inv/index')->andReturn($indexRedirect);
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldReceive('save')->once();

        $result = $this->harness($webService, true)->cardAllocation(
            $this->request(['context' => 'index']),
            '3',
            $this->access(null, 1, false),
            $service,
            $this->invRepository($this->inv()),
        );

        Assert::same($result, $indexRedirect);
        Assert::true($allocation->isCarded());
    }

    public function aSideThatCannotBeCardedWarnsInsteadOfFailing(): void
    {
        [$webService, , $redirect] = $this->webService();
        $allocation = $this->allocation(7);
        $allocation->decide(AllocationStatus::Cancelled, 7, new \DateTimeImmutable());
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldNotReceive('save');

        $result = $this->harness($webService)->cardAllocation(
            $this->request([]),
            '3',
            $this->access($this->worker(7), 1, false),
            $service,
            $this->invRepository($this->inv()),
        );

        Assert::same($result, $redirect);
        Assert::same($this->flashes->getArrayCopy(), [['warning', 'allocation.card.not.possible']]);
    }

    public function anHtmxCardTapReturnsTheReRenderedCellWithNoFlash(): void
    {
        [$webService] = $this->webService();
        $allocation = $this->allocation(7);
        [$service, $repository] = $this->realService($allocation);
        $repository->shouldReceive('save')->once();
        $inv = $this->inv();
        /** @var Response&m\MockInterface $cell */
        $cell = m::mock(Response::class);
        $responder = $this->responder(true);
        $responder->shouldReceive('cell')->once()->with($inv, 7, false)->andReturn($cell);
        $harness = $this->harness($webService);
        $harness->allocationCells = $responder;

        $result = $harness->cardAllocation(
            $this->request([]),
            '3',
            $this->access($this->worker(7), 1, false),
            $service,
            $this->invRepository($inv),
        );

        Assert::same($result, $cell);
        Assert::same($this->flashes->getArrayCopy(), []);
    }
}
