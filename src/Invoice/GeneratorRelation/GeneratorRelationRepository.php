<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of GentorRelation
 * @extends Select\Repository<TEntity>
 */
final class GeneratorRelationRepository extends Select\Repository
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
     * Get generatorrelations without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
    
        
    public function findRelations(string $id): EntityReader 
    {
        $query = $this->select()->load('gentor')->where('gentor_id',$id);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|GentorRelation|null $generatorrelation
     * @throws Throwable 
     * @return void
     */
    public function save(array|GentorRelation|null $generatorrelation): void
    {
        $this->entityWriter->write([$generatorrelation]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|GentorRelation|null $generatorrelation
     * @throws Throwable 
     * @return void
     */
    public function delete(array|GentorRelation|null $generatorrelation): void
    {
        $this->entityWriter->delete([$generatorrelation]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['lowercasename','camelcasename','gentor_id'])
                ->withOrder(['gentor_id' => 'asc'])
        );
    }
    
    /**
     * @return null|GentorRelation
     *
     * @psalm-return TEntity|null
     */
    public function repoGeneratorRelationquery(string $id):GentorRelation|null
    {
        $query = $this
            ->select()
            ->load('gentor')
            ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $id
     * @return array
     */
    public function repoGeneratorquery(string $id): array
    {
        $query = $this
            ->select()
            ->where(['gentor_id' => $id]);
        return  $query->fetchAll();        
    }
    
    /**
     * 
     * @param string $generatorrelation_lowercase_name
     * @return object|null
     */
    public function withLowercaseName(string $generatorrelation_lowercase_name): object|null
    {
        $query = $this
            ->select()
            ->where(['lowercasename' => $generatorrelation_lowercase_name]);
        return  $query->fetchOne() ?: null;
    }
}
