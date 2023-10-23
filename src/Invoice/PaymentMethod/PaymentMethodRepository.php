<?php

declare(strict_types=1); 

namespace App\Invoice\PaymentMethod;

use App\Invoice\Entity\PaymentMethod;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of PaymentMethod
 * @extends Select\Repository<TEntity>
 */
final class PaymentMethodRepository extends Select\Repository
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
     * Get paymentmethods  without filter
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
     * @param array|PaymentMethod|null $paymentmethod
     * @throws Throwable 
     * @return void
     */
    public function save(array|PaymentMethod|null $paymentmethod): void
    {
        $this->entityWriter->write([$paymentmethod]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|PaymentMethod|null $paymentmethod
     * @throws Throwable 
     * @return void
     */
    public function delete(array|PaymentMethod|null $paymentmethod): void
    {
        $this->entityWriter->delete([$paymentmethod]);
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
     * @param string $id
     *
     * @return null|PaymentMethod
     *
     * @psalm-return TEntity|null
     */
    public function repoPaymentMethodquery(string $id) : PaymentMethod|null    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $id
     * @return int
     */
    public function repoPaymentMethodqueryCount(string $id): int    {
        $count = $this->select()
                      ->where(['id' => $id])
                      ->count();
        return  $count;         
    }
    
    /**
     * 
     * @return int
     */
    public function count() : int {
        $count = $this->select()
                      ->count();
        return $count;
    }
}