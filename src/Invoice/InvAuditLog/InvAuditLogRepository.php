<?php

declare(strict_types=1);

namespace App\Invoice\InvAuditLog;

use App\Infrastructure\Persistence\InvAuditLog\InvAuditLog;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Deliberately just the write side for now -- InvService::recordAuditLog()
 * is the only real caller so far. A repoForInvoice()/repoForUser() read
 * side (the entry point a future "history" tab on inv/view would use) was
 * built alongside this and then removed again: with no actual UI caller
 * yet, it was untestable speculative surface area dragging this file's own
 * coverage down (this codebase's own convention -- confirmed by grepping
 * every other Select\Repository subclass in src/Invoice -- is that these
 * Cycle query-builder methods are never unit-tested directly regardless;
 * see InvAuditLogRepositoryTest for what IS tested here). Add it back with
 * its own EntityReader-based test coverage once something actually reads
 * this table.
 *
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
     * @param array|InvAuditLog|null $invAuditLog
     * @psalm-param TEntity $invAuditLog
     * @throws Throwable
     */
    public function save(array|InvAuditLog|null $invAuditLog): void
    {
        $this->entityWriter->write([$invAuditLog]);
    }
}
