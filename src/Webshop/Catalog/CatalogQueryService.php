<?php

declare(strict_types=1);

namespace App\Webshop\Catalog;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\ProductImage\ProductImageRepository;

/**
 * Reads the product catalog directly from invoice's own repositories —
 * the in-process replacement for the standalone webshop app's
 * `ProductCatalogClient` (a Guzzle client against `GET /api/products`).
 * This is a near-direct port of the now-decommissioned
 * `App\Api\ProductsController`'s own `toArray()`/`firstImagePath()`/
 * `categoryPrimaryName()`/`categorySecondaryName()` mapping — same
 * non-zero-price filter (`ProductRepository::findAllPreloadedWithPrice()`,
 * the same one this app's own invoice/quote product-lookup modals use),
 * same static-file image path convention.
 */
final readonly class CatalogQueryService
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductImageRepository $productImageRepository,
        private CategoryPrimaryRepository $categoryPrimaryRepository,
        private CategorySecondaryRepository $categorySecondaryRepository,
    ) {
    }

    /** @return list<ProductListing> */
    public function listAll(): array
    {
        $listings = [];
        /** @var Product $product */
        foreach ($this->productRepository->findAllPreloadedWithPrice() as $product) {
            $listings[] = $this->toListing($product);
        }
        return $listings;
    }

    public function find(int $id): ?ProductListing
    {
        $product = $id > 0 ? $this->productRepository->repoProductquery($id) : null;
        if ($product === null || ($product->getProductPrice() ?? 0.00) <= 0.00) {
            return null;
        }
        return $this->toListing($product);
    }

    private function toListing(Product $product): ProductListing
    {
        $family = $product->getFamily();

        return new ProductListing(
            id: $product->reqId(),
            sku: $product->getProductSku(),
            name: $product->getProductName(),
            description: $product->getProductDescription(),
            price: $product->getProductPrice() ?? 0.00,
            unit: $product->getUnit()?->getUnitName(),
            imageUrl: $this->firstImagePath($product->reqId()),
            family: $family?->getFamilyName(),
            category: $this->categoryPrimaryName($family?->getCategoryPrimaryId()),
            subcategory: $this->categorySecondaryName($family?->getCategorySecondaryId()),
        );
    }

    /**
     * A relative, same-origin path (`/products/{file}`) — uploaded
     * product images are plain static files under `@public_product_images`
     * (aliased to `@public/products`), served directly by the web server,
     * never through the router at all. Same convention
     * `App\Api\ProductsController::firstImagePath()` used to build for the
     * cross-origin HTTP feed; now genuinely same-origin, so no base-URL
     * prefixing (and none of the CSP img-src cross-origin allowlisting
     * the standalone webshop app needed) is required at all.
     */
    private function firstImagePath(int $productId): ?string
    {
        /** @var ProductImage $image */
        foreach ($this->productImageRepository->repoProductImageProductquery($productId) as $image) {
            $fileName = $image->getFileNameNew();
            return $fileName !== '' ? '/products/' . rawurlencode($fileName) : null;
        }

        return null;
    }

    private function categoryPrimaryName(?int $categoryPrimaryId): ?string
    {
        if ($categoryPrimaryId === null) {
            return null;
        }

        $name = $this->categoryPrimaryRepository->repoCategoryPrimaryQuery($categoryPrimaryId)?->getName();
        return $name !== '' ? $name : null;
    }

    private function categorySecondaryName(?int $categorySecondaryId): ?string
    {
        if ($categorySecondaryId === null) {
            return null;
        }

        $name = $this->categorySecondaryRepository->repoCategorySecondaryQuery($categorySecondaryId)?->getName();
        return $name !== '' ? $name : null;
    }
}
