<?php

declare(strict_types=1);

namespace App\Invoice\QuoteCustom;

use App\Invoice\Entity\QuoteCustom;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of QuoteCustom
 *
 * @extends Select\Repository<TEntity>
 */
final class QuoteCustomRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get quotecustoms  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('custom_field')
            ->load('quote');

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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|QuoteCustom|null $quotecustom): void
    {
        $this->entityWriter->write([$quotecustom]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|QuoteCustom|null $quotecustom): void
    {
        $this->entityWriter->delete([$quotecustom]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoQuoteCustomquery(string $id): ?QuoteCustom
    {
        $query = $this->select()
            ->load('custom_field')
            ->load('quote')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoFormValuequery(string $quote_id, string $custom_field_id): ?QuoteCustom
    {
        $query = $this->select()
            ->where(['quote_id' => $quote_id])
            ->andWhere(['custom_field_id' => $custom_field_id]);

        return $query->fetchOne();
    }

    public function repoQuoteCustomCount(string $quote_id, string $custom_field_id): int
    {
        $query = $this->select()
            ->where(['quote_id' => $quote_id])
            ->andWhere(['custom_field_id' => $custom_field_id]);

        return $query->count();
    }

    public function repoQuoteCount(string $quote_id): int
    {
        $query = $this->select()
            ->where(['quote_id' => $quote_id]);

        return $query->count();
    }

    /**
     * Get all fields that have been setup for a particular quote.
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $quote_id): EntityReader
    {
        $query = $this->select()
            ->where(['quote_id' => $quote_id]);

        return $this->prepareDataReader($query);
    }
}
