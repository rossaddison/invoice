<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheetItem;

use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;

interface HomeCareRunSheetItemRepositoryInterface
{
    public function save(array|HomeCareRunSheetItem|null $item): void;

    public function delete(array|HomeCareRunSheetItem|null $item): void;

    /**
     * Every item snapshotted into a run sheet at export time, in the order
     * they were added.
     *
     * @return array<int, HomeCareRunSheetItem>
     */
    public function repoForRunSheetquery(int $runSheetId): array;

    /**
     * Only the items whose vision-detected values actually differ from what
     * was expected — the "temporary index of adjustments" staging view is
     * built from exactly this set, not the full run sheet.
     *
     * @return array<int, HomeCareRunSheetItem>
     */
    public function repoChangedForRunSheetquery(int $runSheetId): array;
}
