<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation\GatewayStatus;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRepository;
use Cycle\ORM\Select;
use Mockery as m;
use Testo\Test;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Covers GatewayStatusRepository::delete() directly -- every call site in
 * GatewayStatusServiceTest mocks the whole repository, so delete()'s own
 * body (a one-line pass-through to EntityWriter::delete()) is otherwise
 * never actually executed. $select is a bare mock: delete() never touches
 * it, only entityWriter, matching InvAuditLogRepositoryTest's own
 * established pattern for this class of repository test.
 */
#[Test]
final class GatewayStatusRepositoryTest
{
    public function deleteRemovesTheGivenEntityThroughTheEntityWriter(): void
    {
        $status = new GatewayStatus();
        $status->setGatewayKey('amazon_pay');
        $status->setName('Amazon Pay');

        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);
        $e = $entityWriter->shouldReceive('delete');
        $e->once()->with([$status]);

        /** @var Select<GatewayStatus>&m\MockInterface $select */
        $select = m::mock(Select::class);

        $repository = new GatewayStatusRepository($select, $entityWriter);
        $repository->delete($status);
    }
}
