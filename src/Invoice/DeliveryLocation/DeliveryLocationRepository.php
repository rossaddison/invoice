<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Invoice\Entity\DeliveryLocation;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of DeliveryLocation
 *
 * @extends Select\Repository<TEntity>
 */
final class DeliveryLocationRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
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
     * @throws \Throwable
     */
    public function save(DeliveryLocation $del): void
    {
        $this->entityWriter->write([$del]);
    }

    /**
     * @throws \Throwable
     */
    public function delete(DeliveryLocation $del): void
    {
        $this->entityWriter->delete([$del]);
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
    public function repoDeliveryLocationquery(string $id): ?DeliveryLocation
    {
        // Delivery Location id defaults to 0 in the Entity Delivery Location
        if ($id > 0) {
            $query = $this->select()->where(['id' => $id]);

            return $query->fetchOne() ?: null;
        }

        return null;
    }

    /**
     * Get all delivery locations associated with a Client.
     */
    public function repoClientquery(string $client_id): EntityReader
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

    public function repoInvoiceCount(string $inv_id): int
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

        return $query->count();
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }
}
