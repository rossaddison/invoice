<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\Product;

use App\Infrastructure\Persistence\Product\Product;
use Testo\Assert;
use Testo\Test;

/**
 * Covers Product::availableStock() — the single source of truth every
 * consumer (storefront listing, cart, checkout's authoritative check)
 * calls rather than re-deriving the reorder_threshold arithmetic. See
 * that method's own docblock.
 *
 * @see Product
 */
#[Test]
final class ProductTest
{
    public function isNullWhenStockIsNotTracked(): void
    {
        $product = new Product(stock_quantity: 50.00, reorder_threshold: 5.00);
        $product->setTrackStock(false);

        Assert::null($product->availableStock());
    }

    public function equalsRawStockWhenNoReorderThresholdIsSet(): void
    {
        $product = new Product(stock_quantity: 10.00, reorder_threshold: null);
        $product->setTrackStock(true);

        Assert::same(10.00, $product->availableStock());
    }

    public function subtractsTheReorderThresholdFromRawStock(): void
    {
        // The exact example from the design discussion: 10 in stock, a
        // reorder threshold of 3, so 7 is actually available to the
        // public.
        $product = new Product(stock_quantity: 10.00, reorder_threshold: 3.00);
        $product->setTrackStock(true);

        Assert::same(7.00, $product->availableStock());
    }

    public function isZeroNotNegativeOnceStockIsInsideItsOwnBuffer(): void
    {
        $product = new Product(stock_quantity: 3.00, reorder_threshold: 3.00);
        $product->setTrackStock(true);

        Assert::same(0.00, $product->availableStock());
    }

    public function staysFlooredAtZeroEvenBelowTheBuffer(): void
    {
        $product = new Product(stock_quantity: 1.00, reorder_threshold: 3.00);
        $product->setTrackStock(true);

        Assert::same(0.00, $product->availableStock());
    }
}
