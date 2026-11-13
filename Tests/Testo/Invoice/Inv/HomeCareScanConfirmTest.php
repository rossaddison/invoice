<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\HomeCareVisit\HomeCareVisit;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\HomeCareVisit\HomeCareVisitRepositoryInterface;
use App\Invoice\Inv\HomeCareCleaningEligibilityService;
use App\Invoice\Inv\HomeCareScanConfirmer;
use App\Invoice\Inv\InvCopyDeps;
use App\Invoice\Inv\InvMarkSentService;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvWorkerAllocation\AllocationSendGate;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationRepositoryInterface;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use DateTimeImmutable;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The customer's QR scan confirming a planned visit (the safety net for a worker who
 * forgot to mark a side complete): GET asks, only the POST button acts.
 */
#[Test]
final class HomeCareScanConfirmTest
{
    private const int CLIENT = 40;
    private const int INV = 12;
    private const string TOKEN = 'abc123';

    /** @var list<array{string, array<string, mixed>}> */
    private array $rendered = [];

    private function client(): Client
    {
        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        $client->shouldReceive('reqId')->andReturn(self::CLIENT);
        return $client;
    }

    private function clients(?Client $client): ClientRepository&m\MockInterface
    {
        /** @var ClientRepository&m\MockInterface $clients */
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('repoClientByQrTokenquery')->with(self::TOKEN)->andReturn($client);
        return $clients;
    }

    private function eligibility(bool $enabled): HomeCareCleaningEligibilityService&m\MockInterface
    {
        /** @var HomeCareCleaningEligibilityService&m\MockInterface $service */
        $service = m::mock(HomeCareCleaningEligibilityService::class);
        $service->shouldReceive('isFeatureEnabledForClient')->with(self::CLIENT)->andReturn($enabled);
        return $service;
    }

    private function inv(): Inv
    {
        $inv = new Inv();
        $inv->setId(self::INV);
        return $inv;
    }

    private function visits(): HomeCareVisitRepositoryInterface&m\MockInterface
    {
        /** @var HomeCareVisitRepositoryInterface&m\MockInterface $visits */
        $visits = m::mock(HomeCareVisitRepositoryInterface::class);
        return $visits;
    }

    private function copyDeps(): InvCopyDeps
    {
        /** @var InvCopyDeps&m\MockInterface $deps */
        $deps = m::mock(InvCopyDeps::class);
        return $deps;
    }

    private function hydrator(): FormHydrator
    {
        /** @var FormHydrator&m\MockInterface $hydrator */
        $hydrator = m::mock(FormHydrator::class);
        return $hydrator;
    }

    private function harness(Response $notFound): HomeCareScanTestHarness
    {
        // the test object is reused across tests: start each with nothing rendered
        $this->rendered = [];
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn($notFound);
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->andReturn(true);
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('s');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        /** @var WebViewRenderer&m\MockInterface $renderer */
        $renderer = m::mock(WebViewRenderer::class);
        $renderer->shouldReceive('withControllerName')->andReturnSelf();
        $renderer->shouldReceive('withLayout')->andReturnSelf();
        /** @var Response&m\MockInterface $page */
        $page = m::mock(Response::class);
        $renderer->shouldReceive('renderPartial')->andReturnUsing(
            function (string $view, array $params) use ($page): Response {
                /** @var array<string, mixed> $params */
                $this->rendered[] = [$view, $params];
                return $page;
            }
        );

        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::mock(LoggerInterface::class);
        return new HomeCareScanTestHarness($webService, $userService, $translator, $renderer, $session, $sR, $flash, $logger);
    }

    private function visit(string $outcome = 'pending'): HomeCareVisit
    {
        return new HomeCareVisit(self::CLIENT, new DateTimeImmutable('today'), $outcome);
    }

    private function confirmer(?Inv $pending): HomeCareScanConfirmer&m\MockInterface
    {
        /** @var HomeCareScanConfirmer&m\MockInterface $confirmer */
        $confirmer = m::mock(HomeCareScanConfirmer::class);
        $confirmer->shouldReceive('pendingDraft')->with(self::CLIENT, m::type(DateTimeImmutable::class))->andReturn($pending);
        return $confirmer;
    }

