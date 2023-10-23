<?php
declare(strict_types=1); 

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of AllowanceCharge
 * @extends Select\Repository<TEntity>
 */
final class AllowanceChargeRepository extends Select\Repository
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
     * Get allowancecharges  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()                      
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
    
    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }    
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|AllowanceCharge|null $allowancecharge
     * @psalm-param TEntity $allowancecharge
     * @throws Throwable 
     * @return void
     */
    public function save(array|AllowanceCharge|null $allowancecharge): void
    {
        $this->entityWriter->write([$allowancecharge]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|AllowanceCharge|null $allowancecharge
  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|AllowanceCharge|null $allowancecharge): void
    {
        $this->entityWriter->delete([$allowancecharge]);
    }
    
    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }    
    
    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return AllowanceCharge|null
     */
    public function repoAllowanceChargequery(string $id): AllowanceCharge|null
    {
        $query = $this->select()
                      ->load('tax_rate')
                      ->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id) : int {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }   
}