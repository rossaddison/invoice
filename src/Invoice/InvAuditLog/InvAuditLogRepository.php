<?php

declare(strict_types=1);

namespace App\Invoice\InvAuditLog;

use App\Infrastructure\Persistence\InvAuditLog\InvAuditLog;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of InvAuditLog
 * @extends Select\Repository<TEntity>
 */
final class InvAuditLogRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(
        Select $select,
        private readonly EntityWriter $entityWriter,
    ) {
        parent::__construct($select);
    }

    /**
     * Every audit entry for one invoice, newest first -- the entry point a
     * future "history" tab on inv/view would use.
     *
     * @return EntityReader
     */
    public function repoForInvoice(int $inv_id): EntityReader
    {
        $query = $this->select()
                      ->load('user')
                      ->where(['inv_id' => $inv_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * Every audit entry a given staff member has made, newest first.
     *
     * @return EntityReader
     */
    public function repoForUser(int $user_id): EntityReader
    {
        $query = $this->select()
                      ->load('inv')
                      ->where(['user_id' => $user_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param array|InvAuditLog|null $invAuditLog
     * @psalm-param TEntity $invAuditLog
     * @throws Throwable
     */
    public function save(array|InvAuditLog|null $invAuditLog): void
    {
        $this->entityWriter->write([$invAuditLog]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc']),
        );
    }
}
