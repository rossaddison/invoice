<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet;

use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use DateTimeImmutable;

interface HomeCareRunSheetRepositoryInterface
{
    public function save(array|HomeCareRunSheet|null $runSheet): void;

    public function delete(array|HomeCareRunSheet|null $runSheet): void;

    public function repoLoadedquery(int $id): ?HomeCareRunSheet;

    /**
     * The run sheet already open (not yet Applied) for this
     * category_secondary_id + run_date pair, if one exists — a run should
     * only ever have one live cycle in flight at a time.
     */
    public function repoOpenForRunquery(int $categorySecondaryId, DateTimeImmutable $runDate): ?HomeCareRunSheet;

    /**
     * Most recent run sheets across every run, for the admin-visible list.
     *
     * @return iterable<HomeCareRunSheet>
     */
    public function repoRecentquery(int $limit): iterable;
}
