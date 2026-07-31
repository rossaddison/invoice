<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ProductCustom;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductCustom\ProductCustom;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductCustom\ProductCustomRepository;
use App\Invoice\ProductCustom\ProductCustomService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ProductCustomService: saveProductCustom's product/custom-field
 * relation persistence and field assignment, and deleteProductCustom.
 */
#[Test]
final class ProductCustomServiceTest
{
    private function makeService(
        ?ProductCustomRepository $repository = null,
        ?PR $pR = null,
        ?CFR $cfR = null,
    ): ProductCustomService {
        $repository = $repository ?? m::mock(ProductCustomRepository::class);
        $pR = $pR ?? m::mock(PR::class);
        $cfR = $cfR ?? m::mock(CFR::class);
        return new ProductCustomService($repository, $pR, $cfR);
    }

    public function saveProductCustomSetsAllFieldsAndSaves(): void
    {
        $model = new ProductCustom();
        $array = [
            'product_id' => 5,
            'custom_field_id' => 8,
            'value' => 'PO-9999',
        ];

        $product = m::mock(Product::class);
        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e */
        $e = $pR->shouldReceive('repoProductquery');
        $e->once()->with(5)->andReturn($product);

        $customField = m::mock(CustomField::class);
        $cfR = m::mock(CFR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $cfR->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(8)->andReturn($customField);

        $repository = m::mock(ProductCustomRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $pR, $cfR);
        $service->saveProductCustom($model, $array);

        Assert::same($product, $model->getProduct());
        Assert::same($customField, $model->getCustomField());
        Assert::same(5, $model->reqProductId());
        Assert::same(8, $model->reqCustomFieldId());
        Assert::same('PO-9999', $model->getValue());
    }

    public function deleteProductCustomCallsRepositoryDelete(): void
    {
        $model = new ProductCustom();

        /** @var ProductCustomRepository&m\MockInterface $repository */
        $repository = m::mock(ProductCustomRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteProductCustom($model);
    }
}
