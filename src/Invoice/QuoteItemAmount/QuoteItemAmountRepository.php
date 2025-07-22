<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAmount;

use App\Invoice\Entity\QuoteItemAmount;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of QuoteItemAmount
 *
 * @extends Select\Repository<TEntity>
 */
final class QuoteItemAmountRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get quoteitemamounts  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('quote_item');

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
    public function save(array|QuoteItemAmount|null $quoteitemamount): void
    {
        $this->entityWriter->write([$quoteitemamount]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|QuoteItemAmount|null $quoteitemamount): void
    {
        $this->entityWriter->delete([$quoteitemamount]);
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
    public function repoQuoteItemAmountquery(int $quote_item_id): ?QuoteItemAmount
    {
        $query = $this->select()
            ->load(['quote_item'])
            ->where(['quote_item_id' => $quote_item_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $quote_item_id): int
    {
        $query = $this->select()
            ->where(['quote_item_id' => $quote_item_id]);

        return $query->count();
    }
}
