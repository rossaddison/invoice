<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareSchedule;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\HomeCareRunDay\HomeCareRunDay;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\HomeCareSchedule\HomeCareScheduleController;
use App\Invoice\HomeCareSchedule\ParsedSchedule;
use App\Invoice\HomeCareSchedule\RunCalendarService;
use App\Invoice\HomeCareSchedule\RunScheduleParser;
use App\Invoice\HomeCareSchedule\ScheduleImporter;
use App\Invoice\HomeCareSchedule\ScheduleSaveResult;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Worker\WorkerRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The run calendar screens: the month's saved calendar, the preview of a pasted schedule, saving it
 * with the run and worker choices made, and clearing a day. The importer and calendar service are
 * tested on their own; here they are mocks, so these tests cover what the controller reads from the
 * request and where it sends the user.
 */
#[Test]
final class HomeCareScheduleControllerTest
{
    private const string TEXT = "NOVEMBER\n2 - North 1 (Andy & Dan)\n3 - South 2 (Andy)\n";

    /** @var \ArrayObject<int, array{string, string}> */
    private \ArrayObject $flashes;

    /** @var \ArrayObject<int, array{string, array<string, mixed>}> */
    private \ArrayObject $rendered;

    /** @var \ArrayObject<int, array{string, array<string, string>}> */
    private \ArrayObject $redirects;

    private Psr17Factory $http;

    public function __construct()
    {
        $this->flashes = new \ArrayObject();
        $this->rendered = new \ArrayObject();
        $this->redirects = new \ArrayObject();
        $this->http = new Psr17Factory();
    }

    /**
     * @return array{HomeCareScheduleController, Response&m\MockInterface, Response&m\MockInterface}
     */
    private function controller(
        ?ScheduleImporter $importer = null,
        ?RunCalendarService $calendar = null,
        ?CategorySecondaryRepository $csR = null,
        ?WorkerRepository $wR = null,
    ): array {
        // each test starts with nothing recorded
        $this->flashes = new \ArrayObject();
        $this->rendered = new \ArrayObject();
        $this->redirects = new \ArrayObject();

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $userService->shouldReceive('hasPermission')->with(Permissions::VIEW_INV)->andReturn(true);
        $userService->shouldReceive('hasPermission')->with(Permissions::EDIT_INV)->andReturn(true);
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        $flashes = $this->flashes;
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->andReturnUsing(static function (string $key, string $message) use ($flashes): void {
            $flashes->append([$key, $message]);
        });

        /** @var Response&m\MockInterface $page */
        $page = m::mock(Response::class);
        $rendered = $this->rendered;
        /** @var WebViewRenderer&m\MockInterface $views */
        $views = m::mock(WebViewRenderer::class);
        $views->shouldReceive('withControllerName')->andReturnSelf();
        $views->shouldReceive('withLayout')->andReturnSelf();
        $views->shouldReceive('renderPartialAsString')->andReturn('');
        $views->shouldReceive('render')->andReturnUsing(
            /** @param array<string, mixed> $parameters */
            static function (string $view, array $parameters = []) use ($rendered, $page): Response {
                $rendered->append([$view, $parameters]);
                return $page;
            }
        );

        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        $redirects = $this->redirects;
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->andReturnUsing(
            /** @param array<string, string> $query */
            static function (string $route, array $args = [], array $query = []) use ($redirect, $redirects): Response {
                $redirects->append([$route, $query]);
                return $redirect;
            }
        );
        $webService->shouldReceive('getNotFoundResponse')->andReturn($page);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        /** @var CategoryPrimaryRepository&m\MockInterface $cpR */
        $cpR = m::mock(CategoryPrimaryRepository::class);
        $cpR->shouldReceive('optionsDataCategoryPrimaries')->andReturn([3 => 'Windows']);

        $controller = new HomeCareScheduleController(
            $session,
            $sR,
            $translator,
            $userService,
            $views,
            $webService,
            $flash,
            new RunScheduleParser(),
            $importer ?? $this->importer(),
            $calendar ?? $this->calendar(),
            $csR ?? $this->runs([]),
            $cpR,
            $wR ?? $this->workers([]),
        );
        return [$controller, $page, $redirect];
    }

    private function importer(): ScheduleImporter&m\MockInterface
    {
        /** @var ScheduleImporter&m\MockInterface $importer */
        $importer = m::mock(ScheduleImporter::class);
        return $importer;
    }

