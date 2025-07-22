<?php

declare(strict_types=1);

namespace App\Invoice\PostalAddress;

use App\Invoice\Entity\PostalAddress;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of PostalAddress
 *
 * @extends Select\Repository<TEntity>
 */
final class PostalAddressRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get postaladdress  without filter.
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
     * @psalm-param TEntity $postaladdress
     *
     * @throws \Throwable
     */
    public function save(array|PostalAddress|null $postaladdress): void
    {
        $this->entityWriter->write([$postaladdress]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|PostalAddress|null $postaladdress): void
    {
        $this->entityWriter->delete([$postaladdress]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoPostalAddressLoadedquery(string $id): ?PostalAddress
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoClient(string $client_id): ?PostalAddress
    {
        $query = $this->select()
            ->where(['client_id' => $client_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoClientAll(string $client_id): EntityReader
    {
        $query = $this->select()
            ->where(['client_id' => $client_id]);

        return $this->prepareDataReader($query);
    }

    public function repoClientCount(string $client_id): int
    {
        $query = $this->select()
            ->where(['client_id' => $client_id]);

        return $query->count();
    }
}
