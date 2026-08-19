<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvSentLog;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvSentLog\InvSentLog;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvSentLog\InvSentLogRepository;
use App\Invoice\InvSentLog\InvSentLogService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers InvSentLogService: saveInvSentLog's client/invoice relation
 * persistence, date_sent parsing (including the invalid-date fallback to
 * 1901-01-01), and deleteInvSentLog.
 */
#[Test]
final class InvSentLogServiceTest
{
    private function makeService(
        ?InvSentLogRepository $repository = null,
        ?CR $cR = null,
        ?IR $iR = null,
    ): InvSentLogService {
        /** @var InvSentLogRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(InvSentLogRepository::class);
        /** @var CR&m\MockInterface $cR */
        $cR = $cR ?? m::mock(CR::class);
        /** @var IR&m\MockInterface $iR */
        $iR = $iR ?? m::mock(IR::class);
        return new InvSentLogService($repository, $cR, $iR);
    }

    public function saveInvSentLogSetsAllFieldsAndSaves(): void
    {
        $model = new InvSentLog();
        $array = [
            'client_id' => 4,
            'inv_id' => 9,
            'date_sent' => '2026-05-10',
        ];

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(4)->andReturn($client);

        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $e2 = $iR->shouldReceive('repoInvUnLoadedquery');
        $e2->once()->with(9)->andReturn($inv);

        /** @var InvSentLogRepository&m\MockInterface $repository */
        $repository = m::mock(InvSentLogRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $cR, $iR);
        $service->saveInvSentLog($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same($inv, $model->getInv());
        Assert::same(9, $model->reqInvId());
        Assert::same('2026-05-10', $model->getDateSent()->format('Y-m-d'));
    }

    public function saveInvSentLogFallsBackToDefaultDateWhenInvalid(): void
    {
        $model = new InvSentLog();
        $array = ['date_sent' => 'not-a-date'];

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');
        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoInvUnLoadedquery');

        /** @var InvSentLogRepository&m\MockInterface $repository */
        $repository = m::mock(InvSentLogRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR, $iR);
        $service->saveInvSentLog($model, $array);

        Assert::null($model->getClient());
        Assert::null($model->getInv());
        Assert::same('1901-01-01', $model->getDateSent()->format('Y-m-d'));
    }

    public function deleteInvSentLogCallsRepositoryDelete(): void
    {
        $model = new InvSentLog();

        /** @var InvSentLogRepository&m\MockInterface $repository */
        $repository = m::mock(InvSentLogRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteInvSentLog($model);
    }
}
