<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

/**
 * A standing rule written in the schedule text, such as "Andrew is every Monday and Tuesday": that
 * worker is on the crew of every run on those weekdays.
 */
final readonly class WeeklyRule
{
    /**
     * @param string $name the worker as written
     * @param list<int> $weekdays ISO weekdays, 1 = Monday ... 7 = Sunday
     */
    public function __construct(
        public string $name,
        public array $weekdays,
    ) {
    }
}
