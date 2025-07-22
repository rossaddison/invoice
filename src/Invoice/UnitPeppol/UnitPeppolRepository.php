<?php

declare(strict_types=1);

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\UnitPeppol;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of UnitPeppol
 *
 * @extends Select\Repository<TEntity>
 */
final class UnitPeppolRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get unitpeppols  without filter.
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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @psalm-param TEntity $unitpeppol
     *
     * @throws \Throwable
     */
    public function save(array|UnitPeppol|null $unitpeppol): void
    {
        $this->entityWriter->write([$unitpeppol]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|UnitPeppol|null $unitpeppol): void
    {
        $this->entityWriter->delete([$unitpeppol]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoUnitPeppolLoadedquery(string $id): ?UnitPeppol
    {
        $query = $this->select()
            ->load('unit')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoUnitCount(string $unit_id): int
    {
        $query = $this->select()
            ->where(['unit_id' => $unit_id]);

        return $query->count();
    }

    public function repoUnit(string $unit_id): ?UnitPeppol
    {
        $query = $this->select()
            ->where(['unit_id' => $unit_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }
}
