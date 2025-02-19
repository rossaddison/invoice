<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAmount;

use App\Invoice\Entity\SalesOrderAmount;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderAmount
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderAmountRepository extends Select\Repository
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
     * Get poamounts  without filter
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
     * @param array|SalesOrderAmount|null $soamount
     * @throws Throwable
     */
    public function save(array|SalesOrderAmount|null $soamount): void
    {
        $this->entityWriter->write([$soamount]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderAmount|null $soamount
     * @throws Throwable
     */
    public function delete(array|SalesOrderAmount|null $soamount): void
    {
        $this->entityWriter->delete([$soamount]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }

    /**
     * @param string $so_id
     */
    public function repoSalesOrderAmountCount(string $so_id): int
    {
        return $this->select()
                      ->where(['so_id' => $so_id])
                      ->count();
    }

    /**
     * @return SalesOrderAmount|null
     *
     * @psalm-return TEntity|null
     */
    public function repoSalesOrderAmountqueryTest(string $so_id): SalesOrderAmount|null
    {
        $query = $this->select()
                      ->load('so')
                      ->where(['so_id' => $so_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $so_id
     *
     * @return SalesOrderAmount|null
     *
     * @psalm-return TEntity|null
     */
    public function repoSoquery(string $so_id): SalesOrderAmount|null
    {
        $query = $this->select()
                      ->load('so')
                      ->where(['so_id' => $so_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $so_id
     *
     * @return SalesOrderAmount|null
     *
     * @psalm-return TEntity|null
     */
    public function repoSalesOrderquery(string $so_id): SalesOrderAmount|null
    {
        $query = $this->select()
                      ->load('so')
                      ->where(['so_id' => $so_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $key
     * @param array $range
     * @param SR $sR
     * @return EntityReader
     */
    public function repoStatusTotals(int $key, array $range, SR $sR): EntityReader
    {
        $datehelper = new DateHelper($sR);
        /**
         * @var \DateTimeImmutable $range['lower']
         * @var \DateTimeImmutable $range['upper']
         */
        $query = $this->select()
                      ->where(['so.status_id' => $key])
                      ->andWhere('so.date_created', '>=', $datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('so.date_created', '<=', $datehelper->date_from_mysql_without_style($range['upper']));
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $key
     * @param array $range
     * @param SR $sR
     * @return int
     */
    public function repoStatusTotals_Num_Total(int $key, array $range, SR $sR): int
    {
        $datehelper = new DateHelper($sR);
        /**
         * @var \DateTimeImmutable $range['lower']
         * @var \DateTimeImmutable $range['upper']
         */
        return $this->select()
                      ->load('so')
                      ->where(['so.status_id' => $key])
                      ->andWhere('so.date_created', '>=', $datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('so.date_created', '<=', $datehelper->date_from_mysql_without_style($range['upper']))
                      ->count();
    }

    /**
     * @param SOR $soR
     * @param SR $sR
     * @param Translator $translator
     * @param string $period
     * @return array
     */
    public function get_status_totals(SOR $soR, SR $sR, Translator $translator, string $period): array
    {
        $return = [];
        // $period eg. this-month, last-month derived from $sR->getSetting('invoice or so_overview_period')
        $range = $sR->range($period);
        /**
         * @var int $key
         * @var array $status
         */
        foreach ($soR->getStatuses($translator) as $key => $status) {
            $status_specific_sos = $this->repoStatusTotals($key, $range, $sR);
            /** @var float $total */
            $total = 0.00;
            /** @var SalesOrderAmount $so_amount */
            foreach ($status_specific_sos as $so_amount) {
                $total = $total + (float)$so_amount->getTotal();
            }
            $return[$key] = [
                'so_status_id' => $key,
                'class' => $status['class'],
                'label' => $status['label'],
                'href' => (string) $status['href'],
                'sum_total' => $total,
                'num_total' => $this->repoStatusTotals_Num_Total($key, $range, $sR) ?: 0,
            ];
        }
        return $return;
    }
}
