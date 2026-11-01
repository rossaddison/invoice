<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Infrastructure\Persistence\Gentor\Gentor;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Gentor
 * @extends Select\Repository<TEntity>
 */
final class GeneratorRepository extends Select\Repository
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
     * Get generators without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Gentor|null $generator
     * @throws Throwable
     */
    public function save(array|Gentor|null $generator): void
    {
        $this->entityWriter->write([$generator]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Gentor|null $generator
     * @throws Throwable
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
     * @return Gentor|null
     *
     * @psalm-return TEntity|null
     */
    public function repoGentorQuery(int $id): ?Gentor
    {
        $query = $this
            ->select()
            ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * Find the Gentor record for a relation's target entity by its class
     * name. GentorRelation only stores the target's name as free text
     * (camelcasename) -- there is no real foreign key to the target's own
     * Gentor record -- so this is a best-effort name match, used by the
     * Entity template to resolve the target's real table/primary key
     * column instead of guessing them from Cycle's default relation
     * key-naming convention. Returns null if no Gentor record was ever
     * created for that entity, or its name doesn't match exactly.
     *
     * @psalm-return TEntity|null
     */
    public function findByCamelcaseCapitalName(string $camelcaseCapitalName): ?Gentor
    {
        $query = $this
            ->select()
            ->where(['camelcase_capital_name' => $camelcaseCapitalName]);
        return $query->fetchOne() ?: null;
    }
}