    /**
     * @return array{string, array<string, mixed>}
     */
    private function lastRender(): array
    {
        Assert::true([] !== $this->rendered);
        return $this->rendered[count($this->rendered) - 1];
    }

    public function aScanWithAPlannedDraftWaitingAsksTheCustomerAndChangesNothing(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $inv = $this->inv();
        $visits = $this->visits();
        $visits->shouldNotReceive('tryCreatePendingVisit');
        $visits->shouldNotReceive('save');
        $confirmer = $this->confirmer($inv);
        $confirmer->shouldNotReceive('confirm');

        $this->harness($notFound)->homeCareScan(
            self::TOKEN,
            $this->clients($this->client()),
            $this->eligibility(true),
            $visits,
            $this->copyDeps(),
            $this->hydrator(),
            $confirmer,
        );

        [$view, $params] = $this->lastRender();
        Assert::same($view, 'homecare_scan_result');
        Assert::same($params['outcome'], 'confirm');
        Assert::same($params['token'], self::TOKEN);
        Assert::same($params['visitDate'], $inv->getDateCreated()->format('Y-m-d'));
    }

    public function withTheFeatureOffTheScanNeverOffersConfirmation(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        /** @var HomeCareScanConfirmer&m\MockInterface $confirmer */
        $confirmer = m::mock(HomeCareScanConfirmer::class);
        $confirmer->shouldNotReceive('pendingDraft');
        $confirmer->shouldNotReceive('confirm');
        $visits = $this->visits();
        $visits->shouldReceive('tryCreatePendingVisit')->andReturn(null);
        $visits->shouldReceive('repoFindByClientAndDatequery')->andReturn($this->visit('not_eligible'));
        $visits->shouldReceive('delete');

        // falls through to the existing flow, never to the confirm page
        try {
            $this->harness($notFound)->homeCareScan(
                self::TOKEN,
                $this->clients($this->client()),
                $this->eligibility(false),
                $visits,
                $this->copyDeps(),
                $this->hydrator(),
                $confirmer,
            );
        } catch (\Throwable) {
            // the existing flow's own collaborators are not under test here
        }

        foreach ($this->rendered as [, $params]) {
            Assert::false('confirm' === $params['outcome']);
        }
    }

    public function anUnknownTokenIs404OnBothTheScanAndTheConfirm(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $visits = $this->visits();
        $harness = $this->harness($notFound);

        $scan = $harness->homeCareScan(self::TOKEN, $this->clients(null), $this->eligibility(true), $visits, $this->copyDeps(), $this->hydrator(), $this->confirmer(null));
        $confirm = $harness->homeCareScanConfirm(self::TOKEN, $this->clients(null), $this->eligibility(true), $visits, $this->confirmer(null));

        Assert::same($scan, $notFound);
        Assert::same($confirm, $notFound);
    }

    public function thePostCompletesTheVisitAndRendersTheConfirmedMessage(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $inv = $this->inv();
        $visit = $this->visit();
        $visits = $this->visits();
        $visits->shouldReceive('tryCreatePendingVisit')->once()->with(self::CLIENT, m::type(DateTimeImmutable::class))->andReturn($visit);
        $confirmer = $this->confirmer($inv);
        $confirmer->shouldReceive('confirm')->once()->with($inv, $visit)->andReturn(true);

        $this->harness($notFound)->homeCareScanConfirm(self::TOKEN, $this->clients($this->client()), $this->eligibility(true), $visits, $confirmer);

        [, $params] = $this->lastRender();
        Assert::same($params['outcome'], 'confirmed');
    }

    public function thePostWithNothingPendingConfirmsNothing(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $visit = $this->visit();
        $visits = $this->visits();
        $visits->shouldReceive('tryCreatePendingVisit')->once()->andReturn($visit);
        $visits->shouldReceive('save')->once()->with($visit);
        $confirmer = $this->confirmer(null);
        $confirmer->shouldNotReceive('confirm');

        $this->harness($notFound)->homeCareScanConfirm(self::TOKEN, $this->clients($this->client()), $this->eligibility(true), $visits, $confirmer);

        [, $params] = $this->lastRender();
        Assert::same($params['outcome'], 'not_eligible');
        Assert::same($visit->getOutcome(), 'not_eligible');
    }

