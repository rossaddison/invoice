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
        $repository = $repository ?? m::mock(ProductPropertyRepository::class);
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

        $product = m::mock(Product::class);
        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e */
        $e = $pR->shouldReceive('repoProductquery');
        $e->once()->with(7)->andReturn($product);

        $repository = m::mock(ProductPropertyRepository::class);
        /** @var \Mockery\Expectation $e2 */
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

        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');

        $repository = m::mock(ProductPropertyRepository::class);
        /** @var \Mockery\Expectation $e */
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
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteProductProperty($model);
    }
}
