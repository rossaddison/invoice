<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteItem;

use App\Invoice\Entity\QuoteItem;
use Cycle\ORM\Select;
use Cycle\Database\Injection\Parameter;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of QuoteItem
 * @extends Select\Repository<TEntity>
 */
final class QuoteItemRepository extends Select\Repository
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
     * Get quoteitems  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load(['tax_rate','product','quote']);
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
     * @param array|QuoteItem|null $quoteitem
     * @throws Throwable 
     * @return void
     */
    public function save(array|QuoteItem|null $quoteitem): void
    {
        $this->entityWriter->write([$quoteitem]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|QuoteItem|null $quoteitem
     * @throws Throwable 
     * @return void
     */
    public function delete(array|QuoteItem|null $quoteitem): void
    {
        $this->entityWriter->delete([$quoteitem]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|QuoteItem
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteItemquery(string $id):QuoteItem|null    {
        $query = $this->select()->load(['tax_rate','product','quote'])->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }    
    
    /**
     * Get all items id's that belong to a specific quote
     *
     * @psalm-return EntityReader
     */
    public function repoQuoteItemIdquery(string $quote_id):  EntityReader    {
        $query = $this->select()
                      ->load(['tax_rate','product','quote'])
                      ->where(['quote_id' => $quote_id]);
        return $this->prepareDataReader($query); 
    }
    
    /**
     * Get all items belonging to quote
     *
     * @psalm-return EntityReader
     */
    public function repoQuotequery(string $quote_id): EntityReader    {
        $query = $this->select()
                      ->load(['tax_rate','product','quote'])
                      ->where(['quote_id' => $quote_id]);                                
        return $this->prepareDataReader($query);        
    }
    
    public function repoCount(string $quote_id) : int {
        $count = $this->select()
                      ->where(['quote_id' => $quote_id])                                
                      ->count();
        return $count; 
    }
    
    public function repoQuoteItemCount(string $id) : int {
        $count = $this->select()
                      ->where(['id' => $id])                                
                      ->count();
        return $count; 
    }
    
    /**
     * Get selection of quote items from all quote_items
     * 
     * @param array $item_ids
     * @return EntityReader
     */
     
    public function findinQuoteItems(array $item_ids) : EntityReader {
        $query = $this->select()->where(['id'=>['in'=> new Parameter($item_ids)]]);
        return $this->prepareDataReader($query);    
    } 
}
