<?php

declare(strict_types=1);

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of ClientNote
 * @extends Select\Repository<TEntity>
 */
final class ClientNoteRepository extends Select\Repository
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
     * Get clientnotes  without filter
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
     * @param array|ClientNote|null $clientnote
     * @throws Throwable
     */
    public function save(array|ClientNote|null $clientnote): void
    {
        $this->entityWriter->write([$clientnote]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|ClientNote|null $clientnote
     * @throws Throwable
     */
    public function delete(array|ClientNote|null $clientnote): void
    {
        $this->entityWriter->delete([$clientnote]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @return ClientNote|null
     *
     * @psalm-return TEntity|null
     */
    public function repoClientNotequery(string $id): ClientNote|null
    {
        $query = $this->select()->load('client')->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoClientquery(string $client_id): EntityReader
    {
        $query = $this->select()->load('client')->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }

    public function repoClientNoteCount(int $client_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->count();
    }
}
