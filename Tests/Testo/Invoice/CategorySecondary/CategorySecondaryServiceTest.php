<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\CategorySecondary;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as CPR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CategorySecondaryService: saveCategorySecondary's category-primary
 * relation persistence and field assignment, and deleteCategorySecondary.
 */
#[Test]
final class CategorySecondaryServiceTest
{
    private function makeService(
        ?CategorySecondaryRepository $repository = null,
        ?CPR $cpR = null,
    ): CategorySecondaryService {
        $repository = $repository ?? m::mock(CategorySecondaryRepository::class);
        $cpR = $cpR ?? m::mock(CPR::class);
        return new CategorySecondaryService($repository, $cpR);
    }

    public function saveCategorySecondarySetsRelationAndFieldsAndSaves(): void
    {
        $model = new CategorySecondary();
        $array = ['category_primary_id' => 4, 'name' => 'Cloud Hosting'];

        $categoryPrimary = m::mock(CategoryPrimary::class);

        /** @var CPR&m\MockInterface $cpR */
        $cpR = m::mock(CPR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cpR->expects('repoCategoryPrimaryQuery');
        $e->once()->with(4)->andReturn($categoryPrimary);

        /** @var CategorySecondaryRepository&m\MockInterface $repository */
        $repository = m::mock(CategorySecondaryRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->expects('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cpR);
        $service->saveCategorySecondary($model, $array);

        Assert::same($categoryPrimary, $model->getCategoryPrimary());
        Assert::same(4, $model->reqCategoryPrimaryId());
        Assert::same('Cloud Hosting', $model->getName());
    }

    public function saveCategorySecondarySkipsRelationWhenIdMissing(): void
    {
        $model = new CategorySecondary();

        /** @var CPR&m\MockInterface $cpR */
        $cpR = m::mock(CPR::class);
        $cpR->shouldNotReceive('repoCategoryPrimaryQuery');

        /** @var CategorySecondaryRepository&m\MockInterface $repository */
        $repository = m::mock(CategorySecondaryRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cpR);
        $service->saveCategorySecondary($model, []);

        Assert::null($model->getCategoryPrimary());
    }

    public function deleteCategorySecondaryCallsRepositoryDelete(): void
    {
        $model = new CategorySecondary();

        /** @var CategorySecondaryRepository&m\MockInterface $repository */
        $repository = m::mock(CategorySecondaryRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteCategorySecondary($model);
    }
}
