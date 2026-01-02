<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Invoice\Entity\QuoteItemAllowanceCharge;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of QuoteItemAllowanceCharge
 * @extends Select\Repository<TEntity>
 */
final class QuoteItemAllowanceChargeRepository extends Select\Repository
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
     * Get acqis  without filter
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

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|QuoteItemAllowanceCharge|null $acqi
     * @psalm-param TEntity $acqi
     * @throws Throwable
     */
    public function save(array|QuoteItemAllowanceCharge|null $acqi): void
    {
        $this->entityWriter->write([$acqi]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|QuoteItemAllowanceCharge|null $acqi

     * @throws Throwable
     */
    public function delete(array|QuoteItemAllowanceCharge|null $acqi): void
    {
        $this->entityWriter->delete([$acqi]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * All item allowances or charges for this invoice
     * @param string $quote_id
     * @return EntityReader
     */
    public function repoACQquery(string $quote_id): EntityReader
    {
        $query = $this->select()
                      ->where(['quote_id' => $quote_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return QuoteItemAllowanceCharge|null
     */
    public function repoQuoteItemAllowanceChargequery(string $id): ?QuoteItemAllowanceCharge
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id): int
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }

    /**
     * @param string $quote_id
     * @return int
     */
    public function repoQuoteCount(string $quote_id): int
    {
        $query = $this->select()
                      ->where(['quote_id' => $quote_id]);
        return $query->count();
    }

    /**
     * @param string $quote_item_id
     * @return int
     */
    public function repoQuoteItemCount(string $quote_item_id): int
    {
        $query = $this->select()
                      ->where(['quote_item_id' => $quote_item_id]);
        return $query->count();
    }

    /**
     * All allowances and charges for this quote item
     * @param string $quote_item_id
     * @return EntityReader
     */
    public function repoQuoteItemquery(string $quote_item_id): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['quote_item_id' => $quote_item_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * Used in QuoteController function multiplecopy
     * @return QuoteItemAllowanceCharge|null
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteItemOriginalquery(string $quote_item_id): ?QuoteItemAllowanceCharge
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['quote_item_id' => $quote_item_id]);
        return  $query->fetchOne() ?: null;
    }
}
