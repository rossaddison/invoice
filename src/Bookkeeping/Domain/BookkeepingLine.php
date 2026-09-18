<?php

declare(strict_types=1);

namespace App\Bookkeeping\Domain;

use InvalidArgumentException;

/**
 * One debit or credit entry within a BookkeepingTransaction. Immutable --
 * a line is never edited in place; a correction is a new, reversing
 * BookkeepingTransaction instead (matches how this app already treats
 * stock: App\Infrastructure\Persistence\StockMovement\StockMovement is an
 * append-only ledger, not a mutable row, applied here to money instead of
 * quantity).
 *
 * No $currency here -- it lives once on the owning BookkeepingTransaction,
 * since every line within one balanced double-entry transaction shares
 * the same currency; a per-line currency would need FX conversion to even
 * evaluate the transaction's own debit==credit balance invariant, which
 * is out of scope for now.
 *
 * $taxCode is a plain nullable string for now rather than tied into this
 * app's existing TaxRate/Peppol tax-category system -- that integration
 * is a real future step, not yet scoped.
 */
final readonly class BookkeepingLine
{
    public function __construct(
        public AccountRole $account,
        public DebitCredit $direction,
        public float $amount,
        public ?string $taxCode = null,
        public ?string $description = null,
    ) {
        if ($amount <= 0.0) {
            throw new InvalidArgumentException(
                'BookkeepingLine amount must be greater than zero, got ' . $amount . '.'
            );
        }
    }

    public function isDebit(): bool
    {
        return $this->direction === DebitCredit::Debit;
    }

    public function isCredit(): bool
    {
        return $this->direction === DebitCredit::Credit;
    }
}
