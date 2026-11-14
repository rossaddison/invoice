<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareAlias\HomeCareAlias;

/**
 * Turns a parsed schedule into calendar days. {@see suggest()} proposes which real run and worker
 * each written name means (for the preview); {@see save()} writes the days once a person has
 * confirmed the choices, and remembers each confirmed name so next month's schedule maps by itself.
 */
final readonly class ScheduleImporter
{
    public function __construct(
        private ScheduleNameMatcher $matcher,
        private RunCalendarService $calendar,
    ) {
    }

    /**
     * The proposed match for each name, keyed by the name as written; null where it is not certain.
     *
     * @param array<int, string> $runs run id => name
     * @param array<int, string> $workers worker id => first name
     * @return array{runs: array<string, int|null>, workers: array<string, int|null>}
     */
    public function suggest(ParsedSchedule $schedule, array $runs, array $workers): array
    {
        $suggestedRuns = [];
        foreach ($schedule->runNames() as $name) {
            $suggestedRuns[$name] = $this->matcher->matchRun($name, $runs);
        }
        $suggestedWorkers = [];
        foreach ($schedule->crewNames() as $name) {
            $suggestedWorkers[$name] = $this->matcher->matchWorker($name, $workers);
        }
        return ['runs' => $suggestedRuns, 'workers' => $suggestedWorkers];
    }

    /**
     * Saves every day whose run was resolved. A day whose run is not resolved is skipped; a crew
     * name that is not resolved is left off that day's crew. Both are reported, never guessed.
     *
     * @param array<string, int> $runIds written run name => run id
     * @param array<string, int> $workerIds written crew name => worker id
     */
    public function save(ParsedSchedule $schedule, array $runIds, array $workerIds): ScheduleSaveResult
    {
        $this->rememberConfirmed(HomeCareAlias::KIND_RUN, $runIds);
        $this->rememberConfirmed(HomeCareAlias::KIND_WORKER, $workerIds);
        $created = 0;
        $changed = 0;
        $skipped = [];
        $dropped = [];
        foreach ($schedule->runDays() as $entry) {
            $runId = $runIds[(string) $entry->runName] ?? 0;
            if ($runId <= 0) {
                $skipped[] = $entry->date;
                continue;
            }
            $crew = [];
            foreach ($entry->allCrewNames() as $name) {
                $workerId = $workerIds[$name] ?? 0;
                if ($workerId > 0) {
                    $crew[] = $workerId;
                } else {
                    $dropped[$name] = true;
                }
            }
            $this->calendar->schedule($entry->date, $runId, $crew) ? $created++ : $changed++;
        }
        return new ScheduleSaveResult($created, $changed, $skipped, array_map('strval', array_keys($dropped)));
    }

    /**
     * Remembers each confirmed name (id above zero) so the next schedule maps it by itself.
     *
     * @param array<string, int> $ids written name => id
     */
    private function rememberConfirmed(string $kind, array $ids): void
    {
        foreach ($ids as $name => $id) {
            if ($id > 0) {
                $this->matcher->remember($kind, $name, $id);
            }
        }
    }
}