    public function aRepeatPressTheSameDayIsANoOpBecauseConfirmedIsTerminal(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $visits = $this->visits();
        $visits->shouldReceive('tryCreatePendingVisit')->once()->andReturn(null);
        $visits->shouldReceive('repoFindByClientAndDatequery')->once()->andReturn($this->visit('confirmed'));
        // a stale non-terminal row would be deleted and re-claimed; a confirmed one must not be
        $visits->shouldNotReceive('delete');
        $confirmer = $this->confirmer($this->inv());
        $confirmer->shouldNotReceive('confirm');

        $this->harness($notFound)->homeCareScanConfirm(self::TOKEN, $this->clients($this->client()), $this->eligibility(true), $visits, $confirmer);

        [, $params] = $this->lastRender();
        Assert::same($params['outcome'], 'confirmed');
    }

    public function withTheFeatureOffThePostConfirmsNothing(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $visits = $this->visits();
        $visits->shouldReceive('tryCreatePendingVisit')->once()->andReturn($this->visit());
        $visits->shouldReceive('save')->once();
        /** @var HomeCareScanConfirmer&m\MockInterface $confirmer */
        $confirmer = m::mock(HomeCareScanConfirmer::class);
        $confirmer->shouldNotReceive('pendingDraft');
        $confirmer->shouldNotReceive('confirm');

        $this->harness($notFound)->homeCareScanConfirm(self::TOKEN, $this->clients($this->client()), $this->eligibility(false), $visits, $confirmer);

        [, $params] = $this->lastRender();
        Assert::same($params['outcome'], 'not_eligible');
    }

    // ---- the confirmer itself

    private function allocation(int $id, AllocationSide $side, ?AllocationStatus $decided = null): InvWorkerAllocation
    {
        $allocation = new InvWorkerAllocation(self::INV, $side->value, 7);
        $allocation->setId($id);
        null !== $decided && $allocation->decide($decided, 7, new DateTimeImmutable());
        return $allocation;
    }

    /**
     * @param list<InvWorkerAllocation> $rows
     */
    private function realConfirmer(
        array $rows,
        bool $sentWorks,
        InvRepository&m\MockInterface $iR,
        HomeCareVisitRepositoryInterface&m\MockInterface $visits,
    ): HomeCareScanConfirmer {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repo */
        $repo = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repo->shouldReceive('repoForInvquery')->with(self::INV)->andReturn($rows);
        $repo->shouldReceive('save');
        /** @var InvMarkSentService&m\MockInterface $markSent */
        $markSent = m::mock(InvMarkSentService::class);
        $markSent->shouldReceive('markSentWithoutEmail')->with([self::INV])->andReturn($sentWorks ? [self::INV] : []);

        return new HomeCareScanConfirmer(
            $iR,
            new InvWorkerAllocationService($repo),
            new AllocationSendGate($repo),
            $markSent,
            $visits,
        );
    }

    private function invRepository(): InvRepository&m\MockInterface
    {
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('save');
        return $iR;
    }

    public function confirmingCompletesWaitingSidesAsACustomerScanMarksSentAndRecordsTheVisit(): void
    {
        $front = $this->allocation(1, AllocationSide::Front);
        $back = $this->allocation(2, AllocationSide::Back);
        $inv = $this->inv();
        $inv->setStatusId(1);
        $visits = $this->visits();
        $visit = $this->visit();
        $visits->shouldReceive('save')->once()->with($visit);

        $sent = $this->realConfirmer([$front, $back], true, $this->invRepository(), $visits)->confirm($inv, $visit);

        Assert::true($sent);
        foreach ([$front, $back] as $side) {
            Assert::same($side->getStatus(), AllocationStatus::Completed);
            Assert::null($side->getDecidedByWorkerId());
            Assert::true($side->isConfirmedByCustomerScan());
        }
        Assert::same($visit->getOutcome(), 'confirmed');
        Assert::same($visit->getInvoiceId(), self::INV);
        Assert::true(str_contains((string) $visit->getReason(), 'no email'));
    }

