<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\FrontAccounting;

/**
 * Result of one FrontAccountingLookupSyncService::sync() run -- mirrors
 * App\Bookkeeping\Application\BookkeepingExportSummary's shape.
 */
final class FrontAccountingLookupSyncSummary
{
    /**
     * @param list<string> $messages One entry per kind that failed to sync.
     */
    public function __construct(
        public readonly int $syncedCount,
        public readonly array $messages,
    ) {
    }
}
