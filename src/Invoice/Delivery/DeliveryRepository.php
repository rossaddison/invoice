<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Delivery
 *
 * @extends Select\Repository<TEntity>
 */
final class DeliveryRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get deliverys  without filter.
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
     *
     * @psalm-param TEntity $delivery
     *
     * @throws \Throwable
     */
    public function save(array|Delivery|null $delivery): void
    {
        $this->entityWriter->write([$delivery]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|Delivery|null $delivery): void
    {
        $this->entityWriter->delete([$delivery]);
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
    public function repoDeliveryquery(string $id): ?Delivery
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoPartyquery(string $inv_id): ?Delivery
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoInvoicequery(string $inv_id): ?Delivery
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCountInvoice(string $inv_id): int
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
