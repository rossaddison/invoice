<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareAlias\HomeCareAlias;

interface HomeCareAliasRepositoryInterface
{
    public function save(array|HomeCareAlias|null $alias): void;

    /**
     * What a name written in the schedule is known to mean, if it has been confirmed before.
     *
     * @param string $kind HomeCareAlias::KIND_RUN or HomeCareAlias::KIND_WORKER
     * @param string $alias already normalised (see ScheduleNameMatcher::normalise())
     */
    public function repoLookupquery(string $kind, string $alias): ?HomeCareAlias;
}
