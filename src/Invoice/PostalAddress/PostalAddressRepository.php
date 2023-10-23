<?php

declare(strict_types=1); 

namespace App\Invoice\PostalAddress;

use Cycle\ORM\Select;
use App\Invoice\Entity\PostalAddress;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of PostalAddress
 * @extends Select\Repository<TEntity>
 */
final class PostalAddressRepository extends Select\Repository
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
     * Get postaladdress  without filter
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
     * @param array|PostalAddress|null $postaladdress
     * @psalm-param TEntity $postaladdress
     * @throws Throwable 
     * @return void
     */
    public function save(array|PostalAddress|null $postaladdress): void
    {
        $this->entityWriter->write([$postaladdress]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|PostalAddress|null $postaladdress
  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|PostalAddress|null $postaladdress): void
    {
        $this->entityWriter->delete([$postaladdress]);
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
     * @return PostalAddress|null
     */
    public function repoPostalAddressLoadedquery(string $id): PostalAddress|null
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
     * @psalm-return TEntity|null
     * @param string $client_id
     * @return PostalAddress|null
     */
    public function repoClient(string $client_id) : PostalAddress|null {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $query->fetchOne() ?: null;      
    }
    
    /**
     * 
     * @param string $client_id
     * @return EntityReader
     */
    public function repoClientAll(string $client_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @param string $client_id
     * @return int
     */
    public function repoClientCount(string $client_id) : int {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $query->count();      
    }
}