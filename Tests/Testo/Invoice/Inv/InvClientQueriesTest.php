<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvRepository;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\Select;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Translator\Translator;

/**
 * The two client-scoped invoice queries the QR-scan flow depends on: whether a client is an
 * established customer (has a paid invoice) and the client's latest planned draft that still has a
 * side waiting to be done. Select is mocked, so these cover the shape of each query.
 */
#[Test]
final class InvClientQueriesTest
{
    /**
     * @param Select<Inv>&m\MockInterface $select
     */
    private function repository(Select&m\MockInterface $select): InvRepository
    {
        /** @var EntityWriter&m\MockInterface $writer */
        $writer = m::mock(EntityWriter::class);
        /** @var Translator&m\MockInterface $translator */
        $translator = m::mock(Translator::class);
        return new InvRepository($select, $writer, $translator);
    }

    /**
     * @return Select<Inv>&m\MockInterface
     */
    private function select(): Select&m\MockInterface
    {
        /** @var Select<Inv>&m\MockInterface $select */
        $select = m::mock(Select::class);
        $select->shouldReceive('load')->andReturnSelf();
        $select->shouldReceive('where')->andReturnSelf();
        $select->shouldReceive('andWhere')->andReturnSelf();
        $select->shouldReceive('orderBy')->andReturnSelf();
        return $select;
    }

    public function aClientWithAPaidInvoiceIsAnEstablishedCustomer(): void
    {
        $select = $this->select();
        $select->shouldReceive('count')->andReturn(2);

        Assert::true($this->repository($select)->repoClientHasPaidInvoicequery(40));
    }

    public function aClientWithNoPaidInvoiceIsNot(): void
    {
        $select = $this->select();
        $select->shouldReceive('count')->andReturn(0);

        Assert::false($this->repository($select)->repoClientHasPaidInvoicequery(40));
    }

    public function thePendingDraftIsTheLatestDraftWithASideStillAllocated(): void
    {
        $inv = new Inv();
        $inv->setId(12);
        $select = $this->select();
        $select->shouldReceive('fetchOne')->andReturn($inv);

        Assert::same($this->repository($select)->repoClientPendingAllocatedDraftquery(40, new \DateTimeImmutable('today')), $inv);
    }

    public function noPendingDraftIsNull(): void
    {
        $select = $this->select();
        $select->shouldReceive('fetchOne')->andReturn(null);

        Assert::null($this->repository($select)->repoClientPendingAllocatedDraftquery(40, new \DateTimeImmutable('today')));
    }
}
