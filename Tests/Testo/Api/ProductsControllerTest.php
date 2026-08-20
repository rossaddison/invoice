<?php

declare(strict_types=1);

namespace Tests\Testo\Api;

use App\Api\ProductsController;
use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as cpR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as csR;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\ProductImage\ProductImageRepository as piR;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;

/**
 * Covers ProductsController — the read-only product feed webshop's
 * ProductCatalogClient calls. `image_path` is a relative path
 * (`/products/{file}`), not an absolute URL — see the controller's own
 * firstImagePath() docblock for why. `family`/`category`/`subcategory`
 * are resolved from the product's Family and its
 * category_primary_id/category_secondary_id — see the controller's own
 * categoryPrimaryName()/categorySecondaryName() docblocks.
 */
#[Test]
final class ProductsControllerTest
{
    private function product(int $id, string $name, float $price): Product
    {
        $product = new Product(product_name: $name, product_price: $price);
        $product->setId($id);
        return $product;
    }

    private function image(int $productId, string $fileName): ProductImage
    {
        $image = new ProductImage();
        $image->setProductId($productId);
        $image->setFileNameNew($fileName);
        return $image;
    }

    /** @param list<ProductImage> $images */
    private function fakeImageReader(array $images): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $generator = (static function () use ($images) {
            yield from $images;
        })();
        $reader->shouldReceive('getIterator')->andReturn($generator);
        return $reader;
    }

    /** @param list<Product> $products */
    private function fakeProductReader(array $products): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $generator = (static function () use ($products) {
            yield from $products;
        })();
        $reader->shouldReceive('getIterator')->andReturn($generator);
        return $reader;
    }

    public function indexIncludesTheImagePathWhenOneExists(): void
    {
        $product = $this->product(1, 'Widget', 9.99);

        /** @var pR&m\MockInterface $productRepository */
        $productRepository = m::mock(pR::class);
        $productRepository->shouldReceive('findAllPreloadedWithPrice')
            ->once()->andReturn($this->fakeProductReader([$product]));

        /** @var piR&m\MockInterface $productImageRepository */
        $productImageRepository = m::mock(piR::class);
        $productImageRepository->shouldReceive('repoProductImageProductquery')
            ->once()->with(1)
            ->andReturn($this->fakeImageReader([$this->image(1, 'widget photo.jpg')]));

        /** @var cpR&m\MockInterface $categoryPrimaryRepository */
        $categoryPrimaryRepository = m::mock(cpR::class);
        /** @var csR&m\MockInterface $categorySecondaryRepository */
        $categorySecondaryRepository = m::mock(csR::class);

        /** @var ResponseInterface&m\MockInterface $expectedResponse */
        $expectedResponse = m::mock(ResponseInterface::class);
        /** @var DataResponseFactoryInterface&m\MockInterface $responseFactory */
        $responseFactory = m::mock(DataResponseFactoryInterface::class);
        $responseFactory->shouldReceive('createResponse')->once()
            ->with(m::on($this->firstItemImagePathIs('/products/widget%20photo.jpg')))
            ->andReturn($expectedResponse);

        $controller = new ProductsController($responseFactory);

        Assert::same($expectedResponse, $controller->index(
            $productRepository,
            $productImageRepository,
            $categoryPrimaryRepository,
            $categorySecondaryRepository,
        ));
    }

    public function indexReturnsNullImagePathWhenNoneExists(): void
    {
        $product = $this->product(2, 'Gadget', 4.50);

        /** @var pR&m\MockInterface $productRepository */
        $productRepository = m::mock(pR::class);
        $productRepository->shouldReceive('findAllPreloadedWithPrice')
            ->once()->andReturn($this->fakeProductReader([$product]));

        /** @var piR&m\MockInterface $productImageRepository */
        $productImageRepository = m::mock(piR::class);
        $productImageRepository->shouldReceive('repoProductImageProductquery')
            ->once()->with(2)->andReturn($this->fakeImageReader([]));

        /** @var cpR&m\MockInterface $categoryPrimaryRepository */
        $categoryPrimaryRepository = m::mock(cpR::class);
        /** @var csR&m\MockInterface $categorySecondaryRepository */
        $categorySecondaryRepository = m::mock(csR::class);

        /** @var ResponseInterface&m\MockInterface $expectedResponse */
        $expectedResponse = m::mock(ResponseInterface::class);
        /** @var DataResponseFactoryInterface&m\MockInterface $responseFactory */
        $responseFactory = m::mock(DataResponseFactoryInterface::class);
        $responseFactory->shouldReceive('createResponse')->once()
            ->with(m::on($this->firstItemImagePathIs(null)))
            ->andReturn($expectedResponse);

        $controller = new ProductsController($responseFactory);

        Assert::same($expectedResponse, $controller->index(
            $productRepository,
            $productImageRepository,
            $categoryPrimaryRepository,
            $categorySecondaryRepository,
        ));
    }

    public function indexIncludesFamilyCategoryAndSubcategoryWhenPresent(): void
    {
        $family = new Family(
            family_name: 'Input Devices',
            category_primary_id: 10,
            category_secondary_id: 20,
        );
        $family->setId(5);

        $product = $this->product(4, 'Wireless Mouse', 19.99);
        $product->setFamily($family);

        /** @var pR&m\MockInterface $productRepository */
        $productRepository = m::mock(pR::class);
        $productRepository->shouldReceive('findAllPreloadedWithPrice')
            ->once()->andReturn($this->fakeProductReader([$product]));

        /** @var piR&m\MockInterface $productImageRepository */
        $productImageRepository = m::mock(piR::class);
        $productImageRepository->shouldReceive('repoProductImageProductquery')
            ->once()->with(4)->andReturn($this->fakeImageReader([]));

        $categoryPrimary = new CategoryPrimary(name: 'Computing');
        $categoryPrimary->setId(10);
        /** @var cpR&m\MockInterface $categoryPrimaryRepository */
        $categoryPrimaryRepository = m::mock(cpR::class);
        $categoryPrimaryRepository->shouldReceive('repoCategoryPrimaryQuery')
            ->once()->with(10)->andReturn($categoryPrimary);

        $categorySecondary = new CategorySecondary(category_primary_id: 10, name: 'Input Devices');
        $categorySecondary->setId(20);
        /** @var csR&m\MockInterface $categorySecondaryRepository */
        $categorySecondaryRepository = m::mock(csR::class);
        $categorySecondaryRepository->shouldReceive('repoCategorySecondaryQuery')
            ->once()->with(20)->andReturn($categorySecondary);

        /** @var ResponseInterface&m\MockInterface $expectedResponse */
        $expectedResponse = m::mock(ResponseInterface::class);
        /** @var DataResponseFactoryInterface&m\MockInterface $responseFactory */
        $responseFactory = m::mock(DataResponseFactoryInterface::class);
        $responseFactory->shouldReceive('createResponse')->once()
            ->with(m::on($this->firstItemTaxonomyIs('Input Devices', 'Computing', 'Input Devices')))
            ->andReturn($expectedResponse);

        $controller = new ProductsController($responseFactory);

        Assert::same($expectedResponse, $controller->index(
            $productRepository,
            $productImageRepository,
            $categoryPrimaryRepository,
            $categorySecondaryRepository,
        ));
    }

    private function firstItemImagePathIs(?string $expected): \Closure
    {
        return static function (mixed $items) use ($expected): bool {
            /** @var list<array{image_path: string|null}> $items */
            return $items[0]['image_path'] === $expected;
        };
    }

    private function firstItemTaxonomyIs(?string $family, ?string $category, ?string $subcategory): \Closure
    {
        return static function (mixed $items) use ($family, $category, $subcategory): bool {
            /** @var list<array{family: string|null, category: string|null, subcategory: string|null}> $items */
            return $items[0]['family'] === $family
                && $items[0]['category'] === $category
                && $items[0]['subcategory'] === $subcategory;
        };
    }

    public function showReturns404ForAnUnpricedProduct(): void
    {
        $product = $this->product(3, 'Free Sample', 0.00);

        /** @var pR&m\MockInterface $productRepository */
        $productRepository = m::mock(pR::class);
        $productRepository->shouldReceive('repoProductquery')->once()->with(3)->andReturn($product);

        /** @var piR&m\MockInterface $productImageRepository */
        $productImageRepository = m::mock(piR::class);
        $productImageRepository->shouldNotReceive('repoProductImageProductquery');

        /** @var cpR&m\MockInterface $categoryPrimaryRepository */
        $categoryPrimaryRepository = m::mock(cpR::class);
        $categoryPrimaryRepository->shouldNotReceive('repoCategoryPrimaryQuery');

        /** @var csR&m\MockInterface $categorySecondaryRepository */
        $categorySecondaryRepository = m::mock(csR::class);
        $categorySecondaryRepository->shouldNotReceive('repoCategorySecondaryQuery');

        /** @var CurrentRoute&m\MockInterface $currentRoute */
        $currentRoute = m::mock(CurrentRoute::class);
        $currentRoute->shouldReceive('getArgument')->with('id', '0')->andReturn('3');

        /** @var ResponseInterface&m\MockInterface $expectedResponse */
        $expectedResponse = m::mock(ResponseInterface::class);
        /** @var DataResponseFactoryInterface&m\MockInterface $responseFactory */
        $responseFactory = m::mock(DataResponseFactoryInterface::class);
        $responseFactory->shouldReceive('createResponse')
            ->once()->with('Product not found', 404)->andReturn($expectedResponse);

        $controller = new ProductsController($responseFactory);

        Assert::same(
            $expectedResponse,
            $controller->show(
                $productRepository,
                $productImageRepository,
                $categoryPrimaryRepository,
                $categorySecondaryRepository,
                $currentRoute,
            ),
        );
    }
}
