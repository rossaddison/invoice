<?php

declare(strict_types=1);

namespace App\Webshop\Catalog;

/**
 * Checkbox/range-driven catalog filtering — category / subcategory /
 * family (the same three-level taxonomy `CatalogQueryService` exposes)
 * plus a min/max price range, plus the navbar's search box ($search).
 * Pure GET query params (`category[]`/`subcategory[]`/`family[]`/
 * `min_price`/`max_price`/`q`) — both the sidebar filter form and the
 * navbar search form submit to the same `shop/catalog/index` route, each
 * carrying the other's current value forward as a hidden field (see
 * `resources/views/shop/catalog/index.php`/the storefront layout) so
 * neither one clobbers what the other already narrowed.
 *
 * A checkbox group passes a product only if that group has no boxes
 * checked at all (nothing selected = no restriction) or the product's
 * own value for that field is one of the checked ones. Uncategorized
 * products (a `null` field) always pass an inactive group but never pass
 * an active one — checking any Category box hides products with no
 * category, which is the expected "narrow the list" behaviour.
 *
 * Filters over an already-fetched flat in-memory list
 * (`CatalogQueryService::listAll()`) rather than a server-side SQL range/
 * category filter — deliberate, not a placeholder: this is the exact
 * proven behaviour the standalone webshop app already had (see
 * docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md), and moving from an HTTP
 * round trip to a direct in-process repository call is a strict
 * performance improvement even keeping the same shape. Revisit with real
 * `ProductRepository` filter methods only once catalog size actually
 * warrants it.
 */
final readonly class ProductFilter
{
    /**
     * @param list<string> $categories
     * @param list<string> $subcategories
     * @param list<string> $families
     */
    public function __construct(
        public array $categories = [],
        public array $subcategories = [],
        public array $families = [],
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public string $search = '',
    ) {
    }

    /**
     * @param array<array-key, mixed> $queryParams
     */
    public static function fromQueryParams(array $queryParams): self
    {
        return new self(
            categories: self::stringList($queryParams['category'] ?? null),
            subcategories: self::stringList($queryParams['subcategory'] ?? null),
            families: self::stringList($queryParams['family'] ?? null),
            minPrice: self::nonNegativeFloat($queryParams['min_price'] ?? null),
            maxPrice: self::nonNegativeFloat($queryParams['max_price'] ?? null),
            search: self::trimmedString($queryParams['q'] ?? null),
        );
    }

    /**
     * @param list<ProductListing> $products
     * @return list<ProductListing>
     */
    public function apply(array $products): array
    {
        return array_values(array_filter(
            $products,
            fn (ProductListing $product): bool => $this->matches($this->categories, $product->category)
                && $this->matches($this->subcategories, $product->subcategory)
                && $this->matches($this->families, $product->family)
                && ($this->minPrice === null || $product->price >= $this->minPrice)
                && ($this->maxPrice === null || $product->price <= $this->maxPrice)
                && $this->matchesSearch($product),
        ));
    }

    public function isEmpty(): bool
    {
        return $this->categories === []
            && $this->subcategories === []
            && $this->families === []
            && $this->minPrice === null
            && $this->maxPrice === null
            && $this->search === '';
    }

    /** @param list<string> $selected */
    private function matches(array $selected, ?string $value): bool
    {
        return $selected === [] || ($value !== null && in_array($value, $selected, true));
    }

    /**
     * A blank search term matches everything. Otherwise a case-insensitive
     * substring match against name, SKU, or description — a product only
     * needs to hit on one of the three, not all of them.
     */
    private function matchesSearch(ProductListing $product): bool
    {
        if ($this->search === '') {
            return true;
        }

        foreach ([$product->name, $product->sku, $product->description] as $field) {
            if ($field !== null && mb_stripos($field, $this->search) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $strings = [];
        /** @var mixed $item */
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }
        return $strings;
    }

    private static function trimmedString(mixed $value): string
    {
        return is_string($value) ? mb_substr(trim($value), 0, 200) : '';
    }

    /**
     * A blank/missing/non-numeric/negative submission is treated the same
     * as "no bound set" rather than as a validation error — a price
     * filter degrading to "no restriction" is harmless, unlike silently
     * dropping a category the customer actually meant to check.
     */
    private static function nonNegativeFloat(mixed $value): ?float
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }
        if (is_string($value) && ($value === '' || !is_numeric($value))) {
            return null;
        }

        $float = (float) $value;
        return $float >= 0.0 ? $float : null;
    }
}
