<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use App\Invoice\Entity\CustomField;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of CustomField
 *
 * @extends Select\Repository<TEntity>
 */
final class CustomFieldRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get customfields  without filter.
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
     * @throws \Throwable
     */
    public function save(array|CustomField|null $customfield): void
    {
        $this->entityWriter->write([$customfield]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|CustomField|null $customfield): void
    {
        $this->entityWriter->delete([$customfield]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoCustomFieldquery(string $id): ?CustomField
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Get customfields  with table filter.
     *
     * @psalm-return EntityReader
     */

    // Retrieve all custom fields built for the entity/tabel eg. quote_custom
    public function repoTablequery(string $table): EntityReader
    {
        $query = $this->select()
            ->where(['table' => $table]);

        return $this->prepareDataReader($query);
    }

    public function repoTableCountquery(string $table): int
    {
        return $this->select()
            ->where(['table' => $table])
            ->count();
    }

    public function repoTableAndLabelCountquery(string $table, string $label): int
    {
        return $this->select()
            ->where(['table' => $table])
            ->andWhere(['label' => $label])
            ->count();
    }
}
