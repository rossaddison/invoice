<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\Persistence;

use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransaction;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionRepository;
use Cycle\ORM\Select;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Cycle's own Select\Repository::select() (the parent class's method,
 * unmodified here) returns `clone $this->select` -- a Mockery mock
 * survives that clone fine (PHP's default clone is a shallow property
 * copy, and Mockery stores its stubbed expectations as plain instance
 * state), so a mocked Select configured here still behaves as stubbed
 * after the repository clones it internally on every call.
 */
#[Test]
final class BookkeepingTransactionRepositoryTest
{
    private function transaction(): BookkeepingTransaction
    {
        return new BookkeepingTransaction(
            type: BookkeepingTransactionType::InvoiceIssued->value,
            reference: 'INV-1024',
            date: new DateTimeImmutable('2026-09-18'),
            currency: 'GBP',
        );
    }

    public function saveDelegatesToTheEntityWriter(): void
    {
        $transaction = $this->transaction();

        /** @var Select<BookkeepingTransaction>&m\MockInterface $select */
        $select = m::mock(Select::class);
        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);
        $entityWriter->shouldReceive('write')->once()->with([$transaction]);

        (new BookkeepingTransactionRepository($select, $entityWriter))->save($transaction);
    }

    public function findByReferenceQueriesByTheReferenceColumn(): void
    {
        $transaction = $this->transaction();

        /** @var Select<BookkeepingTransaction>&m\MockInterface $select */
        $select = m::mock(Select::class);
        $select->shouldReceive('where')->once()->with(['reference' => 'INV-1024'])->andReturnSelf();
        $select->shouldReceive('fetchOne')->once()->andReturn($transaction);

        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);

        $result = (new BookkeepingTransactionRepository($select, $entityWriter))->findByReference('INV-1024');

        Assert::same($transaction, $result);
    }

    public function findByReferenceReturnsNullWhenNothingMatches(): void
    {
        /** @var Select<BookkeepingTransaction>&m\MockInterface $select */
        $select = m::mock(Select::class);
        $select->shouldReceive('where')->andReturnSelf();
        $select->shouldReceive('fetchOne')->andReturn(null);

        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);

        $result = (new BookkeepingTransactionRepository($select, $entityWriter))->findByReference('INV-9999');

        Assert::null($result);
    }

    public function findDueForExportQueriesForANullExportedAt(): void
    {
        $transaction = $this->transaction();

        /** @var Select<BookkeepingTransaction>&m\MockInterface $select */
        $select = m::mock(Select::class);
        $select->shouldReceive('where')->once()->with(['exported_at' => null])->andReturnSelf();
        $select->shouldReceive('fetchAll')->once()->andReturn([$transaction]);

        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);

        $result = (new BookkeepingTransactionRepository($select, $entityWriter))->findDueForExport();

        Assert::same([$transaction], $result);
    }
}
