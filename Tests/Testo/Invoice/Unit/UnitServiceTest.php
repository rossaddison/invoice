<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Unit;

use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Unit\UnitRepository;
use App\Invoice\Unit\UnitService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers UnitService: saveUnit's field assignment and deleteUnit.
 */
#[Test]
final class UnitServiceTest
{
    private function makeService(?UnitRepository $repository = null): UnitService
    {
        /** @var UnitRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(UnitRepository::class);
        return new UnitService($repository);
    }

    public function saveUnitSetsAllFieldsAndSaves(): void
    {
        $model = new Unit();
        $array = [
            'unit_name' => 'Hour',
            'unit_name_plrl' => 'Hours',
        ];

        /** @var UnitRepository&m\MockInterface $repository */
        $repository = m::mock(UnitRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveUnit($model, $array);

        Assert::same('Hour', $model->getUnitName());
        Assert::same('Hours', $model->getUnitNamePlrl());
    }

    public function saveUnitSkipsFieldsWhenMissing(): void
    {
        $model = new Unit();

        /** @var UnitRepository&m\MockInterface $repository */
        $repository = m::mock(UnitRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveUnit($model, []);

        Assert::same('', $model->getUnitName());
        Assert::same('', $model->getUnitNamePlrl());
    }

    public function deleteUnitCallsRepositoryDelete(): void
    {
        $model = new Unit();

        /** @var UnitRepository&m\MockInterface $repository */
        $repository = m::mock(UnitRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteUnit($model);
    }
}
