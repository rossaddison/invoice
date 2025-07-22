<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Gentor
 *
 * @extends Select\Repository<TEntity>
 */
final class GeneratorRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get generators without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

        return $this->prepareDataReader($query);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|Gentor|null $generator): void
    {
        $this->entityWriter->write([$generator]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|Gentor|null $generator): void
    {
        $this->entityWriter->delete([$generator]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'small_singular_name', 'pre_entity_table'])
                ->withOrder(['small_singular_name' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoGentorQuery(string $id): ?Gentor
    {
        $query = $this
            ->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }
}
