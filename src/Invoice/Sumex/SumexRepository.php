<?php

declare(strict_types=1);

namespace App\Invoice\Sumex;

use App\Invoice\Entity\Sumex;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Sumex
 * @extends Select\Repository<TEntity>
 */
final class SumexRepository extends Select\Repository
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
     * Get sumexs  without filter
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
     * @param array|Sumex|null $sumex
     * @throws Throwable
     */
    public function save(array|Sumex|null $sumex): void
    {
        $this->entityWriter->write([$sumex]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Sumex|null $sumex
     * @throws Throwable
     */
    public function delete(array|Sumex|null $sumex): void
    {
        $this->entityWriter->delete([$sumex]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }

    /**
     * @return Sumex|null
     *
     * @psalm-return TEntity|null
     */
    public function repoSumexquery(string $id): Sumex|null
    {
        $query = $this->select()->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @return Sumex|null
     *
     * @psalm-return TEntity|null
     */
    public function repoSumexInvoicequery(string $invoice): Sumex|null
    {
        $query = $this->select()->where(['invoice' => $invoice]);
        return  $query->fetchOne() ?: null;
    }
}
