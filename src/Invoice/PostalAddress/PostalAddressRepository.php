<?php

declare(strict_types=1);

namespace App\Invoice\PostalAddress;

use Cycle\ORM\Select;
use App\Infrastructure\Persistence\PostalAddress\PostalAddress;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of PostalAddress
 * @extends Select\Repository<TEntity>
 */
final class PostalAddressRepository extends Select\Repository
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|PostalAddress|null $postaladdress
     * @psalm-param TEntity $postaladdress
     * @throws Throwable
     */
    public function save(array|PostalAddress|null $postaladdress): void
    {
        $this->entityWriter->write([$postaladdress]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|PostalAddress|null $postaladdress

     * @throws Throwable
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
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @param int $id
     * @psalm-return TEntity|null
     * @return PostalAddress|null
     */
    public function repoPostalAddressLoadedquery(int $id): ?PostalAddress
    {
        $query = $this->select()
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
     * @psalm-return TEntity|null
     * @param int $client_id
     * @return PostalAddress|null
     */
    public function repoClient(int $client_id): ?PostalAddress
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $query->fetchOne() ?: null;
    }

    /**
     * @param int $client_id
     * @return EntityReader
     */
    public function repoClientAll(int $client_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
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
