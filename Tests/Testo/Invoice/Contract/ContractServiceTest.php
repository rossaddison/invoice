<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Contract;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Contract\Contract;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Contract\ContractRepository;
use App\Invoice\Contract\ContractService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ContractService: saveContract's client-relation persistence, period
 * date parsing (including the invalid-date fallback that keeps the
 * constructor default), field assignment, and deleteContract.
 */
#[Test]
final class ContractServiceTest
{
    private function formatPeriodStart(Contract $model): string
    {
        return $model->getPeriodStart()->format('Y-m-d');
    }

    private function formatPeriodEnd(Contract $model): string
    {
        return $model->getPeriodEnd()->format('Y-m-d');
    }

    private function makeService(
        ?ContractRepository $repository = null,
        ?CR $cR = null,
    ): ContractService {
        /** @var ContractRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(ContractRepository::class);
        /** @var CR&m\MockInterface $cR */
        $cR = $cR ?? m::mock(CR::class);
        return new ContractService($repository, $cR);
    }

    public function saveContractSetsAllFieldsAndSaves(): void
    {
        $model = new Contract();
        $array = [
            'client_id' => 6,
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'name' => 'Annual Support',
            'reference' => 'REF-001',
        ];

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(6)->andReturn($client);

        /** @var ContractRepository&m\MockInterface $repository */
        $repository = m::mock(ContractRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveContract($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same(6, $model->reqClientId());
        Assert::same('2026-01-01', $this->formatPeriodStart($model));
        Assert::same('2026-12-31', $this->formatPeriodEnd($model));
        Assert::same('Annual Support', $model->getName());
        Assert::same('REF-001', $model->getReference());
    }

    public function saveContractKeepsDefaultPeriodWhenDateInvalid(): void
    {
        $model = new Contract();
        $defaultStart = $this->formatPeriodStart($model);
        $defaultEnd = $this->formatPeriodEnd($model);
        $array = [
            'period_start' => 'not-a-date',
            'period_end' => 'not-a-date',
        ];

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        /** @var ContractRepository&m\MockInterface $repository */
        $repository = m::mock(ContractRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveContract($model, $array);

        Assert::same($defaultStart, $this->formatPeriodStart($model));
        Assert::same($defaultEnd, $this->formatPeriodEnd($model));
        Assert::null($model->getClient());
        Assert::same('', $model->getName());
        Assert::same('', $model->getReference());
    }

    public function deleteContractCallsRepositoryDelete(): void
    {
        $model = new Contract();

        /** @var ContractRepository&m\MockInterface $repository */
        $repository = m::mock(ContractRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteContract($model);
    }
}
