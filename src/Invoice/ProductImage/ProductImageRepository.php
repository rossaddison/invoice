<?php

declare(strict_types=1); 

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of ProductImage
 * @extends Select\Repository<TEntity>
 */
final class ProductImageRepository extends Select\Repository
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
    
    public string $ctype_default = "application/octet-stream";
    
    public array $content_types = [
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'bmp' => 'image/bmp',
        'tiff' => 'image/tiff'
    ];
    
    /**
     * @return array
     */
    public function getContentTypes() : array {
        return $this->content_types;
    }
    
    /**
     * @return string
     */
    public function getContentTypeDefaultOctetStream() : string {
        return $this->ctype_default;
    }

    /**
     * Get productimages  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('product');
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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|ProductImage|null $productimage
     * @throws Throwable 
     * @return void
     */
    public function save(array|ProductImage|null $productimage): void
    {
        $this->entityWriter->write([$productimage]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|ProductImage|null $productimage
     * @throws Throwable 
     * @return void
     */
    public function delete(array|ProductImage|null $productimage): void
    {
        $this->entityWriter->delete([$productimage]);
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
     * @return ProductImage|null
     */
    public function repoProductImagequery(string $id) : ProductImage|null {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * Get productimages
     *
     * @psalm-return EntityReader
     */
    public function repoProductImageProductquery(int $product_id): EntityReader {
        $query = $this->select()
                      ->andWhere(['product_id'=>$product_id]);        
        return $this->prepareDataReader($query);
    }
    
    /**
     *
     * @param int $product_id
     * @return int
     */
    public function repoCount(int $product_id) : int {
        $query = $this->select()
                      ->andWhere(['product_id'=>$product_id]); 
        return $query->count();
    }   
}