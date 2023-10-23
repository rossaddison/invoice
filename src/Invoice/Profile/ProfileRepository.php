<?php

declare(strict_types=1); 

namespace App\Invoice\Profile;

use App\Invoice\Entity\Profile;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Profile
 * @extends Select\Repository<TEntity>
 */
final class ProfileRepository extends Select\Repository
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
     * Get profiles  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('company');
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
     * @param array|Profile|null $profile
     * @throws Throwable 
     * @return void
     */
    public function save(array|Profile|null $profile): void
    {
        $this->entityWriter->write([$profile]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Profile|null $profile
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Profile|null $profile): void
    {
        $this->entityWriter->delete([$profile]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|Profile
     *
     * @psalm-return TEntity|null
     */
    public function repoProfilequery(string $id):Profile|null    {
        $query = $this->select()->load('company')->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
}