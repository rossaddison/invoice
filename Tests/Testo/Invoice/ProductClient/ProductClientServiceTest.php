<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ProductClient;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductClient\ProductClientRepository;
use App\Invoice\ProductClient\ProductClientService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ProductClientService: save's relation persistence and updated_at
 * parsing, syncFromInvItems' dedupe-before-create behaviour, and delete.
 */
#[Test]
final class ProductClientServiceTest
{
    private function makeService(
        ?ProductClientRepository $repository = null,
        ?PR $pR = null,
        ?CR $cR = null,
    ): ProductClientService {
        $repository = $repository ?? m::mock(ProductClientRepository::class);
        $pR = $pR ?? m::mock(PR::class);
        $cR = $cR ?? m::mock(CR::class);
        return new ProductClientService($repository, $pR, $cR);
    }

    public function saveSetsAllFieldsAndSaves(): void
    {
        $model = new ProductClient();
        $array = [
            'product_id' => 7,
            'client_id' => 3,
            'updated_at' => '2026-06-01',
        ];

        $product = m::mock(Product::class);
        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e */
        $e = $pR->shouldReceive('repoProductquery');
        $e->once()->with(7)->andReturn($product);

        $client = m::mock(Client::class);
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $cR->shouldReceive('repoClientquery');
        $e2->once()->with(3)->andReturn($client);

        $repository = m::mock(ProductClientRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $pR, $cR);
        $service->save($model, $array);

        Assert::same($product, $model->getProduct());
        Assert::same($client, $model->getClient());
        Assert::same(7, $model->getProductId());
        Assert::same(3, $model->getClientId());
        Assert::same('2026-06-01', $model->getUpdatedAt()->format('Y-m-d'));
    }

    public function saveSkipsProductRelationWhenProductIdMissing(): void
    {
        $model = new ProductClient();
        $array = ['client_id' => 3, 'updated_at' => '2026-06-01'];

        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');

        $client = m::mock(Client::class);
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(3)->andReturn($client);

        $repository = m::mock(ProductClientRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $pR, $cR);
        $service->save($model, $array);

        Assert::null($model->getProduct());
    }

    public function syncFromInvItemsSkipsAlreadyAssociatedProducts(): void
    {
        $repository = m::mock(ProductClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('isProductAssociatedWithClient');
        $e->once()->with(7, 3)->andReturn(true);
        $repository->shouldNotReceive('save');

        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');
        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        $service = $this->makeService($repository, $pR, $cR);
        $service->syncFromInvItems(3, [7]);
    }

    public function syncFromInvItemsSavesNewAssociationForEachUnassociatedProduct(): void
    {
        $client = m::mock(Client::class);
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cR->shouldReceive('repoClientquery');
        $e->twice()->with(3)->andReturn($client);

        $product1 = m::mock(Product::class);
        $product2 = m::mock(Product::class);
        $pR = m::mock(PR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $pR->shouldReceive('repoProductquery');
        $e2->once()->with(7)->andReturn($product1);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $pR->shouldReceive('repoProductquery');
        $e3->once()->with(9)->andReturn($product2);

        $repository = m::mock(ProductClientRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $repository->shouldReceive('isProductAssociatedWithClient');
        $e4->once()->with(7, 3)->andReturn(false);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $repository->shouldReceive('isProductAssociatedWithClient');
        $e5->once()->with(9, 3)->andReturn(false);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $repository->shouldReceive('save');
        $e6->twice()->with(m::on(fn (mixed $arg): bool => $arg instanceof ProductClient));

        $service = $this->makeService($repository, $pR, $cR);
        $service->syncFromInvItems(3, [7, 9]);
    }

    public function deleteCallsRepositoryDelete(): void
    {
        $model = new ProductClient();

        $repository = m::mock(ProductClientRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->delete($model);
    }
}
