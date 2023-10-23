<?php

declare(strict_types=1); 

namespace App\Invoice\SalesOrderItemAmount;

use App\Invoice\Entity\SalesOrderItemAmount;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderItemAmount
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderItemAmountRepository extends Select\Repository
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
     * Get salesorderitemamounts  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('so_item');
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
     * @param array|SalesOrderItemAmount|null $salesorderitemamount
     * @throws Throwable 
     * @return void
     */
    public function save(array|SalesOrderItemAmount|null $salesorderitemamount): void
    {
        $this->entityWriter->write([$salesorderitemamount]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderItemAmount|null $salesorderitemamount
     * @throws Throwable 
     * @return void
     */
    public function delete(array|SalesOrderItemAmount|null $salesorderitemamount): void
    {
        $this->entityWriter->delete([$salesorderitemamount]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    } 
    
    /**
     * @return null|SalesOrderItemAmount
     *
     * @psalm-return TEntity|null
     */
    public function repoSalesOrderItemAmountquery(string $so_item_id): SalesOrderItemAmount|null {
        $query = $this->select()
                      ->load(['so_item'])
                      ->where(['so_item_id' => $so_item_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $so_item_id
     * @return int
     */
    public function repoCount(string $so_item_id): int {
        $query = $this->select()
                      ->where(['so_item_id'=>$so_item_id]);
        return $query->count(); 
    }
}