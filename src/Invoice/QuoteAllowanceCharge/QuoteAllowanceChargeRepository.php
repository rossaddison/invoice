<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAllowanceCharge;

use App\Invoice\Entity\QuoteAllowanceCharge;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of QuoteAllowanceCharge
 * @extends Select\Repository<TEntity>
 */
final class QuoteAllowanceChargeRepository extends Select\Repository
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
     * Get quoteallowancecharges  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge');
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
     * @param array|QuoteAllowanceCharge|null $quoteallowancecharge
     * @psalm-param TEntity $quoteallowancecharge
     * @throws Throwable
     */
    public function save(array|QuoteAllowanceCharge|null $quoteallowancecharge): void
    {
        $this->entityWriter->write([$quoteallowancecharge]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|QuoteAllowanceCharge|null $quoteallowancecharge

     * @throws Throwable
     */
    public function delete(array|QuoteAllowanceCharge|null $quoteallowancecharge): void
    {
        $this->entityWriter->delete([$quoteallowancecharge]);
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
     * @param string $id
     * @psalm-return TEntity|null
     * @return QuoteAllowanceCharge|null
     */
    public function repoQuoteAllowanceChargeLoadedquery(string $id): ?QuoteAllowanceCharge
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $quoteNumber
     * @psalm-return EntityReader
     */
    public function repoQuoteNumberQuery(string $quoteNumber): EntityReader
    {
        $query = $this->select()
                      ->load('quote')
                      ->where(['quote.number' => $quoteNumber]);
        return $this->prepareDataReader($query);
    }

    public function repoReasonCodeQuery(string $reasonCode): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['allowance_charge.reason_code' => $reasonCode]);
        return $this->prepareDataReader($query);
    }

    public function repoReasonQuery(string $reason): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['allowance_charge.reason' => $reason]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param string $quote_id
     * @psalm-return EntityReader
     */
    public function repoACQquery(string $quote_id): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['quote_id' => $quote_id]);
        return $this->prepareDataReader($query);
    }

    public function getPackHandleShipTotal(string $quote_id): array
    {
        $all = $this->repoACQquery($quote_id);
        $totalAmount = 0.00;
        $totalTax = 0.00;
        /**
         * @var QuoteAllowanceCharge $each
         */
        foreach ($all as $each) {
            $amount  = $each->getAmount();
            $tax = $each->getVatOrTax();
            if ($each->getAllowanceCharge()?->getIdentifier()) {
                $totalAmount += (float) $amount;
                $totalTax += (float) $tax;

            } else {
                $totalAmount -= (float) $amount;
                $totalTax -= (float) $tax;
            }
        }
        return ['totalAmount' => $totalAmount, 'totalTax' => $totalTax];
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

    public function repoACQCount(string $quote_id): int
    {
        $query = $this->select()
                      ->where(['quote_id' => $quote_id]);
        return $query->count();
    }
}
