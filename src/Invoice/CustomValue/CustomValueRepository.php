<?php

declare(strict_types=1); 

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomValue;
use App\Invoice\Entity\CustomField;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of CustomValue
 * @extends Select\Repository<TEntity>
 */
final class CustomValueRepository extends Select\Repository
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
     * Get customvalues  without filter
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
    
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }
    
    /**
     * 
     * @param array|CustomValue|null $customvalue
     * @return void
     */
    public function save(array|CustomValue|null $customvalue): void
    {
        $this->entityWriter->write([$customvalue]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|CustomValue|null $customvalue
     * @throws Throwable 
     * @return void
     */
    public function delete(array|CustomValue|null $customvalue): void
    {
        $this->entityWriter->delete([$customvalue]);
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
     * @return CustomValue|null
     */
    public function repoCustomValuequery(string $id): CustomValue|null {
        $query = $this->select()->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $id
     * @return int
     */
    public function repoCount(string $id): int {
        $count = $this->select()
                      ->where(['id' => $id])
                      ->count();
        return $count;   
    }
        
    /**
     * Get customvalues  with filter
     *
     * @psalm-return EntityReader
     */
    public function repoCustomFieldquery(int $custom_field_id): EntityReader    {
        $query = $this->select()->where(['custom_field_id' =>$custom_field_id]);
        return $this->prepareDataReader($query);        
    }
    
     /**
      * 
      * @param int $custom_field_id
      * @return int
      */
    public function repoCustomFieldquery_count(int $custom_field_id): int    {
        $count = $this->select()
                      ->where(['custom_field_id' =>$custom_field_id])
                      ->count();
        return $count;        
    }
    
    /**
     * 
     * @param EntityReader $custom_fields
     * @return array
     */
    public function attach_hard_coded_custom_field_values_to_custom_field(EntityReader $custom_fields) : array{
        $custom_values  = [];
        /** @var CustomField $custom_field */
        foreach ($custom_fields as $custom_field) {
            if (in_array($custom_field->getType(),['SINGLE-CHOICE','MULTIPLE-CHOICE'])) {
                // build the $custom_values array with the eg. dropdown values for the field whether it be a multiple-choice field or a single-choice field
                $custom_values[$custom_field->getId()] = $this->repoCustomFieldquery((integer)$custom_field->getId());
            }
        }
        return $custom_values;
    }
    
}