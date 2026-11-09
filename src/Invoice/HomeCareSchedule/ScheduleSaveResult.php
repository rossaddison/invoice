<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

/**
 * What saving a schedule did, for the flash message.
 */
final readonly class ScheduleSaveResult
{
    /**
     * @param int $created days newly put on the calendar
     * @param int $changed days that already had an entry, now replaced
     * @param list<string> $skippedDates days left out because their run was not chosen
     * @param list<string> $droppedCrewNames crew names left off because no worker was chosen
     */
    public function __construct(
        public int $created,
        public int $changed,
        public array $skippedDates,
        public array $droppedCrewNames,
    ) {
    }
}
