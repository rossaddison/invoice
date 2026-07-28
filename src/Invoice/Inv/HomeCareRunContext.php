<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

/**
 * The HomeCare "current run" selected on `inv/index`: which
 * category_secondary is being filtered on, and whether the configured
 * `homecare_current_run_last_run_date` setting should additionally apply
 * (only when the effective category_secondary matches the one configured
 * in Settings — browsing a different/past category_secondary via the same
 * dropdown filters by category_secondary alone).
 */
final readonly class HomeCareRunContext
{
    public function __construct(
        public int $categorySecondaryId,
        public bool $applyDateFilter,
        public string $lastRunDate,
    ) {}
}
