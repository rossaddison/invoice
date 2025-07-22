<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAmount;

use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository as SR;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * @template TEntity of QuoteAmount
 *
 * @extends Select\Repository<TEntity>
 */
final class QuoteAmountRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get quoteamounts  without filter.
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
    public function save(array|QuoteAmount|null $quoteamount): void
    {
        $this->entityWriter->write([$quoteamount]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|QuoteAmount|null $quoteamount): void
    {
        $this->entityWriter->delete([$quoteamount]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoQuoteAmountCount(string $quote_id): int
    {
        return $this->select()
            ->where(['quote_id' => $quote_id])
            ->count();
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoQuoteAmountqueryTest(string $quote_id): ?QuoteAmount
    {
        $query = $this->select()
            ->load('quote')
            ->where(['quote_id' => $quote_id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoQuoteAmountquery(string $quote_id): ?QuoteAmount
    {
        $query = $this->select()
            ->load('quote')
            ->where(['quote_id' => $quote_id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoQuotequery(string $quote_id): ?QuoteAmount
    {
        $query = $this->select()
            ->load('quote')
            ->where(['quote_id' => $quote_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoStatusTotals(int $key, array $range, SR $sR): EntityReader
    {
        $datehelper = new DateHelper($sR);
        /**
         * @var \DateTimeImmutable $range['lower']
         * @var \DateTimeImmutable $range['upper']
         */
        $query = $this->select()
            ->load('quote')
            ->where(['quote.status_id' => $key])
            ->andWhere('quote.date_created', '>=', $datehelper->date_from_mysql_without_style($range['lower']))
            ->andWhere('quote.date_created', '<=', $datehelper->date_from_mysql_without_style($range['upper']));

        return $this->prepareDataReader($query);
    }

    public function repoStatusTotals_Num_Total(int $key, array $range, SR $sR): int
    {
        $datehelper = new DateHelper($sR);

        /*
         * @var \DateTimeImmutable $range['lower']
         * @var \DateTimeImmutable $range['upper']
         */
        return $this->select()
            ->load('quote')
            ->where(['quote.status_id' => $key])
            ->andWhere('quote.date_created', '>=', $datehelper->date_from_mysql_without_style($range['lower']))
            ->andWhere('quote.date_created', '<=', $datehelper->date_from_mysql_without_style($range['upper']))
            ->count();
    }

    public function get_status_totals(QR $qR, SR $sR, Translator $translator, string $period): array
    {
        $return = [];
        // $period eg. this-month, last-month derived from $sR->getSetting('invoice or quote_overview_period')
        $range = $sR->range($period);
        /**
         * @var int   $key
         * @var array $status
         */
        foreach ($qR->getStatuses($translator) as $key => $status) {
            $status_specific_quotes = $this->repoStatusTotals($key, $range, $sR);
            /** @var float $total */
            $total = 0.00;
            /** @var QuoteAmount $quote_amount */
            foreach ($status_specific_quotes as $quote_amount) {
                $total = $total + (float) $quote_amount->getTotal();
            }
            $return[$key] = [
                'quote_status_id' => $key,
                'class'           => $status['class'],
                'label'           => $status['label'],
                'href'            => (string) $status['href'],
                'sum_total'       => $total,
                'num_total'       => $this->repoStatusTotals_Num_Total($key, $range, $sR) ?: 0,
            ];
        }

        return $return;
    }
}
