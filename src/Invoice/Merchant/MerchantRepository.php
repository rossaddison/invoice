<?php

declare(strict_types=1); 

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Merchant;
// Cycle
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
// Yiisoft
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

use Throwable;

/**
 * @template TEntity of Merchant
 * @extends Select\Repository<TEntity>
 */
final class MerchantRepository extends Select\Repository
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
     * Get merchants  without filter
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
     * @param array|Merchant|null $merchant
     * @throws Throwable 
     * @return void
     */
    public function save(array|Merchant|null $merchant): void
    {
        $this->entityWriter->write([$merchant]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Merchant|null $merchant
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Merchant|null $merchant): void
    {
        $this->entityWriter->delete([$merchant]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|Merchant
     *
     * @psalm-return TEntity|null
     */
    public function repoMerchantquery(string $id):Merchant|null    {
        $query = $this->select()->load('inv')
                                ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    // Find all merchant responses associated with a user's clients ie. their client list / client_id_array
    
    /**
     * Get payments  with filter
     *
     * @param array $client_id_array
     *
     * @psalm-return EntityReader
     */
    public function findOneUserManyClientsMerchantResponses(array $client_id_array): EntityReader
    {
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv.client_id'=>['in'=> new Parameter($client_id_array)]]);        
        return   $this->prepareDataReader($query);
    }
}