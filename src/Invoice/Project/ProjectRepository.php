<?php

declare(strict_types=1); 

namespace App\Invoice\Project;

use App\Invoice\Entity\Project;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Project
 * @extends Select\Repository<TEntity>
 */
final class ProjectRepository extends Select\Repository
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
     * Get projects  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('client');
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
     * @param array|Project|null $project
     * @throws Throwable 
     * @return void
     */
    public function save(array|Project|null $project): void
    {
        $this->entityWriter->write([$project]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Project|null $project
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Project|null $project): void
    {
        $this->entityWriter->delete([$project]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|Project
     *
     * @psalm-return TEntity|null
     */
    public function repoProjectquery(string $id):Project|null    {
        $query = $this->select()->load('client')->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $project_id
     * @return int
     */
    public function count(string $project_id) : int {
        $count = $this->select()
                      ->where(['id'=>$project_id])
                      ->count();
        return $count;
    }
}