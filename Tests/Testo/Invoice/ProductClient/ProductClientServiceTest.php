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
        /** @var ProductClientRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(ProductClientRepository::class);
        /** @var PR&m\MockInterface $pR */
        $pR = $pR ?? m::mock(PR::class);
        /** @var CR&m\MockInterface $cR */
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

        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e = $pR->shouldReceive('repoProductquery');
        $e->once()->with(7)->andReturn($product);

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e2 = $cR->shouldReceive('repoClientquery');
        $e2->once()->with(3)->andReturn($client);

        /** @var ProductClientRepository&m\MockInterface $repository */
        $repository = m::mock(ProductClientRepository::class);
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

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->once()->with(3)->andReturn($client);

        /** @var ProductClientRepository&m\MockInterface $repository */
        $repository = m::mock(ProductClientRepository::class);
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $pR, $cR);
        $service->save($model, $array);

        Assert::null($model->getProduct());
    }

    public function syncFromInvItemsSkipsAlreadyAssociatedProducts(): void
    {
        /** @var ProductClientRepository&m\MockInterface $repository */
        $repository = m::mock(ProductClientRepository::class);
        $e = $repository->shouldReceive('isProductAssociatedWithClient');
        $e->once()->with(7, 3)->andReturn(true);
        $repository->shouldNotReceive('save');

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProductquery');
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        $service = $this->makeService($repository, $pR, $cR);
        $service->syncFromInvItems(3, [7]);
    }

    public function syncFromInvItemsSavesNewAssociationForEachUnassociatedProduct(): void
    {
        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->shouldReceive('repoClientquery');
        $e->twice()->with(3)->andReturn($client);

        /** @var Product&m\MockInterface $product1 */
        $product1 = m::mock(Product::class);
        /** @var Product&m\MockInterface $product2 */
        $product2 = m::mock(Product::class);
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e2 = $pR->shouldReceive('repoProductquery');
        $e2->once()->with(7)->andReturn($product1);
        $e3 = $pR->shouldReceive('repoProductquery');
        $e3->once()->with(9)->andReturn($product2);

        /** @var ProductClientRepository&m\MockInterface $repository */
        $repository = m::mock(ProductClientRepository::class);
        $e4 = $repository->shouldReceive('isProductAssociatedWithClient');
        $e4->once()->with(7, 3)->andReturn(false);
        $e5 = $repository->shouldReceive('isProductAssociatedWithClient');
        $e5->once()->with(9, 3)->andReturn(false);
        $e6 = $repository->shouldReceive('save');
        $e6->twice()->with(m::on(fn (mixed $arg): bool => $arg instanceof ProductClient));

        $service = $this->makeService($repository, $pR, $cR);
        $service->syncFromInvItems(3, [7, 9]);
    }

    public function deleteCallsRepositoryDelete(): void
    {
        $model = new ProductClient();

        /** @var ProductClientRepository&m\MockInterface $repository */
        $repository = m::mock(ProductClientRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->delete($model);
    }
}
