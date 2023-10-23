<?php

declare(strict_types=1); 

namespace App\Invoice\DeliveryLocation;

use App\Invoice\Entity\DeliveryLocation;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of DeliveryLocation
 * @extends Select\Repository<TEntity>
 */
final class DeliveryLocationRepository extends Select\Repository
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
    
    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }
    
    /**
     * @throws Throwable
     */
    public function save(DeliveryLocation $del): void
    {
        $this->entityWriter->write([$del]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(DeliveryLocation $del): void
    {
        $this->entityWriter->delete([$del]);
    }
    
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
     * @return DeliveryLocation|null
     */
    public function repoDeliveryLocationquery(string $id): DeliveryLocation|null {
        $query = $this->select()->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * Get all delivery locations associated with a Client
     * @param string $client_id
     * @return EntityReader
     */
    public function repoClientquery(string $client_id): EntityReader { 
        $query = $this->select()
                      ->where(['client_id' => $client_id]);                                
        return $this->prepareDataReader($query);        
    }
    
    /**
     * @param string $client_id
     * @return int
     */
    public function repoClientCount(string $client_id) : int {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $query->count();
    }
    
    /**
     * @param string $inv_id
     * @return int
     */
    public function repoInvoiceCount(string $inv_id) : int {
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