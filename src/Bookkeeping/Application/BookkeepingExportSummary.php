<?php

declare(strict_types=1);

namespace App\Bookkeeping\Application;

/**
 * Result of one BookkeepingService::exportDueTransactions() run -- a
 * caller (a future console command, most likely) logs/reports $messages
 * as needed; this class holds no opinion on how.
 */
final class BookkeepingExportSummary
{
    /**
     * @param list<string> $messages One entry per failure, prefixed with
     *     the transaction's own reference where applicable.
     */
    public function __construct(
        public readonly int $exportedCount,
        public readonly int $failedCount,
        public readonly array $messages,
    ) {
    }
}
