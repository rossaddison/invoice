<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\Persistence;

use App\Bookkeeping\Application\BookkeepingTransactionRepositoryInterface;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction as DomainBookkeepingTransaction;
use DateTimeImmutable;
use LogicException;

/**
 * The translation boundary between the pure
 * App\Bookkeeping\Domain\BookkeepingTransaction and its Cycle-attributed
 * counterpart in this same namespace -- the one class in this module
 * that is allowed to know both exist. BookkeepingService (Application)
 * only ever sees the Domain type, through the
 * BookkeepingTransactionRepositoryInterface port this class implements.
 */
final class CycleBookkeepingTransactionRepository implements BookkeepingTransactionRepositoryInterface
{
    public function __construct(
        private readonly BookkeepingTransactionRepository $transactions,
        private readonly BookkeepingTransactionLineRepository $lines,
    ) {
    }

    #[\Override]
    public function save(DomainBookkeepingTransaction $transaction): void
    {
        $isNew = !$transaction->isPersisted();

        // An update re-fetches its own record rather than the caller
        // handing back a cached Infrastructure instance -- Cycle tracks
        // changes by object identity in its own Heap, and the Domain
        // layer deliberately never holds or exposes an Infrastructure
        // entity to preserve the layer boundary. Costs one extra query
        // per update; this module's volume (accounting exports, not
        // hot-path traffic) makes that the right tradeoff over threading
        // the Infrastructure instance through the Domain type.
        $record = $isNew
            ? new BookkeepingTransaction(
                type: $transaction->getType()->value,
                reference: $transaction->getReference(),
                date: $transaction->getDate(),
                currency: $transaction->getCurrency(),
                inv_id: $transaction->getSourceInvId(),
            )
            : $this->transactions->findByReference($transaction->getReference());

        if ($record === null) {
            throw new LogicException(
                'BookkeepingTransaction ' . $transaction->getReference() . ' is marked persisted but no matching row was found.'
            );
        }

        $record->setExportedProviderKey($transaction->getExportedProviderKey());
        $record->setExportedProviderReference($transaction->getExportedProviderReference());
        $record->setExportedAt($transaction->getExportedAt());

        $this->transactions->save($record);

        if ($isNew) {
            $transaction->setId($record->reqId());
            foreach ($transaction->getLines() as $line) {
                $lineRecord = new BookkeepingTransactionLine(
                    bookkeeping_transaction_id: $record->reqId(),
                    account: $line->account->value,
                    direction: $line->direction->value,
                    amount: $line->amount,
                    tax_code: $line->taxCode,
                    description: $line->description,
                );
                $this->lines->save($lineRecord);
            }
        }
    }

    #[\Override]
    public function findByReference(string $reference): ?DomainBookkeepingTransaction
    {
        $record = $this->transactions->findByReference($reference);
        if ($record === null) {
            return null;
        }

        return $this->toDomain($record);
    }

    /**
     * @return list<DomainBookkeepingTransaction>
     */
    #[\Override]
    public function findDueForExport(): array
    {
        return array_map(
            fn(BookkeepingTransaction $record): DomainBookkeepingTransaction => $this->toDomain($record),
            $this->transactions->findDueForExport(),
        );
    }

    private function toDomain(BookkeepingTransaction $record): DomainBookkeepingTransaction
    {
        $lines = array_map(
            static fn(BookkeepingTransactionLine $line): BookkeepingLine => new BookkeepingLine(
                $line->getAccount(),
                $line->getDirection(),
                $line->getAmount(),
                $line->getTaxCode(),
                $line->getDescription(),
            ),
            $this->lines->findAllForTransaction($record->reqId()),
        );

        $transaction = new DomainBookkeepingTransaction(
            $record->getType(),
            $record->getReference(),
            $record->getDate(),
            $record->getCurrency(),
            $lines,
            $record->getInvId(),
        );
        $transaction->setId($record->reqId());

        if ($record->isExported()) {
            $transaction->markExported(
                (string) $record->getExportedProviderKey(),
                (string) $record->getExportedProviderReference(),
                $record->getExportedAt() ?? new DateTimeImmutable(),
            );
        }

        return $transaction;
    }
}
