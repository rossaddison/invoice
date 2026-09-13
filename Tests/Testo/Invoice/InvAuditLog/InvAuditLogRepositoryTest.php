<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvAuditLog;

use App\Infrastructure\Persistence\InvAuditLog\InvAuditLog;
use App\Invoice\InvAuditLog\InvAuditLogRepository;
use Cycle\ORM\Select;
use Mockery as m;
use Testo\Test;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Covers InvAuditLogRepository::save() -- the only method this repository
 * actually has (see its own docblock for why the read side was removed
 * again before this PR). $select is a bare mock: save() never touches it,
 * only entityWriter, matching this codebase's own convention of never
 * unit-testing a Select\Repository subclass's *query-building* methods
 * directly (confirmed by grepping every other one in src/Invoice -- none
 * have a dedicated test file at all).
 */
#[Test]
final class InvAuditLogRepositoryTest
{
    public function saveWritesTheGivenEntityThroughTheEntityWriter(): void
    {
        $log = new InvAuditLog(inv_id: 5, action: 'created');

        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);
        $e = $entityWriter->shouldReceive('write');
        $e->once()->with([$log]);

        /** @var Select<InvAuditLog>&m\MockInterface $select */
        $select = m::mock(Select::class);

        $repository = new InvAuditLogRepository($select, $entityWriter);
        $repository->save($log);
    }

    public function saveAcceptsNullConsistentlyWithTheBaseRepositoryInterfaceItImplements(): void
    {
        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);
        $e = $entityWriter->shouldReceive('write');
        $e->once()->with([null]);

        /** @var Select<InvAuditLog>&m\MockInterface $select */
        $select = m::mock(Select::class);

        $repository = new InvAuditLogRepository($select, $entityWriter);
        $repository->save(null);
    }
}
