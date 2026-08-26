<?php

declare(strict_types=1);

namespace App\Webshop\Catalog;

use App\Infrastructure\Persistence\Product\Product;

/**
 * A read-only storefront projection of one catalog `Product` entity —
 * named `ProductListing` rather than `Product` to avoid any confusion
 * with `App\Infrastructure\Persistence\Product\Product` (the actual
 * Cycle ORM entity this is built from) even though the two live in
 * different namespaces. Built by `CatalogQueryService`, which is also
 * where the equivalent of the old `App\Api\ProductsController::toArray()`
 * mapping logic (name/sku/price/family/category/subcategory/image) now
 * lives in-process.
 */
final readonly class ProductListing
{
    public function __construct(
        public int $id,
        public ?string $sku,
        public ?string $name,
        public ?string $description,
        public float $price,
        public ?string $unit,
        public ?string $imageUrl,
        public ?string $family,
        public ?string $category,
        public ?string $subcategory,
        // Trade (B2B/wholesale) ordering terms — always $product_price
        // ("wholesale"), never $price above (which may be $retail_price).
        // $tradeMinOrderQty/$tradeMinOrderSpend both null means this
        // product has no trade terms configured, so the storefront's
        // "Trade Pricing" button (resources/views/shop/catalog/view.php)
        // doesn't render at all — see Product::$trade_min_order_qty.
        public float $tradePrice = 0.00,
        public ?int $tradeMinOrderQty = null,
        public ?float $tradeMinOrderSpend = null,
        // Product::availableStock() — null means stock isn't tracked for
        // this product at all (unlimited, nothing shown). Otherwise the
        // real "stock left" figure after the reserved reorder_threshold
        // buffer is set aside; 0.00 means out of stock to the public even
        // though physical stock inside the buffer may still exist.
        public ?float $availableStock = null,
    ) {
    }

    public function hasTradeTerms(): bool
    {
        return $this->tradeMinOrderQty !== null || $this->tradeMinOrderSpend !== null;
    }

    public function isOutOfStock(): bool
    {
        return $this->availableStock !== null && $this->availableStock <= 0.0;
    }

    public function displayName(): string
    {
        return $this->name ?? ('Product #' . $this->id);
    }
}
