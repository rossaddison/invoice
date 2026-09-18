<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\Persistence;

use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\DebitCredit;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionLine;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionLineRepository;
use Cycle\ORM\Select;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * See BookkeepingTransactionRepositoryTest's own docblock for why a
 * mocked Select still behaves correctly after Select\Repository::select()
 * clones it internally.
 */
#[Test]
final class BookkeepingTransactionLineRepositoryTest
{
    private function line(): BookkeepingTransactionLine
    {
        return new BookkeepingTransactionLine(
            bookkeeping_transaction_id: 7,
            account: AccountRole::Bank->value,
            direction: DebitCredit::Debit->value,
            amount: 100.00,
        );
    }

    public function saveDelegatesToTheEntityWriter(): void
    {
        $line = $this->line();

        /** @var Select<BookkeepingTransactionLine>&m\MockInterface $select */
        $select = m::mock(Select::class);
        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);
        $entityWriter->shouldReceive('write')->once()->with([$line]);

        (new BookkeepingTransactionLineRepository($select, $entityWriter))->save($line);
    }

    public function findAllForTransactionQueriesByTheForeignKeyColumn(): void
    {
        $line = $this->line();

        /** @var Select<BookkeepingTransactionLine>&m\MockInterface $select */
        $select = m::mock(Select::class);
        $select->shouldReceive('where')->once()->with(['bookkeeping_transaction_id' => 7])->andReturnSelf();
        $select->shouldReceive('fetchAll')->once()->andReturn([$line]);

        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);

        $result = (new BookkeepingTransactionLineRepository($select, $entityWriter))->findAllForTransaction(7);

        Assert::same([$line], $result);
    }

    public function findAllForTransactionReturnsAnEmptyListWhenNoneMatch(): void
    {
        /** @var Select<BookkeepingTransactionLine>&m\MockInterface $select */
        $select = m::mock(Select::class);
        $select->shouldReceive('where')->andReturnSelf();
        $select->shouldReceive('fetchAll')->andReturn([]);

        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);

        $result = (new BookkeepingTransactionLineRepository($select, $entityWriter))->findAllForTransaction(9999);

        Assert::same([], $result);
    }
}