    public function aSideAlreadyDoneByAWorkerKeepsItsWorkerAndIsNotRelabelled(): void
    {
        $done = $this->allocation(1, AllocationSide::Front, AllocationStatus::Completed);
        $waiting = $this->allocation(2, AllocationSide::Back);
        $inv = $this->inv();
        $inv->setStatusId(1);
        $visits = $this->visits();
        $visits->shouldReceive('save');

        $this->realConfirmer([$done, $waiting], true, $this->invRepository(), $visits)->confirm($inv, $this->visit());

        Assert::same($done->getDecidedByWorkerId(), 7);
        Assert::false($done->isConfirmedByCustomerScan());
        Assert::true($waiting->isConfirmedByCustomerScan());
    }

    public function ifTheInvoiceCannotBeMarkedSentTheSidesAreStillCompletedAndTheVisitSaysSo(): void
    {
        $waiting = $this->allocation(1, AllocationSide::Front);
        $postponed = $this->allocation(2, AllocationSide::Back, AllocationStatus::Postponed);
        $inv = $this->inv();
        $inv->setStatusId(1);
        $visits = $this->visits();
        $visits->shouldReceive('save');
        $visit = $this->visit();

        // the mark-sent service refuses (e.g. the invoice is flagged do-not-send), so nothing is sent
        $sent = $this->realConfirmer([$waiting, $postponed], false, $this->invRepository(), $visits)->confirm($inv, $visit);

        Assert::false($sent);
        Assert::true($waiting->isConfirmedByCustomerScan());
        // a postponed side is not the customer's to confirm: left as the worker recorded it
        Assert::false($postponed->isConfirmedByCustomerScan());
        Assert::same($postponed->getStatus(), AllocationStatus::Postponed);
        Assert::true(str_contains((string) $visit->getReason(), 'could not be marked sent'));
    }

    public function onlyAnEstablishedCustomerWithAPaidInvoiceCanBeOfferedConfirmation(): void
    {
        $inv = $this->inv();
        /** @var InvRepository&m\MockInterface $iR */
        $iR = m::mock(InvRepository::class);
        $iR->shouldReceive('repoClientHasPaidInvoicequery')->with(self::CLIENT)->andReturn(false, true);
        $iR->shouldReceive('repoClientPendingAllocatedDraftquery')->once()->andReturn($inv);
        $confirmer = $this->realConfirmer([], true, $iR, $this->visits());
        $today = new DateTimeImmutable('today');

        Assert::null($confirmer->pendingDraft(self::CLIENT, $today));
        Assert::same($confirmer->pendingDraft(self::CLIENT, $today), $inv);
    }

    public function aScanWithNothingPendingClaimsTheVisitAndRecordsNotEligibleWhenThereIsNoInvoiceToCopy(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $visit = $this->visit();
        $visits = $this->visits();
        $visits->shouldReceive('tryCreatePendingVisit')->once()->andReturn($visit);
        $visits->shouldReceive('save')->once()->with($visit);
        $eligibility = $this->eligibility(true);
        $eligibility->shouldReceive('findInvoiceToCopyIfEligible')->with(self::CLIENT)->andReturn(null);

        $this->harness($notFound)->homeCareScan(
            self::TOKEN,
            $this->clients($this->client()),
            $eligibility,
            $visits,
            $this->copyDeps(),
            $this->hydrator(),
            $this->confirmer(null),
        );

        [, $params] = $this->lastRender();
        Assert::same($params['outcome'], 'not_eligible');
        Assert::same($visit->getOutcome(), 'not_eligible');
    }

    public function aScanThatFindsTodaysVisitAlreadyGeneratedShowsThatOutcomeAndClaimsNothing(): void
    {
        /** @var Response&m\MockInterface $notFound */
        $notFound = m::mock(Response::class);
        $visits = $this->visits();
        $visits->shouldReceive('tryCreatePendingVisit')->once()->andReturn(null);
        $visits->shouldReceive('repoFindByClientAndDatequery')->once()->andReturn($this->visit('generated'));
        $visits->shouldNotReceive('delete');
        $visits->shouldNotReceive('save');
        $eligibility = $this->eligibility(true);
        $eligibility->shouldNotReceive('findInvoiceToCopyIfEligible');

        $this->harness($notFound)->homeCareScan(
            self::TOKEN,
            $this->clients($this->client()),
            $eligibility,
            $visits,
            $this->copyDeps(),
            $this->hydrator(),
            $this->confirmer(null),
        );

        [, $params] = $this->lastRender();
        Assert::same($params['outcome'], 'generated');
    }
}
