<?php

declare(strict_types=1); 

namespace App\Invoice\InvCustom;

use App\Invoice\Entity\InvCustom;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of InvCustom
 * @extends Select\Repository<TEntity>
 */
final class InvCustomRepository extends Select\Repository
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
     * Get invcustoms  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('custom_field')->load('inv');
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
     * @param array|InvCustom|null $invcustom
     * @throws Throwable 
     * @return void
     */
    public function save(array|InvCustom|null $invcustom): void
    {
        $this->entityWriter->write([$invcustom]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|InvCustom|null $invcustom
     * @throws Throwable 
     * @return void
     */
    public function delete(array|InvCustom|null $invcustom): void
    {
        $this->entityWriter->delete([$invcustom]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|InvCustom
     *
     * @psalm-return TEntity|null
     */
    public function repoInvCustomquery(string $id): InvCustom|null    {
        $query = $this->select()->load('custom_field')
                                ->load('inv')
                                ->where(['id'=>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @return null|InvCustom
     *
     * @psalm-return TEntity|null
     */
    public function repoFormValuequery(string $inv_id, string $custom_field_id) : InvCustom|null {
        $query = $this->select()->where(['inv_id' =>$inv_id])
                                ->andWhere(['custom_field_id' =>$custom_field_id]);
        return  $query->fetchOne();        
    }
    
    public function repoInvCustomCount(string $inv_id, string $custom_field_id) : int {
        $query = $this->select()->where(['inv_id' =>$inv_id])
                                ->andWhere(['custom_field_id' =>$custom_field_id]);
        return $query->count();
    } 
    
    public function repoInvCount(string $inv_id) : int {
        $query = $this->select()->where(['inv_id' =>$inv_id]);
        return $query->count();
    }   
    
    /**
     * Get all fields that have been setup for a particular inv
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $inv_id): EntityReader
    {
        $query = $this->select()->where(['inv_id'=>$inv_id]);                
        return $this->prepareDataReader($query);
    }
}