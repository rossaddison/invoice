<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of PeppolMessage
 * @extends Select\Repository<TEntity>
 */
final class PeppolMessageRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(
        Select $select,
        private readonly EntityWriter $entityWriter,
    ) {
        parent::__construct($select);
    }

    /** @psalm-return EntityReader */
    public function findAllPreloaded(): EntityReader
    {
        return $this->prepareDataReader($this->select());
    }

    /**
     * @throws Throwable
     */
    public function save(PeppolMessage $message): void
    {
        $this->entityWriter->write([$message]);
    }

    /**
     * @throws Throwable
     */
    public function delete(PeppolMessage $message): void
    {
        $this->entityWriter->delete([$message]);
    }

    public function repoFind(int $id): ?PeppolMessage
    {
        return $this->select()->where(['id' => $id])->fetchOne() ?: null;
    }

    /** @psalm-return EntityReader */
    public function repoInvMessages(int $inv_id): EntityReader
    {
        return $this->prepareDataReader(
            $this->select()->where(['inv_id' => $inv_id])
        );
    }

    /** @psalm-return EntityReader */
    public function repoByStatus(string $status): EntityReader
    {
        return $this->prepareDataReader(
            $this->select()->where(['status' => $status])
        );
    }

    public function repoByMessageId(string $message_id): ?PeppolMessage
    {
        return $this->select()->where(['message_id' => $message_id])->fetchOne() ?: null;
    }

    public function repoCount(int $inv_id): int
    {
        return $this->select()->where(['inv_id' => $inv_id])->count();
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])->withOrder(['id' => 'desc'])
        );
    }
}
