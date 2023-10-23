<?php

declare(strict_types=1); 

namespace App\Invoice\UserCustom;

use App\Invoice\Entity\UserCustom;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of UserCustom
 * @extends Select\Repository<TEntity>
 */
final class UserCustomRepository extends Select\Repository
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
     * Get usercustoms  without filter
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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|UserCustom|null $usercustom
     * @throws Throwable 
     * @return void
     */
    public function save(array|UserCustom|null $usercustom): void
    {
        $this->entityWriter->write([$usercustom]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|UserCustom|null $usercustom
     * @throws Throwable 
     * @return void
     */
    public function delete(array|UserCustom|null $usercustom): void
    {
        $this->entityWriter->delete([$usercustom]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|UserCustom
     *
     * @psalm-return TEntity|null
     */
    public function repoUserCustomquery(string $id):UserCustom|null    {
        $query = $this->select()->load('user')->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
}