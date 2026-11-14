<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareRunDay\HomeCareRunDay;

interface HomeCareRunDayRepositoryInterface
{
    public function save(array|HomeCareRunDay|null $runDay): void;

    public function delete(array|HomeCareRunDay|null $runDay): void;

    /**
     * The run scheduled on a date (Y-m-d), if any.
     */
    public function repoDatequery(string $date): ?HomeCareRunDay;

    /**
     * The scheduled days from $from (inclusive) to $toExclusive, earliest first.
     *
     * @return list<HomeCareRunDay>
     */
    public function repoRangequery(string $from, string $toExclusive): array;
}
