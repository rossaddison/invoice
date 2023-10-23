<?php

declare(strict_types=1); 

namespace App\Invoice\Quote;

use App\Invoice\Entity\Quote;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Group\GroupRepository as GR;
use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Quote
 * @extends Select\Repository<TEntity>
 */
final class QuoteRepository extends Select\Repository
{
    private EntityWriter $entityWriter;
    private SR $sR;
    
    /**
     * @param Select<TEntity> $select     
     * @param EntityWriter $entityWriter
     * @param SR $sR
     * 
     */
    public function __construct(Select $select, EntityWriter $entityWriter, SR $sR)
    {
        $this->entityWriter = $entityWriter;
        $this->sR = $sR;
        parent::__construct($select);
    }
    
    /**
     * Get Quotes with filter
     *
     * @psalm-return EntityReader
     */
    public function findAllWithStatus(int $status_id) : EntityReader
    {
        if (($status_id) > 0) {
        $query = $this->select()
                ->load(['client','group','user'])
                ->where(['status_id' => $status_id]);  
         return $this->prepareDataReader($query);
       } else {
         return $this->findAllPreloaded();  
       }       
    }
    
    /**
     * Get quotes  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                ->load(['client','group','user']);
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
        // Provide the latest quote at the top of the list and order additionally according to status
        return Sort::only(['id', 'status'])->withOrder(['id' => 'desc', 'status' => 'asc']);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Quote|null $quote
     * @throws Throwable 
     * @return void
     */
    public function save(array|Quote|null $quote): void
    {
        $this->entityWriter->write([$quote]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Quote|null $quote
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Quote|null $quote): void
    {
        $this->entityWriter->delete([$quote]);
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
                ->withOrder(['id' => 'desc'])
        );
    }
    
    /**
     * @return null|Quote
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteUnLoadedquery(string $id):Quote|null    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @return null|Quote
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteLoadedquery(string $id):Quote|null    {
        $query = $this->select()
                      ->load(['client','group','user']) 
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string|null $quote_id
     * @param int $status_id
     * @return Quote|null
     */
    public function repoQuoteStatusquery(string|null $quote_id, int $status_id) : Quote|null {
        $query = $this->select()->where(['id' => $quote_id])
                                ->where(['status_id'=>$status_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @psalm-param 1 $status_id
     *
     * @param null|string $quote_id
     */
    public function repoQuoteStatuscount(string|null $quote_id, int $status_id): int {
        $count = $this->select()->where(['id' => $quote_id])
                                ->where(['status_id'=>$status_id])
                                ->count();
        return  $count;      
    }
    
    /**
     * 
     * @param string $url_key
     * @return Quote|null
     */    
    public function repoUrl_key_guest_loaded(string $url_key) : Quote|null {
        $query = $this->select()
                       ->load('client') 
                       ->where(['url_key' => $url_key]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $url_key
     * @return int
     */
    public function repoUrl_key_guest_count(string $url_key) : int {
        $count = $this->select()
                      ->where(['url_key' => $url_key])
                      ->count();
        return  $count;        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param array $user_client
     * @return int
     */
    public function repoClient_guest_count(string $quote_id, array $user_client = []) : int {
        $count = $this->select()
                      ->where(['id' => $quote_id])
                      ->andWhere(['client_id'=>['in'=> new Parameter($user_client)]])
                      ->count();
        return  $count;        
    }
    
    /**
     * 
     * @param int $status_id
     * @param array $user_client
     * @return EntityReader
     */
    public function repoGuest_Clients_Sent_Viewed_Approved_Rejected_Cancelled(int $status_id, array $user_client = []) : EntityReader {
        // Get specific statuses
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id'=>$status_id])
                    ->andWhere(['client_id'=>['in'=> new Parameter($user_client)]]);
            return $this->prepareDataReader($query);
       } else
       // Get all the quotes that are either sent, viewed, approved, or rejected, or cancelled
       {
            $query = $this->select()
                    // sent = 2, viewed = 3, approved = 4, rejected = 5, cancelled = 6
                    ->where(['client_id'=>['in'=> new Parameter($user_client)]])                      
                    ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4,5,6])]]);
            return $this->prepareDataReader($query);
       }
    }
        
