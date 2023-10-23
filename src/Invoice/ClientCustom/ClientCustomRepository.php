<?php

declare(strict_types=1); 

namespace App\Invoice\ClientCustom;

use App\Invoice\Entity\ClientCustom;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of ClientCustom
 * @extends Select\Repository<TEntity>
 */
final class ClientCustomRepository extends Select\Repository
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
     * Get clientcustoms  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                ->load('client')
                ->load('custom_field');
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
     * @param array|ClientCustom|null $clientcustom
     * @throws Throwable 
     * @return void
     */
    public function save(array|ClientCustom|null $clientcustom): void
    {
        $this->entityWriter->write([$clientcustom]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|ClientCustom|null $clientcustom
     * @throws Throwable 
     * @return void
     */
    public function delete(array|ClientCustom|null $clientcustom): void
    {
        $this->entityWriter->delete([$clientcustom]);
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
    * @return ClientCustom|null
    */
    public function repoClientCustomquery(string $id) : ClientCustom|null{
        $query = $this->select()
                      ->load('client')
                      ->load('custom_field')
                      ->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $client_id
     * @return int
     */
    public function repoClientCount(string $client_id) : int {
        $query = $this->select()
                      ->where(['client_id' =>$client_id]);
        return $query->count();
    }
    
    /**
     * 
     * @param string $client_id
     * @param string $custom_field_id
     * @return ClientCustom|null
     */
    public function repoFormValuequery(string $client_id, string $custom_field_id): ClientCustom|null {
        $query = $this->select()
                      ->where(['client_id' =>$client_id])
                      ->andWhere(['custom_field_id' =>$custom_field_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $client_id
     * @param string $custom_field_id
     * @return int
     */
    public function repoClientCustomCount(string $client_id, string $custom_field_id) : int {
        $query = $this->select()
                      ->where(['client_id' =>$client_id])
                      ->andWhere(['custom_field_id' =>$custom_field_id]);
        return $query->count();
    } 
    
    /**
     * Get all fields that have been setup for a particular client
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $client_id): EntityReader
    {
        $query = $this->select()->where(['client_id'=>$client_id]);                
        return $this->prepareDataReader($query);
    }
}