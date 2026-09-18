<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\Persistence;

use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Cycle's own native repository for the persisted BookkeepingTransaction
 * entity -- low-level query access, referenced by
 * BookkeepingTransaction's own #[Entity(repository: ...)] attribute.
 * CycleBookkeepingTransactionRepository (the BookkeepingTransactionRepositoryInterface
 * adapter the Application layer actually depends on) delegates to this
 * class rather than duplicating its queries.
 *
 * Deliberately not final -- CycleBookkeepingTransactionRepositoryTest
 * partial-mocks this class with Mockery (which cannot mock a final
 * class) rather than requiring a live database just to unit-test the
 * translation/orchestration logic in the class that depends on it.
 *
 * @template TEntity of BookkeepingTransaction
 * @extends Select\Repository<TEntity>
 */
class BookkeepingTransactionRepository extends Select\Repository
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
    public function save(BookkeepingTransaction $transaction): void
    {
        $this->entityWriter->write([$transaction]);
    }

    public function findByReference(string $reference): ?BookkeepingTransaction
    {
        /** @var ?BookkeepingTransaction */
        return $this->select()->where(['reference' => $reference])->fetchOne();
    }

    /**
     * @return list<BookkeepingTransaction>
     */
    public function findDueForExport(): array
    {
        /** @var list<BookkeepingTransaction> */
        return iterator_to_array($this->select()->where(['exported_at' => null])->fetchAll(), false);
    }
}
