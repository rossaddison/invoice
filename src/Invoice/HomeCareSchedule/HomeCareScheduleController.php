<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\BaseController;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as CPR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Worker\WorkerRepository as WR;
use App\Service\WebControllerService;
use App\User\UserService;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The run calendar page: paste the monthly schedule as it was sent round (WhatsApp text), check how
 * each run and worker name was read, then save it. Nothing is written until the second step.
 */
final class HomeCareScheduleController extends BaseController
{
    protected string $controllerName = 'invoice/homecareschedule';

    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
        private readonly RunScheduleParser $parser,
        private readonly ScheduleImporter $importer,
        private readonly RunCalendarService $calendar,
        private readonly CSR $csR,
        private readonly CPR $cpR,
        private readonly WR $wR,
    ) {
        parent::__construct(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash
        );
    }

    /**
     * The month's saved calendar, with the paste box.
     */
    public function index(Request $request): Response
    {
        $now = new DateTimeImmutable('now');
        $query = $request->getQueryParams();
        $year = (int) ($query['year'] ?? $now->format('Y'));
        $month = (int) ($query['month'] ?? $now->format('n'));
        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            $year = (int) $now->format('Y');
            $month = (int) $now->format('n');
        }
        $names = $this->workerNames();
        $days = [];
        foreach ($this->calendar->forMonth($year, $month) as $day) {
            $days[] = [
                'date' => $day->getRunDate(),
                'run' => $this->csR->repoCategorySecondaryQuery($day->getCategorySecondaryId()),
                'crew' => array_map(static fn (int $id): string => $names[$id] ?? '?', $day->getCrewWorkerIds()),
            ];
        }
        return $this->webViewRenderer->render('index', [
            'year' => $year,
            'month' => $month,
            'days' => $days,
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Reads the pasted text and shows how each run and worker name was understood.
     */
    public function preview(Request $request): Response
    {
        $body = $this->body($request);
        $text = $this->field($body, 'schedule');
        $year = (int) $this->field($body, 'year', date('Y'));
        $month = (int) $this->field($body, 'month', date('n'));
        $schedule = $this->parser->parse($text, $year, $month);
        if ([] === $schedule->entries) {
            $this->flashMessage('danger', $this->translator->translate('homecare.schedule.nothing.read'));
            return $this->webService->getRedirectResponse('homecareschedule/index', [], ['year' => (string) $year, 'month' => (string) $month]);
        }
        $runs = [];
        $startless = [];
        foreach ($this->csR->findAllPreloaded() as $run) {
            if (!$run instanceof CategorySecondary) {
                continue;
            }
            $runs[$run->reqId()] = $run->getName() ?? '';
            $startless[$run->reqId()] = in_array($run->getStartPostcode(), [null, ''], true);
        }
        $workers = $this->workerNames();
        $existing = [];
        foreach ($schedule->entries as $entry) {
            $existing[$entry->date] = null !== $this->calendar->forDate($entry->date);
        }
        return $this->webViewRenderer->render('preview', [
            'schedule' => $schedule,
            'text' => $text,
            'suggested' => $this->importer->suggest($schedule, $runs, $workers),
            'runs' => $runs,
            'workers' => $workers,
            'existing' => $existing,
            'startless' => $startless,
            'primaries' => $this->cpR->optionsDataCategoryPrimaries(),
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Saves the schedule with the run and worker choices made in the preview.
     */
    public function save(Request $request): Response
    {
        $body = $this->body($request);
        $year = (int) $this->field($body, 'year', date('Y'));
        $month = (int) $this->field($body, 'month', date('n'));
        $schedule = $this->parser->parse($this->field($body, 'schedule'), $year, $month);
        $primaryId = (int) $this->field($body, 'new_run_category_primary_id', '0');

        $runIds = [];
        $choices = $this->choices($body, 'run');
        foreach ($schedule->runNames() as $i => $name) {
            $choice = $choices[$i] ?? '';
            if ('new' === $choice && $primaryId > 0) {
                $created = new CategorySecondary(null, $primaryId, $name);
                $this->csR->save($created);
                $runIds[$name] = $created->reqId();
            } elseif (ctype_digit($choice) && (int) $choice > 0) {
                $runIds[$name] = (int) $choice;
            }
        }
        $workerIds = [];
        $choices = $this->choices($body, 'worker');
        foreach ($schedule->crewNames() as $i => $name) {
            $choice = $choices[$i] ?? '';
            if ('new' === $choice) {
                $created = new Worker($name);
                $this->wR->save($created);
                $workerIds[$name] = $created->reqId();
            } elseif (ctype_digit($choice) && (int) $choice > 0) {
                $workerIds[$name] = (int) $choice;
            }
        }

        $result = $this->importer->save($schedule, $runIds, $workerIds);
        $this->flashMessage('success', sprintf(
            $this->translator->translate('homecare.schedule.saved'),
            $result->created,
            $result->changed
        ));
        if ([] !== $result->skippedDates) {
            $this->flashMessage('warning', $this->translator->translate('homecare.schedule.skipped') . ' ' . implode(', ', $result->skippedDates));
        }
        if ([] !== $result->droppedCrewNames) {
            $this->flashMessage('warning', $this->translator->translate('homecare.schedule.dropped') . ' ' . implode(', ', $result->droppedCrewNames));
        }
        return $this->webService->getRedirectResponse('homecareschedule/index', [], ['year' => (string) $schedule->year, 'month' => (string) $schedule->month]);
    }

    /**
     * Clears one day back to an open day.
     */
    public function clear(Request $request): Response
    {
        $date = $this->field($this->body($request), 'date');
        $when = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (false === $when) {
            return $this->webService->getNotFoundResponse();
        }
        $this->calendar->clear($date);
        return $this->webService->getRedirectResponse('homecareschedule/index', [], ['year' => $when->format('Y'), 'month' => $when->format('n')]);
    }

    /**
     * @return array<string, string|array<array-key, string>>
     */
    private function body(Request $request): array
    {
        /** @var array<string, string|array<array-key, string>>|null|object $body */
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }

    /**
     * @param array<string, string|array<array-key, string>> $body
     */
    private function field(array $body, string $key, string $default = ''): string
    {
        $value = $body[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * The select values posted as "key[i]", by position.
     *
     * @param array<string, string|array<array-key, string>> $body
     * @return array<int, string>
     */
    private function choices(array $body, string $key): array
    {
        $posted = $body[$key] ?? [];
        $out = [];
        if (is_array($posted)) {
            foreach ($posted as $i => $value) {
                $out[(int) $i] = $value;
            }
        }
        return $out;
    }

    /**
     * @return array<int, string> worker id => first name
     */
    private function workerNames(): array
    {
        $names = [];
        foreach ($this->wR->findAllActive() as $worker) {
            $names[$worker->reqId()] = $worker->getFirstname();
        }
        return $names;
    }
}
