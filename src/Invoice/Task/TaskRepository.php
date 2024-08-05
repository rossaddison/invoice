<?php

declare(strict_types=1); 

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;
use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * @template TEntity of Task
 * @extends Select\Repository<TEntity>
 */
final class TaskRepository extends Select\Repository
{
    private EntityWriter $entityWriter;
    private Translator $translator;
    
     /**
     * 
     * @param Select<TEntity> $select     
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter, Translator $translator)
    {
        $this->entityWriter = $entityWriter;
        $this->translator = $translator;
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
     * @param Translator $translator
     * @return array
     */
    public function getTaskStatuses(Translator $translator): array
    {
        return [
            '1' => [
                'label' => $translator->translate('i.not_started'),
                'class' => 'draft'
            ],
            '2' => [
                'label' => $translator->translate('i.in_progress'),
                'class' => 'viewed'
            ],
            '3' => [
                'label' => $translator->translate('i.complete'),
                'class' => 'sent'
            ],
            '4' => [
                'label' => $translator->translate('i.invoiced'),
                'class' => 'paid'
            ]
        ];
    }
    
    /**
     * 
     * @param string $key
     * @return string
     */
    public function getSpecificStatusArrayLabel(string $key) : string
    {
        $statuses_array = $this->getTaskStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }
    
    /**
     * 
     * @param int $status
     * @return string
     */
    public function getSpecificStatusArrayClass(int $status) : string
    {
        $statuses_array = $this->getTaskStatuses($this->translator);
        /**
         * @var array $statuses_array[$status]
         * @var string $statuses_array[$status]['class']
         */
        return $statuses_array[$status]['class'];
    }
}    