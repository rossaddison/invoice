<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ProductProperty;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductProperty\ProductProperty;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductProperty\ProductPropertyRepository;
use App\Invoice\ProductProperty\ProductPropertyService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ProductPropertyService: saveProductProperty's product-relation
 * persistence and field assignment, and deleteProductProperty.
 */
#[Test]
final class ProductPropertyServiceTest
{
    private function makeService(
        ?ProductPropertyRepository $repository = null,
        ?PR $pR = null,
    ): ProductPropertyService {
        /** @var ProductPropertyRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(ProductPropertyRepository::class);
        /** @var PR&m\MockInterface $pR */
        $pR = $pR ?? m::mock(PR::class);
        return new ProductPropertyService($repository, $pR);
    }

    public function saveProductPropertySetsAllFieldsAndSaves(): void
    {
        $model = new ProductProperty();
        $array = [
            'product_id' => 7,
            'name' => 'Color',
            'value' => 'Blue',
        ];

        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e = $pR->shouldReceive('repoProductquery');
        $e->once()->with(7)->andReturn($product);

        /** @var ProductPropertyRepository&m\MockInterface $repository */
        $repository = m::mock(ProductPropertyRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $pR);
        $service->saveProductProperty($model, $array);

        Assert::same($product, $model->getProduct());
        Assert::same(7, $model->reqProductId());
        Assert::same('Color', $model->getName());
        Assert::same('Blue', $model->getValue());
    }

    public function saveProductPropertySkipsFieldsWhenMissing(): void
    {
        $model = new ProductProperty();

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');

        /** @var ProductPropertyRepository&m\MockInterface $repository */
        $repository = m::mock(ProductPropertyRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $pR);
        $service->saveProductProperty($model, []);

        Assert::null($model->getProduct());
        Assert::same('', $model->getName());
    }

    public function deleteProductPropertyCallsRepositoryDelete(): void
    {
        $model = new ProductProperty();

        /** @var ProductPropertyRepository&m\MockInterface $repository */
        $repository = m::mock(ProductPropertyRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteProductProperty($model);
    }
}
