<?php
declare(strict_types=1); 

namespace App\Invoice\InvAmount;

use App\Invoice\Entity\InvAmount;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as SR;
use Cycle\ORM\Select;
use Yiisoft\Data\Reader\Sort;

use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of InvAmount
 * @extends Select\Repository<TEntity>
 */
final class InvAmountRepository extends Select\Repository
{
private EntityWriter $entityWriter;
    
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
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
    
    /**
     * 
     * @param array|InvAmount|null $invamount
     * @return void
     */
    public function save(array|InvAmount|null $invamount): void
    {
        $this->entityWriter->write([$invamount]);
    }
    
    /**
     * 
     * @param array|InvAmount|null $invamount
     * @return void
     */
    public function delete(array|Invamount|null $invamount): void
    {
        $this->entityWriter->delete([$invamount]);
    }
    
    /**
     * 
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * 
     * @param int $inv_id
     * @return int
     */
    public function repoInvAmountCount(int $inv_id) : int {
        $count = $this->select()
                      ->where(['inv_id' => $inv_id])
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param string $inv_id
     * @return null|InvAmount
     */
    public function repoCreditInvoicequery(string $inv_id): null|InvAmount {
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv_id' => $inv_id])
                      ->andWhere(['sign' => -1]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param int $id
     * @return null|InvAmount
     */
    public function repoInvAmountquery(int $id): null|InvAmount {
        $query = $this->select()
                      ->load('inv')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }

    /**
     * @psalm-param 1|16|31 $interval_end
     * @psalm-param 15|30|365 $interval_start
     */
    public function AgingCount(int $interval_end, int $interval_start) : int {        
        $end = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.$interval_end.'D'))
                                              ->format('Y-m-d');
        $start = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.$interval_start. 'D'))
                                                ->format('Y-m-d');
        $count = $this->select()
                      ->load('inv')
                      ->where('inv.date_due','<=', $end)
                      ->andWhere('inv.date_due','>=', $start)
                      ->andWhere('balance','>',0)
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $interval_end
     * @param int $interval_start
     * @return EntityReader
     */
    public function Aging(int $interval_end, int $interval_start) : EntityReader {
        $end = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.$interval_end.'D'))
                                              ->format('Y-m-d');
        
        $start = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.$interval_start. 'D'))
                                                ->format('Y-m-d');
        $query = $this->select()
                      ->load('inv')
                      ->where('inv.date_due','<=', $end)
                      ->andWhere('inv.date_due','>=', $start)
                      ->andWhere('balance','>',0);
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param int $inv_id
     * @return InvAmount|null
     */
    public function repoInvquery(int $inv_id): InvAmount|null {
        $query = $this->select()
                      ->where(['inv_id' => $inv_id]);
        return  $query->fetchOne() ?: null;    
    } 
    
    /**
     * 
     * @param int $key
     * @param array $range
     * @param SR $sR
     * @return EntityReader
     */
    public function repoStatusTotals(int $key, array $range, SR $sR) : EntityReader {        
        $datehelper = new DateHelper($sR);
        /** 
         * @var \DateTimeImmutable $range['lower']
         * @var \DateTimeImmutable $range['upper']
         */
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv.status_id' => $key])
                      ->andWhere('inv.date_created', '>=' ,$datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('inv.date_created', '<=' ,$datehelper->date_from_mysql_without_style($range['upper']));
         return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param int $key
     * @param array $range
     * @param SR $sR
     * @return int
     */
    public function repoStatusTotals_Num_Total(int $key, array $range, SR $sR) : int {        
        $datehelper = new DateHelper($sR);
        /** 
         * @var \DateTimeImmutable $range['lower']
         * @var \DateTimeImmutable $range['upper']
         */
        $query = $this->select()
                      ->load('inv')                
                      ->where(['inv.status_id' => $key])
                      ->andWhere('inv.date_created', '>=' ,$datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('inv.date_created', '<=' ,$datehelper->date_from_mysql_without_style($range['upper']))
                      ->count();
        return $query;
    }
    
    /**
     * 
     * @param IR $iR
     * @param SR $sR
     * @param string $period
     * @return array
     */
    public function get_status_totals(IR $iR, SR $sR, string $period) : array
    {
        $return = [];
        $range = $sR->range($period);          
        // 1 => class: 'draft', href: 1},
        // 2 => class: 'sent', href: 2}, 
        // 3 => class: 'viewed', href: 3}, 
        // 4 => class: 'paid', href: 4}}
        
        /** @var array $status */
        foreach ($iR->getStatuses($sR) as $key => $status) {
            $status_specific_invoices = $this->repoStatusTotals((int)$key, $range, $sR);
            $total = 0.00;
             /** @var InvAmount $inv_amount */
            foreach ($status_specific_invoices as $inv_amount) {
               $total = $total + (float)$inv_amount->getTotal();
            }
            $return[$key] = [
                'inv_status_id' => $key,
                'class' => $status['class'],
                'label' => $status['label'],
                'href' => (string) $status['href'],
                'sum_total' => $total,
                'num_total' => $this->repoStatusTotals_Num_Total((int)$key, $range, $sR)
            ];
        }
        return $return;
    }
}