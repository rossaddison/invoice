<?php

declare(strict_types=1);

namespace App\Bookkeeping\Application;

use App\Bookkeeping\Domain\BookkeepingTransaction;

/**
 * Persistence port for BookkeepingTransaction -- implemented in
 * Infrastructure/Persistence by a class that maps between this pure
 * Domain entity and its Cycle-attributed persisted counterpart (also
 * named BookkeepingTransaction, disambiguated by namespace).
 *
 * Kept separate from BookkeepingGatewayInterface deliberately: persisting
 * our own audit-trail row and exporting to an external provider are two
 * different concerns that happen to both be "save this transaction
 * somewhere" -- BookkeepingService uses both together, but neither knows
 * about the other.
 */
interface BookkeepingTransactionRepositoryInterface
{
    public function save(BookkeepingTransaction $transaction): void;

    public function findByReference(string $reference): ?BookkeepingTransaction;

    /**
     * Every persisted transaction still due for export -- not yet
     * exported (BookkeepingTransaction::isExported() false).
     *
     * @return list<BookkeepingTransaction>
     */
    public function findDueForExport(): array;
}
