<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\CategoryPrimary;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategoryPrimary\CategoryPrimaryService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CategoryPrimaryService: saveCategoryPrimary's field assignment and
 * deleteCategoryPrimary.
 */
#[Test]
final class CategoryPrimaryServiceTest
{
    private function makeService(?CategoryPrimaryRepository $repository = null): CategoryPrimaryService
    {
        $repository = $repository ?? m::mock(CategoryPrimaryRepository::class);
        return new CategoryPrimaryService($repository);
    }

    public function saveCategoryPrimarySetsNameAndSaves(): void
    {
        $model = new CategoryPrimary();
        $array = ['name' => 'Consulting'];

        /** @var CategoryPrimaryRepository&m\MockInterface $repository */
        $repository = m::mock(CategoryPrimaryRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveCategoryPrimary($model, $array);

        Assert::same('Consulting', $model->getName());
    }

    public function saveCategoryPrimarySkipsNameWhenMissing(): void
    {
        $model = new CategoryPrimary();

        /** @var CategoryPrimaryRepository&m\MockInterface $repository */
        $repository = m::mock(CategoryPrimaryRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveCategoryPrimary($model, []);

        Assert::same('', $model->getName());
    }

    public function deleteCategoryPrimaryCallsRepositoryDelete(): void
    {
        $model = new CategoryPrimary();

        /** @var CategoryPrimaryRepository&m\MockInterface $repository */
        $repository = m::mock(CategoryPrimaryRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteCategoryPrimary($model);
    }
}
