<?php

declare(strict_types=1); 

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Entity\SalesOrderItem;
use Cycle\ORM\Select;
use Cycle\Database\Injection\Parameter;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderItem
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderItemRepository extends Select\Repository
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
     * Get salesorderitems  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load(['tax_rate','product','sales_order']);
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
     * @param array|SalesOrderItem|null $salesorderitem
     * @throws Throwable 
     * @return void
     */
    public function save(array|SalesOrderItem|null $salesorderitem): void
    {
        $this->entityWriter->write([$salesorderitem]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderItem|null $salesorderitem
     * @throws Throwable 
     * @return void
     */
    public function delete(array|SalesOrderItem|null $salesorderitem): void
    {
        $this->entityWriter->delete([$salesorderitem]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|SalesOrderItem
     *
     * @psalm-return TEntity|null
     */
    public function repoSalesOrderItemquery(string $id):SalesOrderItem|null    {
        $query = $this->select()->load(['tax_rate','product','sales_order'])
                                ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }    
    
    /**
     * Get all items id's that belong to a specific salesorder
     *
     * @psalm-return EntityReader
     */
    public function repoSalesOrderItemIdquery(string $salesorder_id):  EntityReader    {
        $query = $this->select()
                      ->load(['tax_rate','product','sales_order'])
                      ->where(['sales_order_id' => $salesorder_id]);
        return $this->prepareDataReader($query); 
    }
    
    /**
     * Get all items belonging to salesorder
     *
     * @psalm-return EntityReader
     */
    public function repoSalesOrderquery(string $salesorder_id): EntityReader    {
        $query = $this->select()
                      ->load(['tax_rate','product','sales_order'])
                      ->where(['sales_order_id' => $salesorder_id]);                                
        return $this->prepareDataReader($query);        
    }
    
    public function repoCount(string $salesorder_id) : int {
        $count = $this->select()
                      ->where(['sales_order_id' => $salesorder_id])                                
                      ->count();
        return $count; 
    }
    
    public function repoSalesOrderItemCount(string $id) : int {
        $count = $this->select()
                      ->where(['id' => $id])                                
                      ->count();
        return $count; 
    }
    
    /**
     * Get selection of salesorder items from all salesorder_items
     * 
     * @param array $item_ids
     * @return EntityReader
     */
     
    public function findinSalesOrderItems(array $item_ids) : EntityReader {
        $query = $this->select()->where(['id'=>['in'=> new Parameter($item_ids)]]);
        return $this->prepareDataReader($query);    
    } 
}
