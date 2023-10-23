<?php

declare(strict_types=1); 

namespace App\Invoice\FromDropDown;

use App\Invoice\Entity\FromDropDown;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of FromDropDown
 * @extends Select\Repository<TEntity>
 */
final class FromDropDownRepository extends Select\Repository
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
     * Get froms  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select(); 
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
     * @param array|FromDropDown|null $from
     * @psalm-param TEntity $from
     * @throws Throwable 
     * @return void
     */
    public function save(array|FromDropDown|null $from): void
    {
        $this->entityWriter->write([$from]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|FromDropDown|null $from
  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|FromDropDown|null $from): void
    {
        $this->entityWriter->delete([$from]);
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
     * @return FromDropDown|null
     */
    public function repoFromDropDownLoadedquery(string $id): FromDropDown|null
    {
        $query = $this->select()
                      ->where(['id' =>$id]); 
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * Return the first available default
     * @psalm-return TEntity|null
     * @return FromDropDown|null
     */
    public function getDefault(): FromDropDown|null { 
        $query = $this->select()
                      ->where(['default_email' => 1])
                      ->andWhere(['include' => 1])
                      ->limit(1);
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