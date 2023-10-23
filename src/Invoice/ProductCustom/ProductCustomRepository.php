<?php

declare(strict_types=1); 

namespace App\Invoice\ProductCustom;

use App\Invoice\Entity\ProductCustom;
use Cycle\ORM\Select;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of ProductCustom
 * @extends Select\Repository<TEntity>
 */
final class ProductCustomRepository extends Select\Repository
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
     * Get productcustoms  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('custom_field')->load('product');
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
     * @param array|ProductCustom|null $productcustom
     * @return void
     */
    public function save(array|ProductCustom|null $productcustom): void
    {
        $this->entityWriter->write([$productcustom]);
    }
    
    /**
     * @param array|ProductCustom|null $productcustom
     * @return void
     */
    public function delete(array|ProductCustom|null $productcustom): void
    {
        $this->entityWriter->delete([$productcustom]);
    }
    
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
     * @return ProductCustom|null
     */
    public function repoProductCustomquery(string $id):ProductCustom|null    {
        $query = $this->select()->load('custom_field')
                                ->load('product')
                                ->where(['id'=>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    
    /**
     * 
     * @param string $product_id
     * @param string $custom_field_id
     * @return ProductCustom|null
     */
    public function repoFormValuequery(string $product_id, string $custom_field_id):ProductCustom|null {
        $query = $this->select()->where(['product_id' =>$product_id])
                                ->andWhere(['custom_field_id' =>$custom_field_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $product_id
     * @param string $custom_field_id
     * @return int
     */
    public function repoProductCustomCount(string $product_id, string $custom_field_id) : int {
        $query = $this->select()->where(['product_id' =>$product_id])
                                ->andWhere(['custom_field_id' =>$custom_field_id]);
        return $query->count();
    } 
    
    /**
     * 
     * @param string $product_id
     * @return int
     */
    public function repoProductCount(string $product_id) : int {
        $query = $this->select()->where(['product_id' =>$product_id]);
        return $query->count();
    }   
    
    /**
     * Get all fields that have been setup for a particular inv
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $product_id): EntityReader
    {
        $query = $this->select()->where(['product_id'=>$product_id]);                
        return $this->prepareDataReader($query);
    }
}