<?php

declare(strict_types=1); 

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\UnitPeppol;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of UnitPeppol
 * @extends Select\Repository<TEntity>
 */
final class UnitPeppolRepository extends Select\Repository
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
     * Get unitpeppols  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();        return $this->prepareDataReader($query);
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
     * @param array|UnitPeppol|null $unitpeppol
     * @psalm-param TEntity $unitpeppol
     * @throws Throwable 
     * @return void
     */
    public function save(array|UnitPeppol|null $unitpeppol): void
    {
        $this->entityWriter->write([$unitpeppol]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|UnitPeppol|null $unitpeppol
  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|UnitPeppol|null $unitpeppol): void
    {
        $this->entityWriter->delete([$unitpeppol]);
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
     * @return UnitPeppol|null
     */
    public function repoUnitPeppolLoadedquery(string $id): UnitPeppol|null
    {
        $query = $this->select()
                      ->load('unit')
                      ->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @param string $unit_id
     * @return int
     */
    public function repoUnitCount(string $unit_id) : int
    {
        $query = $this->select()
                      ->where(['unit_id' => $unit_id]);
        return $query->count();
    }
    
    public function repoUnit(string $unit_id) : UnitPeppol|null
    {
        $query = $this->select()
                      ->where(['unit_id' => $unit_id]);
        return $query->fetchOne() ?: null; 
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