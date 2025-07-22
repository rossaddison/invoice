<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of GentorRelation
 *
 * @extends Select\Repository<TEntity>
 */
final class GeneratorRelationRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get generatorrelations without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

        return $this->prepareDataReader($query);
    }

    public function findRelations(string $id): EntityReader
    {
        $query = $this->select()->load('gentor')->where('gentor_id', $id);

        return $this->prepareDataReader($query);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function save(array|GentorRelation|null $generatorrelation): void
    {
        $this->entityWriter->write([$generatorrelation]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|GentorRelation|null $generatorrelation): void
    {
        $this->entityWriter->delete([$generatorrelation]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['lowercasename', 'camelcasename', 'gentor_id'])
                ->withOrder(['gentor_id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoGeneratorRelationquery(string $id): ?GentorRelation
    {
        $query = $this
            ->select()
            ->load('gentor')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoGeneratorquery(string $id): array
    {
        $query = $this
            ->select()
            ->where(['gentor_id' => $id]);

        return $query->fetchAll();
    }

    public function withLowercaseName(string $generatorrelation_lowercase_name): ?object
    {
        $query = $this
            ->select()
            ->where(['lowercasename' => $generatorrelation_lowercase_name]);

        return $query->fetchOne() ?: null;
    }
}
