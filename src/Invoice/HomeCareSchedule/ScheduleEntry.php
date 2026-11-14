<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

/**
 * One day of a pasted run schedule: the date, the run (null: an open day, such as a Wednesday kept
 * free for a weather swap) and who is on the crew.
 */
final readonly class ScheduleEntry
{
    /**
     * @param string $date Y-m-d
     * @param int $weekday ISO: 1 = Monday ... 7 = Sunday
     * @param string|null $runName the run as written in the schedule ("Nor 2", "Sam's East")
     * @param list<string> $crewNames the crew as written ("Andy", "Dan"), in the order given
     * @param list<string> $ruleCrewNames names added by a weekly rule ("Andrew is every Monday and
     *     Tuesday") that the line itself did not give
     * @param bool $crewFromDitto the crew was written as ditto marks ("), meaning the previous day's
     * @param int $line the 1-based line of the pasted text
     */
    public function __construct(
        public string $date,
        public int $weekday,
        public ?string $runName,
        public array $crewNames,
        public array $ruleCrewNames,
        public bool $crewFromDitto,
        public int $line,
    ) {
    }

    public function isOpenDay(): bool
    {
        return null === $this->runName;
    }

    /**
     * Everyone on the crew as the schedule gives it: the written crew, then anyone a weekly rule adds.
     *
     * @return list<string>
     */
    public function allCrewNames(): array
    {
        return array_values(array_unique([...$this->crewNames, ...$this->ruleCrewNames]));
    }
}
