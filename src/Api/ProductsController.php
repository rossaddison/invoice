<?php

declare(strict_types=1);

namespace App\Api;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\ProductImage\ProductImageRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;

/**
 * Read-only product catalog feed for external partners (e.g. the future
 * headless webshop storefront — see
 * docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md), gated by
 * ApiKeyAuthMiddleware. Only products with a real price
 * (`ProductRepository::findAllPreloadedWithPrice()`, the same non-zero-price
 * filter this app's own invoice/quote product-lookup modals already use)
 * are exposed — zero-priced/internal-only catalog rows never appear here.
 */
final readonly class ProductsController
{
    public function __construct(private DataResponseFactoryInterface $responseFactory)
    {
    }

    public function index(
        ProductRepository $productRepository,
        ProductImageRepository $productImageRepository,
        CategoryPrimaryRepository $categoryPrimaryRepository,
        CategorySecondaryRepository $categorySecondaryRepository,
    ): ResponseInterface {
        $items = [];
        /** @var Product $product */
        foreach ($productRepository->findAllPreloadedWithPrice() as $product) {
            $items[] = $this->toArray($product, $productImageRepository, $categoryPrimaryRepository, $categorySecondaryRepository);
        }

        return $this->responseFactory->createResponse($items);
    }

    public function show(
        ProductRepository $productRepository,
        ProductImageRepository $productImageRepository,
        CategoryPrimaryRepository $categoryPrimaryRepository,
        CategorySecondaryRepository $categorySecondaryRepository,
        CurrentRoute $currentRoute,
    ): ResponseInterface {
        $id = (int) $currentRoute->getArgument('id', '0');
        $product = $id > 0 ? $productRepository->repoProductquery($id) : null;

        if ($product === null || ($product->getProductPrice() ?? 0.00) <= 0.00) {
            return $this->responseFactory->createResponse('Product not found', 404);
        }

        return $this->responseFactory->createResponse(
            $this->toArray($product, $productImageRepository, $categoryPrimaryRepository, $categorySecondaryRepository),
        );
    }

    /**
     * @return array{id: int, sku: ?string, name: ?string, description: ?string,
     *     price: float, unit: ?string, image_path: ?string, family: ?string,
     *     category: ?string, subcategory: ?string}
     */
    private function toArray(
        Product $product,
        ProductImageRepository $productImageRepository,
        CategoryPrimaryRepository $categoryPrimaryRepository,
        CategorySecondaryRepository $categorySecondaryRepository,
    ): array {
        $family = $product->getFamily();

        return [
            'id' => $product->reqId(),
            'sku' => $product->getProductSku(),
            'name' => $product->getProductName(),
            'description' => $product->getProductDescription(),
            'price' => $product->getProductPrice() ?? 0.00,
            'unit' => $product->getUnit()?->getUnitName(),
            'image_path' => $this->firstImagePath($product->reqId(), $productImageRepository),
            'family' => $family?->getFamilyName(),
            'category' => $this->categoryPrimaryName($family?->getCategoryPrimaryId(), $categoryPrimaryRepository),
            'subcategory' => $this->categorySecondaryName($family?->getCategorySecondaryId(), $categorySecondaryRepository),
        ];
    }

    /**
     * A relative path (`/products/{file}`), not an absolute URL — uploaded
     * product images are plain static files under `@public_product_images`
     * (`SettingFileFolderTrait::getProductimagesFilesFolderAliases()`,
     * aliased to `@public/products`), served directly by the web server,
     * never through the router at all. The caller (e.g. webshop) already
     * knows this app's own base URL, so it's simpler and more robust to
     * hand back just the path than to reconstruct an absolute URL here
     * from the current request, which could disagree with the public URL
     * behind a reverse proxy.
     */
    private function firstImagePath(int $productId, ProductImageRepository $productImageRepository): ?string
    {
        /** @var ProductImage $image */
        foreach ($productImageRepository->repoProductImageProductquery($productId) as $image) {
            $fileName = $image->getFileNameNew();
            return $fileName !== '' ? '/products/' . rawurlencode($fileName) : null;
        }

        return null;
    }

    private function categoryPrimaryName(?int $categoryPrimaryId, CategoryPrimaryRepository $categoryPrimaryRepository): ?string
    {
        if ($categoryPrimaryId === null) {
            return null;
        }

        $name = $categoryPrimaryRepository->repoCategoryPrimaryQuery($categoryPrimaryId)?->getName();
        return $name !== '' ? $name : null;
    }

    private function categorySecondaryName(?int $categorySecondaryId, CategorySecondaryRepository $categorySecondaryRepository): ?string
    {
        if ($categorySecondaryId === null) {
            return null;
        }

        $name = $categorySecondaryRepository->repoCategorySecondaryQuery($categorySecondaryId)?->getName();
        return $name !== '' ? $name : null;
    }
}
