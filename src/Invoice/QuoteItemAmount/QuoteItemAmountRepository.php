<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteItemAmount;

use App\Invoice\Entity\QuoteItemAmount;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of QuoteItemAmount
 * @extends Select\Repository<TEntity>
 */
final class QuoteItemAmountRepository extends Select\Repository
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
     * Get quoteitemamounts  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('quote_item');
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
     * @param array|QuoteItemAmount|null $quoteitemamount
     * @throws Throwable 
     * @return void
     */
    public function save(array|QuoteItemAmount|null $quoteitemamount): void
    {
        $this->entityWriter->write([$quoteitemamount]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|QuoteItemAmount|null $quoteitemamount
     * @throws Throwable 
     * @return void
     */
    public function delete(array|QuoteItemAmount|null $quoteitemamount): void
    {
        $this->entityWriter->delete([$quoteitemamount]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    } 
    
    /**
     * @return null|QuoteItemAmount
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteItemAmountquery(string $quote_item_id): QuoteItemAmount|null {
        $query = $this->select()
                      ->load(['quote_item'])
                      ->where(['quote_item_id' => $quote_item_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $quote_item_id
     * @return int
     */
    public function repoCount(string $quote_item_id): int {
        $query = $this->select()
                      ->where(['quote_item_id'=>$quote_item_id]);
        return $query->count(); 
    }
}