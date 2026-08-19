<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UnitPeppol;

use App\Infrastructure\Persistence\Unit\Unit;
use App\Infrastructure\Persistence\UnitPeppol\UnitPeppol;
use App\Invoice\Unit\UnitRepository as UR;
use App\Invoice\UnitPeppol\UnitPeppolRepository;
use App\Invoice\UnitPeppol\UnitPeppolService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers UnitPeppolService: saveUnitPeppol's unit-relation persistence and
 * field assignment, and deleteUnitPeppol.
 */
#[Test]
final class UnitPeppolServiceTest
{
    private function makeService(
        ?UnitPeppolRepository $repository = null,
        ?UR $uR = null,
    ): UnitPeppolService {
        /** @var UnitPeppolRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(UnitPeppolRepository::class);
        /** @var UR&m\MockInterface $uR */
        $uR = $uR ?? m::mock(UR::class);
        return new UnitPeppolService($repository, $uR);
    }

    public function saveUnitPeppolSetsAllFieldsAndSaves(): void
    {
        $model = new UnitPeppol();
        $array = [
            'id' => 3,
            'unit_id' => 6,
            'code' => 'HUR',
            'name' => 'Hour',
            'description' => 'Unit of time',
        ];

        /** @var Unit&m\MockInterface $unit */
        $unit = m::mock(Unit::class);
        $reqIdExpectation = $unit->shouldReceive('reqId');
        $reqIdExpectation->andReturn(6);
        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        $e = $uR->shouldReceive('repoUnitquery');
        $e->once()->with(6)->andReturn($unit);

        /** @var UnitPeppolRepository&m\MockInterface $repository */
        $repository = m::mock(UnitPeppolRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $uR);
        $service->saveUnitPeppol($model, $array);

        Assert::same(3, $model->reqId());
        Assert::same($unit, $model->getUnit());
        Assert::same(6, $model->reqUnitId());
        Assert::same('HUR', $model->getCode());
        Assert::same('Hour', $model->getName());
        Assert::same('Unit of time', $model->getDescription());
    }

    public function deleteUnitPeppolCallsRepositoryDelete(): void
    {
        $model = new UnitPeppol();

        /** @var UnitPeppolRepository&m\MockInterface $repository */
        $repository = m::mock(UnitPeppolRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteUnitPeppol($model);
    }
}
