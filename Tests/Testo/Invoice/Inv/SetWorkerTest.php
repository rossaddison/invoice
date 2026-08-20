<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\BaseController;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Inv\Trait\Index;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Worker\WorkerRepository as WR;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers Index::setWorker() — specifically the new worker_allocated_at
 * stamping this session added, since InvRepository::repoWorkerVisible()
 * now sorts inv/guest by it (see that method's own docblock): assigning a
 * worker must stamp "now", reassigning must re-stamp it, and unassigning
 * must clear it back to null, all alongside the pre-existing
 * setWorker()/save() behaviour.
 *
 * Same minimal-harness technique as GuestOfflineTest — mixes in the real
 * Index trait against a bare BaseController subclass rather than
 * constructing InvController itself (its own dependency graph is much
 * larger and entirely unrelated to this one action).
 */
#[Test]
final class SetWorkerTest
{
    /** @return array{0: SetWorkerTestHarness, 1: Response&m\MockInterface} */
    private function makeHarness(): array
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $webService->shouldReceive('getRedirectResponse')->with('inv/index')->andReturn($redirect);

        // BaseController::__construct() -> initializeViewRenderer() reads
        // both of these unconditionally on every harness construction —
        // stubbed the same way GuestOfflineTestHarness's own makeHarness()
        // does, since none of these tests care which layout branch it
        // picks, only that setWorker() itself behaves correctly.
        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(false);

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->with('worker.assigned')->andReturn('Worker assigned');

        /** @var WebViewRenderer&m\MockInterface $webViewRenderer */
        $webViewRenderer = m::mock(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();

        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add');

        /** @var UrlGenerator&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGenerator::class);

        /** @var HtmlResponseFactory&m\MockInterface $htmlResponseFactory */
        $htmlResponseFactory = m::mock(HtmlResponseFactory::class);

        $harness = new SetWorkerTestHarness(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash,
            $urlGenerator,
            $htmlResponseFactory,
        );

        return [$harness, $redirect];
    }

    private function makeRequest(string $workerId): Request&m\MockInterface
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('getParsedBody')->andReturn(['worker_id' => $workerId]);
        return $request;
    }

    public function assigningAWorkerStampsTheAllocationTimestamp(): void
    {
        /** @var Worker&m\MockInterface $worker */
        $worker = m::mock(Worker::class);

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('setWorker')->once()->with($worker);
        $inv->shouldReceive('setWorkerAllocatedAt')->once()
            ->with(m::on(static fn (mixed $arg): bool => $arg instanceof \DateTimeImmutable));

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(101)->andReturn($inv);
        $iR->shouldReceive('save')->once()->with($inv);

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldReceive('repoWorkerquery')->with(55)->andReturn($worker);

        [$harness, $redirect] = $this->makeHarness();
        $result = $harness->setWorker($this->makeRequest('55'), '101', $iR, $wR);

        Assert::same($redirect, $result);
    }

    public function unassigningAWorkerClearsTheAllocationTimestamp(): void
    {
        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('setWorker')->once()->with(null);
        $inv->shouldReceive('setWorkerAllocatedAt')->once()->with(null);

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(101)->andReturn($inv);
        $iR->shouldReceive('save')->once()->with($inv);

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldNotReceive('repoWorkerquery');

        [$harness, $redirect] = $this->makeHarness();
        $result = $harness->setWorker($this->makeRequest(''), '101', $iR, $wR);

        Assert::same($redirect, $result);
    }

    public function reassigningToADifferentWorkerReStampsTheTimestamp(): void
    {
        /** @var Worker&m\MockInterface $newWorker */
        $newWorker = m::mock(Worker::class);

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('setWorker')->once()->with($newWorker);
        $inv->shouldReceive('setWorkerAllocatedAt')->once()
            ->with(m::on(static fn (mixed $arg): bool => $arg instanceof \DateTimeImmutable));

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(101)->andReturn($inv);
        $iR->shouldReceive('save')->once()->with($inv);

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldReceive('repoWorkerquery')->with(56)->andReturn($newWorker);

        [$harness, $redirect] = $this->makeHarness();
        $result = $harness->setWorker($this->makeRequest('56'), '101', $iR, $wR);

        Assert::same($redirect, $result);
    }

    public function doesNothingWhenTheInvoiceIsNotFound(): void
    {
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldReceive('repoInvUnLoadedquery')->with(999)->andReturn(null);
        $iR->shouldNotReceive('save');

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldNotReceive('repoWorkerquery');

        [$harness, $redirect] = $this->makeHarness();
        $result = $harness->setWorker($this->makeRequest('55'), '999', $iR, $wR);

        Assert::same($redirect, $result);
    }
}

/**
 * Test-only harness — mixes in the real Index trait against a minimal
 * BaseController subclass, avoiding InvController's much larger unrelated
 * dependency graph. Only setWorker() is exercised at runtime, but Psalm
 * still type-checks every method Index brings in (including index()
 * itself), so $url_generator/$htmlResponseFactory and the throw-stubs
 * below exist purely to satisfy that — same technique as
 * GuestOfflineTestHarness's own OptionsData-avoidance comment describes,
 * applied here to Index's *own* index() method instead of a second trait.
 */
final class SetWorkerTestHarness extends BaseController
{
    use Index;

    private const string NOT_EXERCISED = 'Not exercised by SetWorkerTest.';

    private readonly UrlGenerator $url_generator;
    private readonly HtmlResponseFactory $htmlResponseFactory;

    public function __construct(
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        SettingRepository $sR,
        Flash $flash,
        UrlGenerator $urlGenerator,
        HtmlResponseFactory $htmlResponseFactory,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->url_generator = $urlGenerator;
        $this->htmlResponseFactory = $htmlResponseFactory;
    }

    protected function markSentFlash(string $_language): void
    {
        throw new \LogicException(self::NOT_EXERCISED);
    }

    /** @return array<string, string> */
    public function optionsDataClientsFilter(IR $iR): array
    {
        throw new \LogicException(self::NOT_EXERCISED);
    }

    /** @return array<string, string> */
    public function optionsDataClientGroupFilter(CR $cR): array
    {
        throw new \LogicException(self::NOT_EXERCISED);
    }

    /** @return array<string, string> */
    public function optionsDataYearMonthFilter(): array
    {
        throw new \LogicException(self::NOT_EXERCISED);
    }

    /** @return array<string, string> */
    public function optionsDataInvNumberFilter(IR $iR): array
    {
        throw new \LogicException(self::NOT_EXERCISED);
    }

    /** @return array<string, string> */
    public function optionsDataFamilyNameFilter(IR $iR): array
    {
        throw new \LogicException(self::NOT_EXERCISED);
    }

    /** @return array<string, string> */
    public function optionsDataCreditInvNumberFilter(IR $iR): array
    {
        throw new \LogicException(self::NOT_EXERCISED);
    }

    /** @return array<string, string> */
    public function optionsDataStatusFilter(IR $iR): array
    {
        throw new \LogicException(self::NOT_EXERCISED);
    }
}
