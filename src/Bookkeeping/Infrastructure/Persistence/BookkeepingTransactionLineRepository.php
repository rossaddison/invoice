<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\Persistence;

use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Deliberately not final -- see BookkeepingTransactionRepository's own
 * docblock for why (Mockery-mockability without a live database).
 *
 * @template TEntity of BookkeepingTransactionLine
 * @extends Select\Repository<TEntity>
 */
class BookkeepingTransactionLineRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @throws Throwable
     */
    public function save(BookkeepingTransactionLine $line): void
    {
        $this->entityWriter->write([$line]);
    }

    /**
     * @return list<BookkeepingTransactionLine>
     */
    public function findAllForTransaction(int $bookkeepingTransactionId): array
    {
        /** @var list<BookkeepingTransactionLine> */
        return iterator_to_array(
            $this->select()->where(['bookkeeping_transaction_id' => $bookkeepingTransactionId])->fetchAll(),
            false,
        );
    }
}
