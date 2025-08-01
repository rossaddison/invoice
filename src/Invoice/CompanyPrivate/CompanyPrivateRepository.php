<?php

declare(strict_types=1);

namespace App\Invoice\CompanyPrivate;

use App\Invoice\Entity\CompanyPrivate;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of CompanyPrivate
 * @extends Select\Repository<TEntity>
 */
final class CompanyPrivateRepository extends Select\Repository
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
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('company');
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
    * @param array|CompanyPrivate|null $companyprivate
    * @throws Throwable
    */
    public function save(array|CompanyPrivate|null $companyprivate): void
    {
        $this->entityWriter->write([$companyprivate]);
    }

    /**
    * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
    * @param array|CompanyPrivate|null $companyprivate
    * @throws Throwable
    */
    public function delete(array|CompanyPrivate|null $companyprivate): void
    {
        $this->entityWriter->delete([$companyprivate]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @return CompanyPrivate|null
     *
     * @psalm-return TEntity|null
     */
    public function repoCompanyPrivatequery(string $id): CompanyPrivate|null
    {
        $query = $this->select()->load('company')->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @return CompanyPrivate|null
     *
     * @psalm-return TEntity|null
     */
    public function repoCompanyquery(string $id): CompanyPrivate|null
    {
        $query = $this->select()
                      ->load('company')
                      ->where(['company_id' => $id]);
        return  $query->fetchOne() ?: null;
    }
}
