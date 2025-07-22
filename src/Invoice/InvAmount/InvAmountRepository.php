<?php

declare(strict_types=1);

namespace App\Invoice\InvAmount;

use App\Invoice\Entity\InvAmount;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as SR;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * @template TEntity of InvAmount
 *
 * @extends Select\Repository<TEntity>
 */
final class InvAmountRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
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
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }

    public function save(array|InvAmount|null $invamount): void
    {
        $this->entityWriter->write([$invamount]);
    }

    public function delete(array|InvAmount|null $invamount): void
    {
        $this->entityWriter->delete([$invamount]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoInvAmountCount(int $inv_id): int
    {
        return $this->select()
            ->where(['inv_id' => $inv_id])
            ->count();
    }

    public function repoCreditInvoicequery(string $inv_id): ?InvAmount
    {
        $query = $this->select()
            ->load('inv')
            ->where(['inv_id' => $inv_id])
            ->andWhere(['sign' => -1]);

        return $query->fetchOne() ?: null;
    }

    public function repoInvAmountquery(int $id): ?InvAmount
    {
        $query = $this->select()
            ->load('inv')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * @psalm-param 1|16|31 $interval_end
     * @psalm-param 15|30|365 $interval_start
     */
    public function AgingCount(int $interval_end, int $interval_start): int
    {
        $end = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.(string) $interval_end.'D'))
            ->format('Y-m-d');
        $start = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.(string) $interval_start.'D'))
            ->format('Y-m-d');

        return $this->select()
            ->load('inv')
            ->where('inv.date_due', '<=', $end)
            ->andWhere('inv.date_due', '>=', $start)
            ->andWhere('balance', '>', 0)
            ->count();
    }

    public function Aging(int $interval_end, int $interval_start): EntityReader
    {
        $end = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.(string) $interval_end.'D'))
            ->format('Y-m-d');

        $start = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.(string) $interval_start.'D'))
            ->format('Y-m-d');
        $query = $this->select()
            ->load('inv')
            ->where('inv.date_due', '<=', $end)
            ->andWhere('inv.date_due', '>=', $start)
            ->andWhere('balance', '>', 0);

        return $this->prepareDataReader($query);
    }

    public function repoInvquery(int $inv_id): ?InvAmount
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

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
            ->load('inv')
            ->where(['inv.status_id' => $key])
            ->andWhere('inv.date_created', '>=', $datehelper->date_from_mysql_without_style($range['lower']))
            ->andWhere('inv.date_created', '<=', $datehelper->date_from_mysql_without_style($range['upper']));

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
            ->load('inv')
            ->where(['inv.status_id' => $key])
            ->andWhere('inv.date_created', '>=', $datehelper->date_from_mysql_without_style($range['lower']))
            ->andWhere('inv.date_created', '<=', $datehelper->date_from_mysql_without_style($range['upper']))
            ->count();
    }

    public function get_status_totals(IR $iR, SR $sR, Translator $translator, string $period): array
    {
        $return = [];
        $range  = $sR->range($period);
        // 1 => class: 'draft', href: 1},
        // 2 => class: 'sent', href: 2},
        // 3 => class: 'viewed', href: 3},
        // 4 => class: 'paid', href: 4}}

        /** @var array $status */
        foreach ($iR->getStatuses($translator) as $key => $status) {
            $status_specific_invoices = $this->repoStatusTotals((int) $key, $range, $sR);
            $total                    = 0.00;
            /** @var InvAmount $inv_amount */
            foreach ($status_specific_invoices as $inv_amount) {
                $total = $total + (float) $inv_amount->getTotal();
            }
            $return[$key] = [
                'inv_status_id' => $key,
                'class'         => $status['class'],
                'label'         => $status['label'],
                'href'          => (string) $status['href'],
                'sum_total'     => $total,
                'num_total'     => $this->repoStatusTotals_Num_Total((int) $key, $range, $sR),
            ];
        }

        return $return;
    }
}
