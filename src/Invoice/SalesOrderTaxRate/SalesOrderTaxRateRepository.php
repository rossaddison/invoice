<?php

declare(strict_types=1); 

namespace App\Invoice\SalesOrderTaxRate;

use App\Invoice\Entity\SalesOrderTaxRate;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderTaxRate
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderTaxRateRepository extends Select\Repository
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
     * Get salesordertaxrates  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('salesorder')
                      ->load('tax_rate');
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
     * @param array|SalesOrderTaxRate|null $salesordertaxrate
     * @throws Throwable 
     * @return void
     */
    public function save(array|SalesOrderTaxRate|null $salesordertaxrate): void
    {
        $this->entityWriter->write([$salesordertaxrate]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderTaxRate|null $salesordertaxrate
     * @throws Throwable 
     * @return void
     */
    public function delete(array|SalesOrderTaxRate|null $salesordertaxrate): void
    {
        $this->entityWriter->delete([$salesordertaxrate]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    //find all salesorder tax rates assigned to specific salesorder. Normally only one but just in case more than one assigned
    //used in salesorder/view to determine if a 'one-off'  salesorder tax rate acquired from tax rates is to be applied to the salesorder 
    //salesorder tax rates are children of their parent tax rate and are normally used when all products use the same tax rate ie. no item tax
    /**
     * @param null|string $salesorder_id
     */
    public function repoCount(string|null $salesorder_id): int {
        $count = $this->select()
                      ->where(['so_id' => $salesorder_id])
                      ->count();
        return $count;   
    }
    
    //find a specific salesorders tax rate, normally to delete
    /**
     * @return null|SalesOrderTaxRate
     *
     * @psalm-return TEntity|null
     */
    public function repoSalesOrderTaxRatequery(string $id):SalesOrderTaxRate|null    {
        $query = $this->select()
                      ->load('salesorder')
                      ->load('tax_rate')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    // find all salesorder tax rates used for a specific salesorder normally to apply include_item_tax 
    // (see function calculate_salesorder_taxes in NumberHelper
    // load 'tax rate' so that we can use tax_rate_id through the BelongTo relation in the Entity
    // to access the parent tax rate table's percent name and percentage 
    // which we will use in salesorder/view
    
    /**
     * 
     * @param string $salesorder_id
     * @return EntityReader
     */
    public function repoSalesOrderquery(string $salesorder_id): EntityReader    {
        $query = $this->select()
                      ->load('tax_rate')
                      ->where(['so_id' => $salesorder_id]);
        return $this->prepareDataReader($query);   
    }
    
    /**
     * @return null|SalesOrderTaxRate
     *
     * @psalm-return TEntity|null
     */
    public function repoTaxRatequery(string $tax_rate_id):SalesOrderTaxRate|null    {
        $query = $this->select()
                      ->load('tax_rate')
                      ->where(['tax_rate_id' => $tax_rate_id]);
        return  $query->fetchOne() ?: null;        
    }
        
    public function repoGetSalesOrderTaxRateAmounts(string $salesorder_id): EntityReader  {
        $query = $this->select()
                      ->where(['so_id'=>$salesorder_id]);
        return $this->prepareDataReader($query);   
    }    
}