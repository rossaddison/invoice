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
    ) {
    }

    public function displayName(): string
    {
        return $this->name ?? ('Product #' . $this->id);
    }
}
