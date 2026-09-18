<?php

declare(strict_types=1);

namespace App\Bookkeeping\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

/**
 * One accounting event -- issuing an invoice, receiving a payment, a
 * merchant fee, a refund -- each its own BookkeepingTransaction with its
 * own balanced set of debit/credit lines, never a single mutable "export
 * this invoice" record (see BookkeepingTransactionType). Mirrors how this
 * app already treats stock
 * (App\Infrastructure\Persistence\StockMovement\StockMovement: one row
 * per event, a ledger not a mutable counter) applied to money instead of
 * quantity.
 *
 * Pure domain object -- no Cycle ORM, no framework dependency of any
 * kind, so the one rule that actually matters (debits must equal
 * credits) is enforced here and is trivially unit-testable with no DB.
 * The Infrastructure-layer persisted counterpart
 * (App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransaction)
 * deliberately shares this class's name, disambiguated by namespace; a
 * repository in that layer maps between the two.
 *
 * $reference is this transaction's idempotency key -- reuses this app's
 * existing provider_reference vocabulary/convention already used across
 * payment webhook handlers and PaymentRefundController, rather than
 * inventing a parallel bookkeep_reference concept for the same problem
 * (did this already get posted, or did the request just time out).
 */
final class BookkeepingTransaction
{
    private ?int $id = null;

    // Export/audit-trail state -- implements the bookkeep_provider/
    // bookkeep_reference/bookkeep_exported_at fields from this module's
    // original design discussion as behaviour on the entity itself,
    // since "has this been posted, and where" is genuine business state,
    // not a mere storage detail. $exportedProviderReference is the
    // PROVIDER's own assigned id (e.g. QuickBooks' JournalEntry id) -- distinct
    // from $reference above, which is this app's own idempotency key.
    private ?string $exportedProviderKey = null;
    private ?string $exportedProviderReference = null;
    private ?DateTimeImmutable $exportedAt = null;

    /**
     * @param list<BookkeepingLine> $lines Accepts an empty list at the type
     *     level deliberately -- assertBalanced() below is the actual
     *     enforcement point, since this app's Application layer builds
     *     $lines from external/runtime data no static type can guarantee
     *     non-empty in practice.
     */
    public function __construct(
        private readonly BookkeepingTransactionType $type,
        private readonly string $reference,
        private readonly DateTimeImmutable $date,
        private readonly string $currency,
        private readonly array $lines,
        private readonly ?int $sourceInvId = null,
    ) {
        $this->assertBalanced($this->lines);
    }

    /**
     * @param BookkeepingLine[] $lines
     */
    private function assertBalanced(array $lines): void
    {
        if ($lines === []) {
            throw new InvalidArgumentException(
                'BookkeepingTransaction ' . $this->reference . ' must have at least one line.'
            );
        }

        $debits = 0.0;
        $credits = 0.0;
        foreach ($lines as $line) {
            if ($line->isDebit()) {
                $debits += $line->amount;
            } else {
                $credits += $line->amount;
            }
        }

        // Float comparison at money-typical precision -- matches this
        // app's existing decimal(20,2) convention for monetary columns
        // (see e.g. Payment::$amount), not a BigDecimal/minor-units
        // representation.
        if (abs($debits - $credits) > 0.005) {
            throw new InvalidArgumentException(
                sprintf(
                    'BookkeepingTransaction %s is not balanced: debits %.2f != credits %.2f.',
                    $this->reference,
                    $debits,
                    $credits,
                )
            );
        }
    }

    public function getType(): BookkeepingTransactionType
    {
        return $this->type;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /** @return list<BookkeepingLine> */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getSourceInvId(): ?int
    {
        return $this->sourceInvId;
    }

    public function reqId(): int
    {
        if ($this->id === null) {
            throw new LogicException('BookkeepingTransaction not persisted');
        }

        return $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Records that this transaction was successfully posted (or
     * confirmed already posted, via a gateway's getTransaction() lookup)
     * to the given provider. Safe to call more than once -- re-confirming
     * an already-exported transaction on a later run overwrites with the
     * same facts rather than needing a guard, matching this app's
     * existing StockMovement::setId()'s own unguarded-reassignment style.
     */
    public function markExported(string $providerKey, string $providerReference, DateTimeImmutable $exportedAt): void
    {
        $this->exportedProviderKey = $providerKey;
        $this->exportedProviderReference = $providerReference;
        $this->exportedAt = $exportedAt;
    }

    public function isExported(): bool
    {
        return $this->exportedAt !== null;
    }

    public function getExportedProviderKey(): ?string
    {
        return $this->exportedProviderKey;
    }

    public function getExportedProviderReference(): ?string
    {
        return $this->exportedProviderReference;
    }

    public function getExportedAt(): ?DateTimeImmutable
    {
        return $this->exportedAt;
    }
}
