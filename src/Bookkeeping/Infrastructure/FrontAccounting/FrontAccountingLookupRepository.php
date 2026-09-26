<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\FrontAccounting;

use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Cycle's own native repository for the cached FrontAccountingLookup
 * entity, referenced by its own #[Entity(repository: ...)] attribute.
 *
 * Deliberately not final -- same reasoning as BookkeepingTransactionRepository:
 * FrontAccountingLookupSyncServiceTest partial-mocks this class with
 * Mockery (which cannot mock a final class) rather than requiring a live
 * database just to unit-test the sync/upsert logic that depends on it.
 *
 * @template TEntity of FrontAccountingLookup
 * @extends Select\Repository<TEntity>
 */
class FrontAccountingLookupRepository extends Select\Repository
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
    public function save(FrontAccountingLookup $lookup): void
    {
        $this->entityWriter->write([$lookup]);
    }

    public function findOneByKindAndExternalId(FrontAccountingLookupKind $kind, string $externalId): ?FrontAccountingLookup
    {
        /** @var ?FrontAccountingLookup */
        return $this->select()->where(['kind' => $kind->value, 'external_id' => $externalId])->fetchOne();
    }

    /**
     * Ordered by label for direct use as a settings-view dropdown's option
     * list.
     *
     * @return list<FrontAccountingLookup>
     */
    public function findByKind(FrontAccountingLookupKind $kind): array
    {
        /** @var list<FrontAccountingLookup> */
        return iterator_to_array(
            $this->select()->where(['kind' => $kind->value])->orderBy('label')->fetchAll(),
            false,
        );
    }
}
