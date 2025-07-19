<?php

declare(strict_types=1);

namespace App\Invoice\Company;

use App\Invoice\Entity\Company;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Company
 * @extends Select\Repository<TEntity>
 */
final class CompanyRepository extends Select\Repository
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
     * Get companys  without filter
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
     * @param array|Company|null $company
     * @throws Throwable
     */
    public function save(array|Company|null $company): void
    {
        $this->entityWriter->write([$company]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Company|null $company
     * @throws Throwable
     */
    public function delete(array|Company|null $company): void
    {
        $this->entityWriter->delete([$company]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @return Company|null
     *
     * @psalm-return TEntity|null
     */
    public function repoCompanyquery(string $id): Company|null
    {
        $query = $this->select()->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @return Company|null
     *
     * @psalm-return TEntity|null
     */
    public function repoCompanyActivequery(): Company|null
    {
        $query = $this->select()->where(['current' => '1']);
        return  $query->fetchOne() ?: null;
    }
}
