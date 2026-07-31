<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\GeneratorRelation;

use App\Infrastructure\Persistence\GentorRelation\GentorRelation;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use App\Invoice\GeneratorRelation\GeneratorRelationService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers GeneratorRelationService: saveGeneratorRelation's field assignment
 * and deleteGeneratorRelation.
 */
#[Test]
final class GeneratorRelationServiceTest
{
    private function makeService(?GeneratorRelationRepository $repository = null): GeneratorRelationService
    {
        $repository = $repository ?? m::mock(GeneratorRelationRepository::class);
        return new GeneratorRelationService($repository);
    }

    public function saveGeneratorRelationSetsAllFieldsAndSaves(): void
    {
        $model = new GentorRelation();
        $array = [
            'lowercasename' => 'invoice',
            'camelcasename' => 'Invoice',
            'view_field_name' => 'number',
            'gentor_id' => 11,
        ];

        /** @var GeneratorRelationRepository&m\MockInterface $repository */
        $repository = m::mock(GeneratorRelationRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveGeneratorRelation($model, $array);

        Assert::same('invoice', $model->getLowercaseName());
        Assert::same('Invoice', $model->getCamelcaseName());
        Assert::same('number', $model->getViewFieldName());
        Assert::same(11, $model->reqGentorId());
    }

    public function saveGeneratorRelationSkipsFieldsWhenMissing(): void
    {
        $model = new GentorRelation();

        /** @var GeneratorRelationRepository&m\MockInterface $repository */
        $repository = m::mock(GeneratorRelationRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveGeneratorRelation($model, []);

        Assert::same('', $model->getLowercaseName());
        Assert::null($model->reqGentorId());
    }

    public function deleteGeneratorRelationCallsRepositoryDelete(): void
    {
        $model = new GentorRelation();

        /** @var GeneratorRelationRepository&m\MockInterface $repository */
        $repository = m::mock(GeneratorRelationRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteGeneratorRelation($model);
    }
}
