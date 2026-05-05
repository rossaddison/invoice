<?php

declare(strict_types=1);

namespace App\Invoice\Contract;

use App\Infrastructure\Persistence\Contract\Contract;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Contract
 * @extends Select\Repository<TEntity>
 */
final class ContractRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get contracts  without filter
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Contract|null $contract
     * @psalm-param TEntity $contract
     * @throws Throwable
     */
    public function save(array|Contract|null $contract): void
    {
        $this->entityWriter->write([$contract]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Contract|null $contract
     * @throws Throwable
     */
    public function delete(array|Contract|null $contract): void
    {
        $this->entityWriter->delete([$contract]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @param int $client_id
     * @return int
     */
    public function repoClientCount(int $client_id): int
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $query->count();
    }

    /**
     * @param int $id
     * @psalm-return TEntity|null
     * @return Contract|null
     */
    public function repoContractquery(int $id): ?Contract
    {
        $query = $this->select()
                      ->load('client')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $id
     * @return int
     */
    public function repoCount(int $id): int
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }

    /**
     * @param int $client_id
     * @return EntityReader
     */
    public function repoClient(int $client_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }
}
