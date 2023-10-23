<?php

declare(strict_types=1); 

namespace App\Invoice\Payment;

use App\Invoice\Entity\Payment;
// Cycle
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
// Yiisoft
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

use Throwable;

/**
 * @template TEntity of Payment
 * @extends Select\Repository<TEntity>
 */
final class PaymentRepository extends Select\Repository
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
     * Get payments  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('inv')
                                ->load('payment_method');
        return $this->prepareDataReader($query);
    }
    
    // Find all payments associated with a user's clients ie. their client list / client_id_array
    
    /**
     * Get payments  with filter
     *
     * @param array $client_id_array
     *
     * @psalm-return EntityReader
     */
    public function findOneUserManyClientsPayments(array $client_id_array): EntityReader
    {
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv.client_id'=>['in'=> new Parameter($client_id_array)]]);        
        return   $this->prepareDataReader($query);
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
     * @param array|Payment|null $payment
     * @throws Throwable 
     * @return void
     */
    public function save(array|Payment|null $payment): void
    {
        $this->entityWriter->write([$payment]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Payment|null $payment
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Payment|null $payment): void
    {
        $this->entityWriter->delete([$payment]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc'])
        );
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function repoPaymentInvLoadedAll(int $list_limit) {
        $query = $this->select()
                      ->load('inv')
                      ->limit($list_limit);
        return  $this->prepareDataReader($query);           
    }
    
    /**
     * @return null|Payment
     *
     * @psalm-return TEntity|null
     */
    public function repoPaymentquery(string $id): Payment|null    {
        $query = $this->select()
                      ->load('inv')
                      ->load('payment_method')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function repoPaymentLoaded_from_to_count(string $from, string $to): int {
        $count = $this->select()
                      ->load('inv')
                      ->load('payment_method')
                      ->where('payment_date','>=',$from)
                      ->andWhere('payment_date','<=',$to)
                      ->count();
        return $count;        
    }
    
    public function repoPaymentLoaded_from_to(string $from, string $to): EntityReader {
        $query = $this->select()
                      ->load('inv')
                      ->load('payment_method')
                      ->where('payment_date','>=',$from)
                      ->andWhere('payment_date','<=',$to);
        return $this->prepareDataReader($query);        
    }
    
    /**
     * Get payments  without filter
     *
     * @psalm-return EntityReader
     */
    public function repoInvquery(string $inv_id): EntityReader {
        $query = $this->select()
                      ->where(['inv_id' => $inv_id]);
        return $this->prepareDataReader($query);   
    }
    
    /**
     * 
     * @param string $inv_id
     * @return int
     */
    public function repoCount(string $inv_id) : int {
        $count = $this->select()
                      ->where(['inv_id' => $inv_id])                                
                      ->count();
        return $count; 
    }
    
}