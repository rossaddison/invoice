<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of TaxRate
 * @extends Select\Repository<TEntity>
 */
final class TaxRateRepository extends Select\Repository
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
     * Get taxrates without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
            
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|TaxRate|null $taxrate
     * @throws Throwable 
     * @return void
     */
    public function save(array|TaxRate|null $taxrate): void
    {
        $this->entityWriter->write([$taxrate]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|TaxRate|null $taxrate
     * @throws Throwable 
     * @return void
     */
    public function delete(array|TaxRate|null $taxrate): void
    {
        $this->entityWriter->delete([$taxrate]);
    }

    /**
     * 
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'tax_rate_name'])
                ->withOrder(['tax_rate_name' => 'asc'])
        );
    }
    
    /**
     * 
     * @param string $tax_rate_id
     * @return null|TaxRate
     */
    public function repoTaxRatequery(string $tax_rate_id): null|TaxRate
    {
        $query = $this
            ->select()
            ->where(['id' => $tax_rate_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $tax_rate_name
     * @return TaxRate|null
     */
    public function withName(string $tax_rate_name): TaxRate|null
    {
        $query = $this
            ->select()
            ->where(['tax_rate_name' => $tax_rate_name]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * 
     * @param string $tax_rate_id
     * @return int
     */
    public function repoCount(string $tax_rate_id): int {
        $count = $this->select()
                      ->where(['id' => $tax_rate_id])
                      ->count();
        return $count;   
    }
    
    /**
     * 
     * @return int
     */
    public function repoCountAll(): int {
        $countall = $this->select()
                         ->count();
        return $countall;
    }
}
