<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Generator;

use App\Infrastructure\Persistence\Gentor\Gentor;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Generator\GeneratorService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers GeneratorService: saveGenerator's field assignment (including the
 * always-set include boolean flags) and deleteGenerator.
 */
#[Test]
final class GeneratorServiceTest
{
    private function makeService(?GeneratorRepository $repository = null): GeneratorService
    {
        $repository = $repository ?? m::mock(GeneratorRepository::class);
        return new GeneratorService($repository);
    }

    public function saveGeneratorSetsAllFieldsAndSaves(): void
    {
        $model = new Gentor();
        $array = [
            'route_prefix' => 'inv',
            'route_suffix' => 'controller',
            'camelcase_capital_name' => 'Invoice',
            'small_singular_name' => 'invoice',
            'small_plural_name' => 'invoices',
            'namespace_path' => 'App\\Invoice',
            'controller_layout_dir' => 'invoice/layout',
            'controller_layout_dir_dot_path' => '@invoice/layout/main.php',
            'pre_entity_table' => 'inv',
            'flash_include' => '1',
            'created_include' => '1',
            'modified_include' => '1',
            'updated_include' => '1',
            'deleted_include' => '1',
        ];

        /** @var GeneratorRepository&m\MockInterface $repository */
        $repository = m::mock(GeneratorRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveGenerator($model, $array);

        Assert::same('inv', $model->getRoutePrefix());
        Assert::same('controller', $model->getRouteSuffix());
        Assert::same('Invoice', $model->getCamelcaseCapitalName());
        Assert::same('invoice', $model->getSmallSingularName());
        Assert::same('invoices', $model->getSmallPluralName());
        Assert::same('App\\Invoice', $model->getNamespacePath());
        Assert::same('invoice/layout', $model->getControllerLayoutDir());
        Assert::same('@invoice/layout/main.php', $model->getControllerLayoutDirDotPath());
        Assert::same('inv', $model->getPreEntityTable());
        Assert::true($model->isFlashInclude());
        Assert::true($model->isCreatedInclude());
        Assert::true($model->isModifiedInclude());
        Assert::true($model->isUpdatedInclude());
        Assert::true($model->isDeletedInclude());
    }

    public function saveGeneratorSetsBooleanFlagsToFalseWhenNotOne(): void
    {
        $model = new Gentor();
        $array = [
            'flash_include' => '0',
            'created_include' => '0',
            'modified_include' => '0',
            'updated_include' => '0',
            'deleted_include' => '0',
        ];

        /** @var GeneratorRepository&m\MockInterface $repository */
        $repository = m::mock(GeneratorRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveGenerator($model, $array);

        Assert::same('', $model->getRoutePrefix());
        Assert::false($model->isFlashInclude());
        Assert::false($model->isCreatedInclude());
    }

    public function deleteGeneratorCallsRepositoryDelete(): void
    {
        $model = new Gentor();

        /** @var GeneratorRepository&m\MockInterface $repository */
        $repository = m::mock(GeneratorRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteGenerator($model);
    }
}
