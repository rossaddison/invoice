<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of ClientPeppol
 * @extends Select\Repository<TEntity>
 */
final class ClientPeppolRepository extends Select\Repository
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
     * Get clientpeppols  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('client');
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
     * @param array|ClientPeppol|null $clientpeppol
     * @psalm-param TEntity $clientpeppol
     * @throws Throwable
     */
    public function save(array|ClientPeppol|null $clientpeppol): void
    {
        $this->entityWriter->write([$clientpeppol]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|ClientPeppol|null $clientpeppol

     * @throws Throwable
     */
    public function delete(array|ClientPeppol|null $clientpeppol): void
    {
        $this->entityWriter->delete([$clientpeppol]);
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
     * @psalm-return TEntity|null
     * @return ClientPeppol|null
     */
    public function repoClientPeppolLoadedquery(int $client_id): ?ClientPeppol
    {
        $query = $this->select()
                      ->load('client')
                      ->where(['client_id' => $client_id]);
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
     * @return int
     */
    public function repoClientCount(int $client_id): int
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $query->count();
    }
}
