<?php

declare(strict_types=1); 

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;
use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Task
 * @extends Select\Repository<TEntity>
 */
final class TaskRepository extends Select\Repository
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
     * Get tasks  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('tax_rate');
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
     * @param array|Task|null $task
     * @throws Throwable 
     * @return void
     */
    public function save(array|Task|null $task): void
    {
        $this->entityWriter->write([$task]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Task|null $task
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Task|null $task): void
    {
        $this->entityWriter->delete([$task]);
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
     * @return null|Task
     *
     * @psalm-return TEntity|null
     */
    public function repoTaskquery(string $id):Task|null    {
        $query = $this->select()->load('tax_rate')
                                ->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    
    /**
     * Get tasks  with filter
     *
     * @psalm-return EntityReader
     */
    public function repoTaskStatusquery(int $status): EntityReader {
        $query = $this->select()->load('tax_rate')
                                ->where(['status' =>$status]);
        return $this->prepareDataReader($query);        
    }
    
    /**
     * 
     * @param array $task_ids
     * @return EntityReader
     */    
    public function findinTasks(array $task_ids) : EntityReader {
        $query = $this->select()
                      ->where(['id'=>['in'=> new Parameter($task_ids)]]);
        return $this->prepareDataReader($query);    
    } 
    
    /**
     * 
     * @param string $task_id
     * @return int
     */
    public function repoCount(string $task_id) : int {
        $count = $this->select()
                      ->where(['id'=>$task_id])
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param \App\Invoice\Setting\SettingRepository $sR
     * @return array
     */
    public function getTask_statuses(\App\Invoice\Setting\SettingRepository $sR): array
    {
        return [
            '1' => [
                'label' => $sR->trans('not_started'),
                'class' => 'draft'
            ],
            '2' => [
                'label' => $sR->trans('in_progress'),
                'class' => 'viewed'
            ],
            '3' => [
                'label' => $sR->trans('complete'),
                'class' => 'sent'
            ],
            '4' => [
                'label' => $sR->trans('invoiced'),
                'class' => 'paid'
            ]
        ];
    }
}