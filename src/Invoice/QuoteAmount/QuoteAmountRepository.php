<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteAmount;

use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository as SR;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of QuoteAmount
 * @extends Select\Repository<TEntity>
 */
final class QuoteAmountRepository extends Select\Repository
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
     * Get quoteamounts  without filter
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
     * @param array|QuoteAmount|null $quoteamount
     * @throws Throwable 
     * @return void
     */
    public function save(array|QuoteAmount|null $quoteamount): void
    {
        $this->entityWriter->write([$quoteamount]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|QuoteAmount|null $quoteamount
     * @throws Throwable 
     * @return void
     */
    public function delete(array|QuoteAmount|null $quoteamount): void
    {
        $this->entityWriter->delete([$quoteamount]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @param string $quote_id
     */
    public function repoQuoteAmountCount(string $quote_id) : int {
        $count = $this->select()
                      ->where(['quote_id' => $quote_id])
                      ->count();
        return $count;
    }
    
    /**
     * @return null|QuoteAmount
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteAmountqueryTest(string $quote_id):QuoteAmount|null {
        $query = $this->select()
                      ->load('quote')
                      ->where(['quote_id' => $quote_id]);
        return  $query->fetchOne() ?: null;        
    }   
    
    /**
     * @param string $quote_id
     *
     * @return null|QuoteAmount
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteAmountquery(string $quote_id):QuoteAmount|null {
        $query = $this->select()
                      ->load('quote')
                      ->where(['quote_id' => $quote_id]);
        return  $query->fetchOne() ?: null;        
    }    
    
    /**
     * @param string $quote_id
     *
     * @return null|QuoteAmount
     *
     * @psalm-return TEntity|null
     */
    public function repoQuotequery(string $quote_id):QuoteAmount|null {
        $query = $this->select()
                      ->load('quote')
                      ->where(['quote_id' => $quote_id]);
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
                      ->load('quote')
                      ->where(['quote.status_id' => $key])
                      ->andWhere('quote.date_created', '>=' ,$datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('quote.date_created', '<=' ,$datehelper->date_from_mysql_without_style($range['upper']));                      
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
                      ->load('quote')                      
                      ->where(['quote.status_id' => $key])
                      ->andWhere('quote.date_created', '>=' ,$datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('quote.date_created', '<=' ,$datehelper->date_from_mysql_without_style($range['upper']))
                      ->count();
        return $query;
    }
    
    /**
     * 
     * @param QR $qR
     * @param SR $sR
     * @param string $period
     * @return array
     */
    public function get_status_totals(QR $qR, SR $sR, string $period) : array
    {
        $return = [];
        // $period eg. this-month, last-month derived from $sR->get_setting('invoice or quote_overview_period') 
        $range = $sR->range($period);
        /**
         * @var int $key
         * @var array $status 
         */
        foreach ($qR->getStatuses($sR) as $key => $status) {
            $status_specific_quotes = $this->repoStatusTotals($key, $range, $sR);
            /** @var float $total */
            $total = 0.00;
            /** @var QuoteAmount $quote_amount */
            foreach ($status_specific_quotes as $quote_amount) {
                $total = $total + (float)$quote_amount->getTotal();                
            }
            $return[$key] = [
                'quote_status_id' => $key,
                'class' => $status['class'],
                'label' => $status['label'],
                'href' => (string) $status['href'],
                'sum_total' => $total,
                'num_total' => $this->repoStatusTotals_Num_Total($key, $range, $sR) ?: 0
            ];
        }
        return $return;
    }
}