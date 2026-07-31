<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ItemLookup;

use App\Infrastructure\Persistence\ItemLookup\ItemLookup;
use App\Invoice\ItemLookup\ItemLookupRepository;
use App\Invoice\ItemLookup\ItemLookupService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ItemLookupService: saveItemLookup's field assignment and
 * deleteItemLookup.
 */
#[Test]
final class ItemLookupServiceTest
{
    private function makeService(?ItemLookupRepository $repository = null): ItemLookupService
    {
        $repository = $repository ?? m::mock(ItemLookupRepository::class);
        return new ItemLookupService($repository);
    }

    public function saveItemLookupSetsAllFieldsAndSaves(): void
    {
        $model = new ItemLookup();
        $array = [
            'name' => 'Consulting Hour',
            'description' => 'One hour of consulting',
            'price' => 125.50,
        ];

        /** @var ItemLookupRepository&m\MockInterface $repository */
        $repository = m::mock(ItemLookupRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveItemLookup($model, $array);

        Assert::same('Consulting Hour', $model->getName());
        Assert::same('One hour of consulting', $model->getDescription());
        Assert::same(125.50, $model->getPrice());
    }

    public function saveItemLookupSkipsFieldsWhenMissing(): void
    {
        $model = new ItemLookup();

        /** @var ItemLookupRepository&m\MockInterface $repository */
        $repository = m::mock(ItemLookupRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveItemLookup($model, []);

        Assert::same('', $model->getName());
        Assert::same('', $model->getDescription());
        Assert::null($model->getPrice());
    }

    public function deleteItemLookupCallsRepositoryDelete(): void
    {
        $model = new ItemLookup();

        /** @var ItemLookupRepository&m\MockInterface $repository */
        $repository = m::mock(ItemLookupRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteItemLookup($model);
    }
}
