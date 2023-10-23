<?php

declare(strict_types=1); 

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Delivery
 * @extends Select\Repository<TEntity>
 */
final class DeliveryRepository extends Select\Repository
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
     * Get deliverys  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();        return $this->prepareDataReader($query);
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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Delivery|null $delivery
     * @psalm-param TEntity $delivery
     * @throws Throwable 
     * @return void
     */
    public function save(array|Delivery|null $delivery): void
    {
        $this->entityWriter->write([$delivery]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Delivery|null $delivery
  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Delivery|null $delivery): void
    {
        $this->entityWriter->delete([$delivery]);
    }
    
    /**
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
     * @param string $id
     * @psalm-return TEntity|null
     * @return Delivery|null
     */
    public function repoDeliveryquery(string $id): Delivery|null
    {
        $query = $this->select()
                      ->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @param string $inv_id
     * @return Delivery|null
     */
    public function repoPartyquery(string $inv_id): Delivery|null
    {
        $query = $this->select()
                      ->where(['inv_id' =>$inv_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @param string $inv_id
     * @return Delivery|null
     */
    public function repoInvoicequery(string $inv_id): Delivery|null
    {
        $query = $this->select()
                      ->where(['inv_id' =>$inv_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $inv_id
     * @return int
     */
    public function repoCountInvoice(string $inv_id) : int {
        $query = $this->select()
                      ->where(['inv_id' => $inv_id]);
        return $query->count();
    }   
    
    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id) : int {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }   
}