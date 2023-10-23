<?php

declare(strict_types=1); 

namespace App\Invoice\PaymentCustom;

use App\Invoice\Entity\PaymentCustom;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of PaymentCustom
 * @extends Select\Repository<TEntity>
 */
final class PaymentCustomRepository extends Select\Repository
{
private EntityWriter $entityWriter;
    
     /**
     * @param Select<TEntity> $select
     * 
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * Get paymentcustoms  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('payment')->load('custom_field');
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
     * @param array|PaymentCustom|null $paymentcustom
     * @throws Throwable 
     * @return void
     */
    public function save(array|PaymentCustom|null $paymentcustom): void
    {
        $this->entityWriter->write([$paymentcustom]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|PaymentCustom|null $paymentcustom
     * @throws Throwable 
     * @return void
     */
    public function delete(array|PaymentCustom|null $paymentcustom): void
    {
        $this->entityWriter->delete([$paymentcustom]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|PaymentCustom
     *
     * @psalm-return TEntity|null
     */
    public function repoPaymentCustomquery(string $id): PaymentCustom|null    {
        $query = $this->select()->load('payment')
            ->load('custom_field')
            ->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @return null|PaymentCustom
     *
     * @psalm-return TEntity|null
     */
    public function repoFormValuequery(string $payment_id, string $custom_field_id): PaymentCustom|null {
        $query = $this->select()->where(['payment_id' =>$payment_id])
                                ->andWhere(['custom_field_id' =>$custom_field_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function repoPaymentCustomCount(string $payment_id, string $custom_field_id) : int {
        $query = $this->select()->where(['payment_id' =>$payment_id])
                                ->andWhere(['custom_field_id' =>$custom_field_id]);
        return $query->count();
    } 
    
    public function repoPaymentCount(string $payment_id) : int {
        $query = $this->select()->where(['payment_id' =>$payment_id]);
        return $query->count();
    }   
    
    /**
     * Get all fields that have been setup for a particular payment
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $payment_id): EntityReader
    {
        $query = $this->select()->where(['payment_id'=>$payment_id]);                
        return $this->prepareDataReader($query);
    }
}