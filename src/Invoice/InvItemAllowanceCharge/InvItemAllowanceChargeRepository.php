<?php

declare(strict_types=1); 

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\Entity\InvItemAllowanceCharge;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of InvItemAllowanceCharge
 * @extends Select\Repository<TEntity>
 */
final class InvItemAllowanceChargeRepository extends Select\Repository
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
     * Get aciis  without filter
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
     * @param array|InvItemAllowanceCharge|null $acii
     * @psalm-param TEntity $acii
     * @throws Throwable 
     * @return void
     */
    public function save(array|InvItemAllowanceCharge|null $acii): void
    {
        $this->entityWriter->write([$acii]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|InvItemAllowanceCharge|null $acii
  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|InvItemAllowanceCharge|null $acii): void
    {
        $this->entityWriter->delete([$acii]);
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
     * All item allowances or charges for this invoice 
     * @param string $inv_id
     * @return EntityReader
     */
    public function repoACIquery(string $inv_id) : EntityReader 
    {
        $query = $this->select()
                      ->where(['inv_id'=>$inv_id]);
        return $this->prepareDataReader($query);
    }        
    
    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return InvItemAllowanceCharge|null
     */
    public function repoInvItemAllowanceChargequery(string $id): InvItemAllowanceCharge|null
    {
        $query = $this->select()
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
    
    /**
     * @param string $inv_id
     * @return int
     */
    public function repoInvCount(string $inv_id) : int {
        $query = $this->select()
                      ->where(['inv_id' => $inv_id]);
        return $query->count();
    }
    
    /**
     * @param string $inv_item_id
     * @return int
     */
    public function repoInvItemCount(string $inv_item_id) : int {
        $query = $this->select()
                      ->where(['inv_item_id' => $inv_item_id]);
        return $query->count();
    }
    
    /**
     * All allowances and charges for this invoice item
     * @param string $inv_item_id
     * @return EntityReader
     */
    public function repoInvItemquery(string $inv_item_id) : EntityReader 
    {
        $query = $this->select()
                      ->where(['inv_item_id'=>$inv_item_id]);
        return $this->prepareDataReader($query);
    }
}