    /**
     * 
     * @param SR $s
     * @return array
     */
    public function getStatuses(SR $s): array
    {
        return array(
            '0' => array(
                'label' => $s->trans('all'),
                'class' => 'all',
                'href' => 0
            ),
            '1' => array(
                'label' => $s->trans('draft'),
                'class' => 'draft',
                'href' => 1
            ),
            '2' => array(
                'label' => $s->trans('sent'),
                'class' => 'sent',
                'href' => 2
            ),
            '3' => array(
                'label' => $s->trans('viewed'),
                'class' => 'viewed',
                'href' => 3
            ),
            '4' => array(
                'label' => $s->trans('approved'),
                'class' => 'approved',
                'href' => 4
            ),
            '5' => array(
                'label' => $s->trans('rejected'),
                'class' => 'rejected',
                'href' => 5
            ),
            '6' => array(
                'label' => $s->trans('canceled'),
                'class' => 'canceled',
                'href' => 6
            )
        );       
    }
    
    public function getSpecificStatusArrayLabel(string $key) : string
    {
        $statuses_array = $this->getStatuses($this->sR);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }
    
    /**
     * @param string $group_id
     * @return mixed
     */
    public function get_quote_number(string $group_id, GR $gR) : mixed
    {   
        return $gR->generate_number((int)$group_id);
    }
    
    /**
     * @psalm-return Select<TEntity>
     */
    public function is_draft(): Select
    {
        $query = $this->select()->where(['status_id' => 1]);
        return $query;
    }
   
    /**
     * @psalm-return Select<TEntity>
     */
    public function is_sent(): Select
    {
        $query = $this->select()->where(['status_id' => 2]);
        return $query;
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_viewed(): Select
    {
        $query = $this->select()->where(['status_id' => 3]);
        return $query;
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_approved(): Select
    {
        $query = $this->select()->where(['status_id' => 4]);
        return $query;
    }
    
    /**
     * @psalm-return Select<TEntity>
     */
    public function is_rejected(): Select
    {
        $query = $this->select()->where(['status_id' => 5]);
        return $query;
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_canceled(): Select
    {
        $query = $this->select()->where(['status_id' => 6]);
        return $query;
    }

    /**
     * Used by guest; includes only sent and viewed
     *
     * @psalm-return Select<TEntity>
     */
    public function is_open(): Select
    {
        $query = $this->select()->where(['status_id'=>['in'=>new Parameter([2,3])]]);
        return $query;
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function guest_visible(): Select
    {
        $query = $this->select()->where(['status_id'=>['in'=>new Parameter([2,3,4,5])]]);
        return $query ;
    }

    /**
     * @param int $client_id
     *
     * @psalm-return Select<TEntity>
     */
    public function by_client(int $client_id): Select
    {
        $query = $this->select()
                      ->where(['client_id'=>$client_id]);
        return $query;
    }
    
    /**
     * @param $client_id
     * @param $status_id
     *
     * @psalm-return EntityReader
     */
    public function by_client_quote_status(int $client_id, int $status_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id]);
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param int $client_id
     * @param int $status_id
     * @return int
     */
    public function by_client_quote_status_count(int $client_id, int $status_id): int
    {
        $count = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id])
                      ->count();        
        return $count; 
    }

    /**
     * @param string $url_key
     *
     * @psalm-return Select<TEntity>
     */
    public function approve_or_reject_quote_by_key(string $url_key): Select{
        $query = $this->select()
                      ->where(['status_id'=>['in'=>new Parameter([2,3,4,5])]])
                      ->where(['url_key'=>$url_key]);
        return $query;
    }

    /**
     * 
     * @param string $id
     * @return Select
     */
    public function approve_or_reject_quote_by_id(string $id): Select{
        $query = $this->select()
                      ->where(['status_id'=>['in'=>new Parameter([2,3,4,5])]])
                      ->where(['id'=>$id]);
        return $query;
    }
    
    /**
     * @param null|string $quote_id
     */
    public function repoCount(string|null $quote_id) : int {
        $count = $this->select()
                      ->where(['id'=>$quote_id])
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @return int
     */
    public function repoCountAll() : int {
        $count = $this->select()
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $client_id
     * @return int
     */
    public function repoCountByClient(int $client_id) : int {
        $count = $this->select()
                      ->where(['client_id'=>$client_id])  
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $client_id
     * @return EntityReader
     */
    public function repoClient(int $client_id) : EntityReader { 
        $query = $this->select()
                      ->where(['client_id' => $client_id]); 
        return $this->prepareDataReader($query);
    }
}