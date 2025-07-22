<?php

declare(strict_types=1);

namespace App\Invoice\FromDropDown;

use App\Invoice\Entity\FromDropDown;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of FromDropDown
 *
 * @extends Select\Repository<TEntity>
 */
final class FromDropDownRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get froms  without filter.
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
     * @psalm-param TEntity $from
     *
     * @throws \Throwable
     */
    public function save(array|FromDropDown|null $from): void
    {
        $this->entityWriter->write([$from]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|FromDropDown|null $from): void
    {
        $this->entityWriter->delete([$from]);
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
    public function repoFromDropDownLoadedquery(string $id): ?FromDropDown
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Return the first available default.
     *
     * @psalm-return TEntity|null
     */
    public function getDefault(): ?FromDropDown
    {
        $query = $this->select()
            ->where(['default_email' => 1])
            ->andWhere(['include' => 1])
            ->limit(1);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }
}
