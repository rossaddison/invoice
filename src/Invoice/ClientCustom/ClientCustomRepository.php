<?php

declare(strict_types=1);

namespace App\Invoice\ClientCustom;

use App\Invoice\Entity\ClientCustom;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of ClientCustom
 *
 * @extends Select\Repository<TEntity>
 */
final class ClientCustomRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get clientcustoms  without filter.
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|ClientCustom|null $clientcustom): void
    {
        $this->entityWriter->write([$clientcustom]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|ClientCustom|null $clientcustom): void
    {
        $this->entityWriter->delete([$clientcustom]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoClientCustomquery(string $id): ?ClientCustom
    {
        $query = $this->select()
            ->load('client')
            ->load('custom_field')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoClientCount(string $client_id): int
    {
        $query = $this->select()
            ->where(['client_id' => $client_id]);

        return $query->count();
    }

    public function repoFormValuequery(string $client_id, string $custom_field_id): ?ClientCustom
    {
        $query = $this->select()
            ->where(['client_id' => $client_id])
            ->andWhere(['custom_field_id' => $custom_field_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoClientCustomCount(string $client_id, string $custom_field_id): int
    {
        $query = $this->select()
            ->where(['client_id' => $client_id])
            ->andWhere(['custom_field_id' => $custom_field_id]);

        return $query->count();
    }

    /**
     * Get all fields that have been setup for a particular client.
     *
     * @psalm-return EntityReader
     */
    public function repoFields(string $client_id): EntityReader
    {
        $query = $this->select()->where(['client_id' => $client_id]);

        return $this->prepareDataReader($query);
    }
}
