<?php
declare(strict_types=1); 

namespace App\Invoice\InvAllowanceCharge;

use App\Invoice\Entity\InvAllowanceCharge;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of InvAllowanceCharge
 * @extends Select\Repository<TEntity>
 */
final class InvAllowanceChargeRepository extends Select\Repository
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
     * Get invallowancecharges  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge');
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
     * @param array|InvAllowanceCharge|null $invallowancecharge
     * @psalm-param TEntity $invallowancecharge
     * @throws Throwable 
     * @return void
     */
    public function save(array|InvAllowanceCharge|null $invallowancecharge): void
    {
        $this->entityWriter->write([$invallowancecharge]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|InvAllowanceCharge|null $invallowancecharge
  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|InvAllowanceCharge|null $invallowancecharge): void
    {
        $this->entityWriter->delete([$invallowancecharge]);
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
     * @return InvAllowanceCharge|null
     */
    public function repoInvAllowanceChargeLoadedquery(string $id): InvAllowanceCharge|null
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['id' =>$id]); 
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @param string $inv_id
     * @psalm-return EntityReader
     */
    public function repoACIquery(string $inv_id): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['inv_id' =>$inv_id]); 
        return $this->prepareDataReader($query);        
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
    
    public function repoACICount(string $inv_id) : int {
        $query = $this->select()
                      ->where(['inv_id' => $inv_id]);
        return $query->count();
    }   
}