<?php

declare(strict_types=1);

namespace App\Invoice\ItemLookup;

use App\Invoice\Entity\ItemLookup;
use Cycle\ORM\Select;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of ItemLookup
 * @extends Select\Repository<TEntity>
 */
final class ItemLookupRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get itemlookups  without filter
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|ItemLookup|null $itemlookup
     */
    public function save(array|ItemLookup|null $itemlookup): void
    {
        $this->entityWriter->write([$itemlookup]);
    }

    /**
     * @param array|ItemLookup|null $itemlookup
     */
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
     * @param string $id
     * @return TEntity|null
     */
    public function repoItemLookupquery(string $id): ?ItemLookup
    {
        $query = $this->select()->where(['id' => $id]);
        return  $query->fetchOne();
    }
}
