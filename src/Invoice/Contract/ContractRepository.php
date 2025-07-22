<?php

declare(strict_types=1);

namespace App\Invoice\Contract;

use App\Invoice\Entity\Contract;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Contract
 *
 * @extends Select\Repository<TEntity>
 */
final class ContractRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get contracts  without filter.
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @psalm-param TEntity $contract
     *
     * @throws \Throwable
     */
    public function save(array|Contract|null $contract): void
    {
        $this->entityWriter->write([$contract]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|Contract|null $contract): void
    {
        $this->entityWriter->delete([$contract]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoClientCount(string $client_id): int
    {
        $query = $this->select()
            ->where(['client_id' => $client_id]);

        return $query->count();
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoContractquery(string $id): ?Contract
    {
        $query = $this->select()
            ->load('client')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }

    public function repoClient(string $client_id): EntityReader
    {
        $query = $this->select()
            ->where(['client_id' => $client_id]);

        return $this->prepareDataReader($query);
    }
}