    private function calendar(): RunCalendarService&m\MockInterface
    {
        /** @var RunCalendarService&m\MockInterface $calendar */
        $calendar = m::mock(RunCalendarService::class);
        $calendar->shouldReceive('forMonth')->andReturn([]);
        $calendar->shouldReceive('forDate')->andReturn(null);
        return $calendar;
    }

    /**
     * @param list<CategorySecondary> $runs
     */
    private function runs(array $runs): CategorySecondaryRepository&m\MockInterface
    {
        /** @var CategorySecondaryRepository&m\MockInterface $csR */
        $csR = m::mock(CategorySecondaryRepository::class);
        /** @var EntityReader&m\MockInterface $all */
        $all = m::mock(EntityReader::class);
        $all->shouldReceive('getIterator')->andReturnUsing(static function () use ($runs): \Generator {
            yield from $runs;
        });
        $csR->shouldReceive('findAllPreloaded')->andReturn($all);
        $csR->shouldReceive('repoCategorySecondaryQuery')->andReturn(null);
        return $csR;
    }

    /**
     * @param list<Worker> $workers
     */
    private function workers(array $workers): WorkerRepository&m\MockInterface
    {
        /** @var WorkerRepository&m\MockInterface $wR */
        $wR = m::mock(WorkerRepository::class);
        $wR->shouldReceive('findAllActive')->andReturn($workers);
        return $wR;
    }

    private function run(int $id, string $name): CategorySecondary
    {
        $run = new CategorySecondary(null, 3, $name);
        $run->setId($id);
        return $run;
    }

