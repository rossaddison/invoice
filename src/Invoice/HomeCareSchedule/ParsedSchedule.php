<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

/**
 * What {@see RunScheduleParser} read from a pasted schedule: nothing is saved yet; this is what the
 * preview shows for a person to confirm.
 */
final readonly class ParsedSchedule
{
    /**
     * @param list<ScheduleEntry> $entries every dated line, in the order written
     * @param list<WeeklyRule> $rules standing rules such as "Andrew is every Monday and Tuesday"
     * @param list<string> $warnings anything the parser could not make sense of, for the preview
     */
    public function __construct(
        public int $year,
        public int $month,
        public array $entries,
        public array $rules,
        public array $warnings,
    ) {
    }

    /**
     * The days that have a run (open days left out).
     *
     * @return list<ScheduleEntry>
     */
    public function runDays(): array
    {
        return array_values(array_filter($this->entries, static fn (ScheduleEntry $e): bool => !$e->isOpenDay()));
    }

    /**
     * Every distinct run name as written, in order of first appearance.
     *
     * @return list<string>
     */
    public function runNames(): array
    {
        $names = [];
        foreach ($this->runDays() as $entry) {
            $names[(string) $entry->runName] = true;
        }
        return array_map('strval', array_keys($names));
    }

    /**
     * Every distinct crew name as written (including those a weekly rule adds), in order of first appearance.
     *
     * @return list<string>
     */
    public function crewNames(): array
    {
        $names = [];
        foreach ($this->runDays() as $entry) {
            foreach ($entry->allCrewNames() as $name) {
                $names[$name] = true;
            }
        }
        return array_map('strval', array_keys($names));
    }
}
