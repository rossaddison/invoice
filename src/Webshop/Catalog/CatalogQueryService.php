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
 * static-file image path convention. Unlike the old cross-origin feed,
 * this now also gates on `available_on_webshop`
 * (`ProductRepository::findAllPreloadedWithPriceAvailableOnWebshop()`,
 * distinct from `findAllPreloadedWithPrice()`, which the staff
 * invoice/quote product-lookup modals still use and which deliberately
 * ignores this flag) — a B2B-only priced Product must never appear here,
 * including via a direct `find($id)` lookup by URL.
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
        foreach ($this->productRepository->findAllPreloadedWithPriceAvailableOnWebshop() as $product) {
            $listings[] = $this->toListing($product);
        }
        return $listings;
    }

    public function find(int $id): ?ProductListing
    {
        $product = $id > 0 ? $this->productRepository->repoProductquery($id) : null;
        if ($product === null
            || ($product->getProductPrice() ?? 0.00) <= 0.00
            || !$product->isAvailableOnWebshop()
        ) {
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
            // Retail (webshop) price wins when actually set, else falls
            // back to product_price ("wholesale") — see
            // Product::webshopPrice()'s own docblock. App\Api\
            // OrderService::addOrderItem() bills the resulting InvItem at
            // this exact same value, so a customer is never shown one
            // price here and charged another there.
            price: $product->webshopPrice(),
            unit: $product->getUnit()?->getUnitName(),
            imageUrl: $this->firstImagePath($product->reqId()),
            family: $family?->getFamilyName(),
            category: $this->categoryPrimaryName($family?->getCategoryPrimaryId()),
            subcategory: $this->categorySecondaryName($family?->getCategorySecondaryId()),
            tradePrice: $product->getProductPrice() ?? 0.00,
            tradeMinOrderQty: $product->getTradeMinOrderQty(),
            tradeMinOrderSpend: $product->getTradeMinOrderSpend(),
            availableStock: $product->availableStock(),
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
