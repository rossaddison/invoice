<?php

declare(strict_types=1);

namespace App\Invoice\ItemLookup;

use App\Invoice\Entity\ItemLookup;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of ItemLookup
 *
 * @extends Select\Repository<TEntity>
 */
final class ItemLookupRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get itemlookups  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     */
    public function save(array|ItemLookup|null $itemlookup): void
    {
        $this->entityWriter->write([$itemlookup]);
    }

    public function delete(array|ItemLookup|null $itemlookup): void
    {
        $this->entityWriter->delete([$itemlookup]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @return TEntity|null
     */
    public function repoItemLookupquery(string $id): ?ItemLookup
    {
        $query = $this->select()->where(['id' => $id]);

        return $query->fetchOne();
    }
}
