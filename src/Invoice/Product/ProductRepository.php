<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;
use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Product
 * @extends Select\Repository<TEntity>
 */
final class ProductRepository extends Select\Repository
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
     * Get products without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit');
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
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Product|null $product
     * @throws Throwable 
     * @return void
     */
    public function save(array|Product|null $product): void
    {
        $this->entityWriter->write([$product]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Product|null $product
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Product|null $product): void
    {
        $this->entityWriter->delete([$product]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'product_description'])
                ->withOrder([
                             'id'=>'desc',
                             'product_description' => 'desc'
                            ])
        );
    }
    
    /**
     * @param null|string $product_id
     *
     * @return null|Product
     *
     * @psalm-return TEntity|null
     */
    public function repoProductquery(string|null $product_id): Product|null
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit')
            ->where(['id' => $product_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $product_name
     * @return Product|null
     */
    public function withName(string $product_name) : Product|null 
    {
        $query = $this
            ->select()
            ->where(['product_name' => $product_name]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * Get products with filter
     *
     * @psalm-return EntityReader
     */
    
    public function repoProductwithfamilyquery(string $product_name, string $family_id): EntityReader
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit');

        //lookup without filters eg. product/lookup
        if (empty($product_name)&&(empty($family_id))) {}
                
        //eg. product/lookup?fp=Cleaning%20Services
        if ((!empty($product_name))&&(empty($family_id))) {      
            $query = $query->where(['product_name' => ltrim(rtrim($product_name))]);
        }
        
        //eg. product/lookup?Cleaning%20Services&ff=4
        if (!empty($product_name)&&($family_id>(string)0)) {      
            $query = $query->where(['family_id'=>$family_id])->andWhere(['product_name' => ltrim(rtrim($product_name))]);
        }
        
        //eg. product/lookup?ff=4
        if (empty($product_name)&&($family_id>(string)0)) {                  
            $query = $query->where(['family_id'=>$family_id]);
        }
        
        return $this->prepareDataReader($query);
    } 
    
     /**
      * Get selection of products from all products
      * 
      * @param array $product_ids
      * @return EntityReader
      */
    public function findinProducts(array $product_ids) : EntityReader {
        $query = $this
        ->select()
        ->where(['id'=>['in'=> new Parameter($product_ids)]]);
        return $this->prepareDataReader($query);    
    } 
    
    /**
     * 
     * @param string $product_id
     * @return int
     */
    public function repoCount(string $product_id): int {
        $count = $this->select()
                      ->where(['id' => $product_id])
                      ->count();
        return $count;   
    }
    
    /**
     * @return int
     */
    public function repoTestDataCount(): int {
        $count = $this->select()
                      ->count();
        return $count;   
    }
}
