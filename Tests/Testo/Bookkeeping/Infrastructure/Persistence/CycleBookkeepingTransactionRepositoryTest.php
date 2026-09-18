<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\Persistence;

use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction as DomainBookkeepingTransaction;
use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Domain\DebitCredit;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransaction as InfraBookkeepingTransaction;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionLine as InfraBookkeepingTransactionLine;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionLineRepository;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionRepository;
use App\Bookkeeping\Infrastructure\Persistence\CycleBookkeepingTransactionRepository;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * BookkeepingTransactionRepository/BookkeepingTransactionLineRepository
 * are partial-mocked here rather than exercised against a live database --
 * matches this app's existing precedent (StockMovementRepository, whose
 * own real Cycle query methods have no dedicated unit test either; only
 * consumers that use it are tested, with it mocked). Both classes are
 * deliberately not `final` for exactly this reason -- see their own
 * docblocks.
 */
#[Test]
final class CycleBookkeepingTransactionRepositoryTest
{
    private function domainTransaction(bool $persisted = false): DomainBookkeepingTransaction
    {
        $transaction = new DomainBookkeepingTransaction(
            BookkeepingTransactionType::InvoiceIssued,
            'INV-1024',
            new DateTimeImmutable('2026-09-18'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Debit, 120.00),
                new BookkeepingLine(AccountRole::Sales, DebitCredit::Credit, 100.00),
                new BookkeepingLine(AccountRole::VatOrTax, DebitCredit::Credit, 20.00),
            ],
            1024,
        );
        if ($persisted) {
            $transaction->setId(7);
        }
        return $transaction;
    }

    public function savePersistsANewTransactionAndAllOfItsLines(): void
    {
        $domainTransaction = $this->domainTransaction();

        /** @var BookkeepingTransactionRepository&m\MockInterface $transactions */
        $transactions = m::mock(BookkeepingTransactionRepository::class);
        $transactions->shouldReceive('save')
            ->once()
            ->with(m::type(InfraBookkeepingTransaction::class))
            ->andReturnUsing(static function (InfraBookkeepingTransaction $record): void {
                $record->setId(7);
            });

        /** @var BookkeepingTransactionLineRepository&m\MockInterface $lines */
        $lines = m::mock(BookkeepingTransactionLineRepository::class);
        $lines->shouldReceive('save')->times(3)->with(m::type(InfraBookkeepingTransactionLine::class));

        (new CycleBookkeepingTransactionRepository($transactions, $lines))->save($domainTransaction);

        Assert::true($domainTransaction->isPersisted());
        Assert::same(7, $domainTransaction->reqId());
    }

    public function saveOnAnAlreadyPersistedTransactionUpdatesWithoutRecreatingLines(): void
    {
        $domainTransaction = $this->domainTransaction(persisted: true);
        $domainTransaction->markExported('xero', 'XERO-9001', new DateTimeImmutable('2026-09-19'));

        $existingRecord = new InfraBookkeepingTransaction(
            type: BookkeepingTransactionType::InvoiceIssued->value,
            reference: 'INV-1024',
            date: new DateTimeImmutable('2026-09-18'),
            currency: 'GBP',
        );
        $existingRecord->setId(7);

        /** @var BookkeepingTransactionRepository&m\MockInterface $transactions */
        $transactions = m::mock(BookkeepingTransactionRepository::class);
        $transactions->shouldReceive('findByReference')->once()->with('INV-1024')->andReturn($existingRecord);
        $transactions->shouldReceive('save')->once()->with($existingRecord);

        /** @var BookkeepingTransactionLineRepository&m\MockInterface $lines */
        $lines = m::mock(BookkeepingTransactionLineRepository::class);
        $lines->shouldNotReceive('save');

        (new CycleBookkeepingTransactionRepository($transactions, $lines))->save($domainTransaction);

        Assert::same('xero', $existingRecord->getExportedProviderKey());
        Assert::same('XERO-9001', $existingRecord->getExportedProviderReference());
    }

    #[ExpectException(\LogicException::class)]
    public function saveThrowsWhenAnAlreadyPersistedTransactionHasNoMatchingRow(): void
    {
        $domainTransaction = $this->domainTransaction(persisted: true);

        /** @var BookkeepingTransactionRepository&m\MockInterface $transactions */
        $transactions = m::mock(BookkeepingTransactionRepository::class);
        $transactions->shouldReceive('findByReference')->andReturn(null);

        /** @var BookkeepingTransactionLineRepository&m\MockInterface $lines */
        $lines = m::mock(BookkeepingTransactionLineRepository::class);

        // NOSONAR php:S1848 — save() throwing is the assertion
        (new CycleBookkeepingTransactionRepository($transactions, $lines))->save($domainTransaction);
    }

    public function findByReferenceReturnsNullWhenNoRowMatches(): void
    {
        /** @var BookkeepingTransactionRepository&m\MockInterface $transactions */
        $transactions = m::mock(BookkeepingTransactionRepository::class);
        $transactions->shouldReceive('findByReference')->with('INV-9999')->andReturn(null);

        /** @var BookkeepingTransactionLineRepository&m\MockInterface $lines */
        $lines = m::mock(BookkeepingTransactionLineRepository::class);

        $result = (new CycleBookkeepingTransactionRepository($transactions, $lines))->findByReference('INV-9999');

        Assert::null($result);
    }

    public function findByReferenceMapsARecordAndItsLinesBackToTheDomainType(): void
    {
        $record = new InfraBookkeepingTransaction(
            type: BookkeepingTransactionType::InvoiceIssued->value,
            reference: 'INV-1024',
            date: new DateTimeImmutable('2026-09-18'),
            currency: 'GBP',
            inv_id: 1024,
            exported_provider_key: 'xero',
            exported_provider_reference: 'XERO-9001',
            exported_at: new DateTimeImmutable('2026-09-19'),
        );
        $record->setId(7);

        // Two lines, balanced (Domain's own constructor enforces this --
        // see BookkeepingTransactionTest's own rejectsUnbalancedLines).
        $debitLine = new InfraBookkeepingTransactionLine(
            bookkeeping_transaction_id: 7,
            account: AccountRole::Bank->value,
            direction: DebitCredit::Debit->value,
            amount: 100.00,
        );
        $creditLine = new InfraBookkeepingTransactionLine(
            bookkeeping_transaction_id: 7,
            account: AccountRole::AccountsReceivable->value,
            direction: DebitCredit::Credit->value,
            amount: 100.00,
        );

        /** @var BookkeepingTransactionRepository&m\MockInterface $transactions */
        $transactions = m::mock(BookkeepingTransactionRepository::class);
        $transactions->shouldReceive('findByReference')->with('INV-1024')->andReturn($record);

        /** @var BookkeepingTransactionLineRepository&m\MockInterface $lines */
        $lines = m::mock(BookkeepingTransactionLineRepository::class);
        $lines->shouldReceive('findAllForTransaction')->with(7)->andReturn([$debitLine, $creditLine]);

        $domainTransaction = (new CycleBookkeepingTransactionRepository($transactions, $lines))
            ->findByReference('INV-1024');

        Assert::notNull($domainTransaction);
        Assert::same('INV-1024', $domainTransaction->getReference());
        Assert::same(1024, $domainTransaction->getSourceInvId());
        Assert::count($domainTransaction->getLines(), 2);
        Assert::true($domainTransaction->isExported());
        Assert::same('xero', $domainTransaction->getExportedProviderKey());
        Assert::same('XERO-9001', $domainTransaction->getExportedProviderReference());
    }

    public function findDueForExportMapsEveryRecordReturned(): void
    {
        $recordA = new InfraBookkeepingTransaction(
            type: BookkeepingTransactionType::InvoiceIssued->value,
            reference: 'INV-1024',
            date: new DateTimeImmutable('2026-09-18'),
            currency: 'GBP',
        );
        $recordA->setId(7);
        $recordB = new InfraBookkeepingTransaction(
            type: BookkeepingTransactionType::PaymentReceived->value,
            reference: 'INV-1025',
            date: new DateTimeImmutable('2026-09-18'),
            currency: 'GBP',
        );
        $recordB->setId(8);

        // A balanced pair of lines -- Domain's own constructor rejects an
        // empty line list (see BookkeepingTransactionTest's own
        // rejectsAnEmptyLineList), so each mapped record needs at least
        // one balanced pair, not [].
        $balancedLines = [
            new InfraBookkeepingTransactionLine(
                account: AccountRole::Bank->value,
                direction: DebitCredit::Debit->value,
                amount: 50.00,
            ),
            new InfraBookkeepingTransactionLine(
                account: AccountRole::AccountsReceivable->value,
                direction: DebitCredit::Credit->value,
                amount: 50.00,
            ),
        ];

        /** @var BookkeepingTransactionRepository&m\MockInterface $transactions */
        $transactions = m::mock(BookkeepingTransactionRepository::class);
        $transactions->shouldReceive('findDueForExport')->andReturn([$recordA, $recordB]);

        /** @var BookkeepingTransactionLineRepository&m\MockInterface $lines */
        $lines = m::mock(BookkeepingTransactionLineRepository::class);
        $lines->shouldReceive('findAllForTransaction')->andReturn($balancedLines);

        $due = (new CycleBookkeepingTransactionRepository($transactions, $lines))->findDueForExport();

        Assert::count($due, 2);
        Assert::same('INV-1024', $due[0]->getReference());
        Assert::same('INV-1025', $due[1]->getReference());
    }
}