    private function worker(int $id, string $firstname): Worker
    {
        $worker = new Worker($firstname);
        $worker->setId($id);
        return $worker;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function post(array $body): ServerRequestInterface
    {
        return $this->http->createServerRequest('POST', '/')->withParsedBody($body);
    }

    public function theIndexListsTheMonthsSavedDaysWithTheirRunAndCrewNames(): void
    {
        /** @var RunCalendarService&m\MockInterface $calendar */
        $calendar = m::mock(RunCalendarService::class);
        $calendar->shouldReceive('forMonth')->with(2026, 11)->andReturn([new HomeCareRunDay('2026-11-02', 7, '11,12')]);
        [$controller, $page] = $this->controller(null, $calendar, null, $this->workers([$this->worker(11, 'Andy')]));

        $response = $controller->index($this->http->createServerRequest('GET', '/')->withQueryParams(['year' => '2026', 'month' => '11']));

        Assert::same($response, $page);
        [$view, $parameters] = $this->rendered[0];
        Assert::same($view, 'index');
        Assert::same($parameters['year'], 2026);
        Assert::same($parameters['month'], 11);
        Assert::same($parameters['days'], [['date' => '2026-11-02', 'run' => null, 'crew' => ['Andy', '?']]]);
    }

    public function aNonsenseMonthFallsBackToTheCurrentOne(): void
    {
        [$controller] = $this->controller();

        $controller->index($this->http->createServerRequest('GET', '/')->withQueryParams(['year' => '1', 'month' => '99']));

        Assert::same($this->rendered[0][1]['year'], (int) date('Y'));
        Assert::same($this->rendered[0][1]['month'], (int) date('n'));
    }

    public function aPastedScheduleIsPreviewedWithSuggestionsAndWhatIsAlreadyOnTheCalendar(): void
    {
        $importer = $this->importer();
        $importer->shouldReceive('suggest')->once()->andReturn(['runs' => ['North 1' => 7], 'workers' => ['Andy' => 11]]);
        /** @var RunCalendarService&m\MockInterface $calendar */
        $calendar = m::mock(RunCalendarService::class);
        $calendar->shouldReceive('forDate')->with('2026-11-02')->andReturn(new HomeCareRunDay('2026-11-02', 7));
        $calendar->shouldReceive('forDate')->with('2026-11-03')->andReturn(null);
        [$controller, $page] = $this->controller(
            $importer,
            $calendar,
            $this->runs([$this->run(7, 'North 1')]),
            $this->workers([$this->worker(11, 'Andy')]),
        );

        $response = $controller->preview($this->post(['schedule' => self::TEXT, 'year' => '2026', 'month' => '11']));

        Assert::same($response, $page);
        [$view, $parameters] = $this->rendered[0];
        Assert::same($view, 'preview');
        Assert::true($parameters['schedule'] instanceof ParsedSchedule);
        Assert::same($parameters['runs'], [7 => 'North 1']);
        Assert::same($parameters['startless'], [7 => true]);
        Assert::same($parameters['existing'], ['2026-11-02' => true, '2026-11-03' => false]);
        Assert::same($parameters['primaries'], [3 => 'Windows']);
    }

    public function textThatCannotBeReadSendsYouBackToTheMonth(): void
    {
        [$controller, , $redirect] = $this->controller();

        $response = $controller->preview($this->post(['schedule' => 'nothing here', 'year' => '2026', 'month' => '11']));

        Assert::same($response, $redirect);
        Assert::same($this->redirects[0], ['homecareschedule/index', ['year' => '2026', 'month' => '11']]);
        Assert::same($this->flashes[0], ['danger', 'homecare.schedule.nothing.read']);
    }

    public function savingCreatesTheNewRunsAndWorkersChosenAndPassesTheirIdsToTheImporter(): void
    {
        $csR = $this->runs([]);
        $csR->shouldReceive('save')->once()->andReturnUsing(static function (CategorySecondary $created): void {
            $created->setId(21);
        });
        $wR = $this->workers([]);
        $wR->shouldReceive('save')->once()->andReturnUsing(static function (Worker $created): void {
            $created->setId(31);
        });
        $importer = $this->importer();
        $importer->shouldReceive('save')->once()->withArgs(
            static fn (ParsedSchedule $schedule, array $runIds, array $workerIds): bool
                => ['North 1' => 21, 'South 2' => 7] === $runIds && ['Andy' => 31, 'Dan' => 12] === $workerIds
        )->andReturn(new ScheduleSaveResult(2, 0, ['2026-11-09'], ['Zed']));
        [$controller, , $redirect] = $this->controller($importer, null, $csR, $wR);

        $response = $controller->save($this->post([
            'schedule' => self::TEXT, 'year' => '2026', 'month' => '11',
            'new_run_category_primary_id' => '3',
            'run' => ['new', '7'],
            'worker' => ['new', '12'],
        ]));

        Assert::same($response, $redirect);
        Assert::same($this->redirects[0], ['homecareschedule/index', ['year' => '2026', 'month' => '11']]);
        Assert::same(array_column($this->flashes->getArrayCopy(), 0), ['success', 'warning', 'warning']);
    }

    public function aRunLeftUnmatchedOrNewWithNoCategoryIsNotPassedOn(): void
    {
        $csR = $this->runs([]);
        $csR->shouldNotReceive('save');
        $importer = $this->importer();
        $importer->shouldReceive('save')->once()->withArgs(
            static fn (ParsedSchedule $schedule, array $runIds, array $workerIds): bool => [] === $runIds && [] === $workerIds
        )->andReturn(new ScheduleSaveResult(0, 0, [], []));
        [$controller] = $this->controller($importer, null, $csR);

        $controller->save($this->post([
            'schedule' => self::TEXT, 'year' => '2026', 'month' => '11',
            'run' => ['new', ''],
            'worker' => ['', 'x'],
        ]));

        Assert::same(array_column($this->flashes->getArrayCopy(), 0), ['success']);
    }

    public function clearingADayCallsTheCalendarAndRedirectsToItsMonth(): void
    {
        /** @var RunCalendarService&m\MockInterface $calendar */
        $calendar = m::mock(RunCalendarService::class);
        $calendar->shouldReceive('clear')->once()->with('2026-11-05');
        [$controller, , $redirect] = $this->controller(null, $calendar);

        Assert::same($controller->clear($this->post(['date' => '2026-11-05'])), $redirect);
        Assert::same($this->redirects[0], ['homecareschedule/index', ['year' => '2026', 'month' => '11']]);
    }

    public function aBadDateToClearIsA404(): void
    {
        /** @var RunCalendarService&m\MockInterface $calendar */
        $calendar = m::mock(RunCalendarService::class);
        $calendar->shouldNotReceive('clear');
        [$controller, $page] = $this->controller(null, $calendar);

        Assert::same($controller->clear($this->post(['date' => 'not-a-date'])), $page);
    }
}
