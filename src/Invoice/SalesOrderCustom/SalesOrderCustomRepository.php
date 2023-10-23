<?php

declare(strict_types=1); 

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderCustom
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderCustomRepository extends Select\Repository
{
private EntityWriter $entityWriter;
    
    /**
     * 
     * @param Select<TEntity> $select     
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * Get client sales order customs  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('custom_field')
                      ->load('quote');
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
     * @param array|SalesOrderCustom|null $quotecustom
     * @throws Throwable 
     * @return void
     */
    public function save(array|SalesOrderCustom|null $quotecustom): void
    {
        $this->entityWriter->write([$quotecustom]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderCustom|null $so_custom
     * @throws Throwable 
     * @return void
     */
    public function delete(array|SalesOrderCustom|null $so_custom): void
    {
        $this->entityWriter->delete([$so_custom]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoSalesOrderCustomquery(string $id): SalesOrderCustom|null {
        $query = $this->select()
                      ->load('custom_field')
                      ->load('customsalesorder')
                      ->where(['id'=>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    
    public function repoFormValuequery(string $so_id, string $custom_field_id): SalesOrderCustom|null {
        $query = $this->select()
                      ->where(['so_id' =>$so_id])
                      ->andWhere(['custom_field_id' =>$custom_field_id]);
        return  $query->fetchOne();        
    }
    
    public function repoSalesOrderCustomCount(string $so_id, string $custom_field_id) : int {
        $query = $this->select()
                      ->where(['so_id' =>$so_id])
                      ->andWhere(['custom_field_id' =>$custom_field_id]);
        return $query->count();
    } 
    
    public function repoSalesOrderCount(string $so_id) : int {
        $query = $this->select()
                      ->where(['so_id' =>$so_id]);
        return $query->count();
    }   
    
    /**
     * Get all fields that have been setup for a particular sales order
     * 
     * @psalm-return EntityReader
     */
    public function repoFields(string $so_id): EntityReader
    {
        $query = $this->select()
                      ->where(['so_id'=>$so_id]);                
        return $this->prepareDataReader($query);
    }
}