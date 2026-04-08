<?php

declare(strict_types=1);

namespace App\Invoice\Qa;

use App\Invoice\Entity\Qa;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Qa
 * @extends Select\Repository<TEntity>
 */
final class QaRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select,
        private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Qa|null $qa
     * @throws Throwable
     */
    public function save(array|Qa|null $qa): void
    {
        $this->entityWriter->write([$qa]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Qa|null $qa
     * @throws Throwable
     */
    public function delete(array|Qa|null $qa): void
    {
        $this->entityWriter->delete([$qa]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'question'])
                ->withOrder(['question' => 'asc']),
        );
    }

    /**
     * Get frequently asked questions without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * @return Qa|null
     *
     * @psalm-return TEntity|null
     */
    public function repoQaQuery(string $id): ?Qa
    {
        $query = $this
            ->select()
            ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    public function findAllActive(): EntityReader
    {
        $query = $this->select()
                      ->where(['active' => '1']);
        return $this->prepareDataReader($query);
    }
}