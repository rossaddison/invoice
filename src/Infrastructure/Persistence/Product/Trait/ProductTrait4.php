<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Product\ProductRepository as PR;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method int requireId(?int $id, string $context)
 */
trait ProductTrait4
{
    // Step 3: Get all the productClients that are associated with this product
    public function getProductClients(): ArrayCollection
    {
        return $this->client_associations;
    }

    public function isAvailableOnWebshop(): bool
    {
        return $this->available_on_webshop;
    }

    public function setAvailableOnWebshop(bool $available_on_webshop): void
    {
        $this->available_on_webshop = $available_on_webshop;
    }

    public function getRetailPrice(): ?float
    {
        return $this->retail_price;
    }

    public function setRetailPrice(?float $retail_price): void
    {
        $this->retail_price = $retail_price;
    }

    /**
     * The price actually charged on the public /shop storefront for this
     * product — $retail_price when actually set (not left at its
     * null/0.00 "never filled in" default), else $product_price
     * ("wholesale"). Single source of truth for this fallback: used
     * identically by both App\Webshop\Catalog\CatalogQueryService::
     * toListing() (what the customer sees before ordering) and
     * App\Api\OrderService::addOrderItem() (what the resulting InvItem is
     * actually billed at) — those two must never diverge, or a customer
     * could be shown one price and charged another.
     */
    public function webshopPrice(): float
    {
        return $this->retail_price !== null && $this->retail_price > 0.00
            ? $this->retail_price
            : ($this->product_price ?? 0.00);
    }

    public function getTradeMinOrderQty(): ?int
    {
        return $this->trade_min_order_qty;
    }

    public function setTradeMinOrderQty(?int $trade_min_order_qty): void
    {
        $this->trade_min_order_qty = $trade_min_order_qty;
    }

    public function getTradeMinOrderSpend(): ?float
    {
        return $this->trade_min_order_spend;
    }

    public function setTradeMinOrderSpend(?float $trade_min_order_spend): void
    {
        $this->trade_min_order_spend = $trade_min_order_spend;
    }

    public function isTrackStock(): bool
    {
        return $this->track_stock;
    }

    public function setTrackStock(bool $track_stock): void
    {
        $this->track_stock = $track_stock;
    }

    // Cache only — see App\Infrastructure\Persistence\StockMovement\StockMovement
    // for the ledger this is derived from. Never write this directly outside
    // of the code path that also writes the corresponding StockMovement row.
    public function getStockQuantity(): float
    {
        return $this->stock_quantity;
    }

    public function setStockQuantity(float $stock_quantity): void
    {
        $this->stock_quantity = $stock_quantity;
    }

    public function getReorderThreshold(): ?float
    {
        return $this->reorder_threshold;
    }

    public function setReorderThreshold(?float $reorder_threshold): void
    {
        $this->reorder_threshold = $reorder_threshold;
    }

    /**
     * Single source of truth for "how much of this product can actually be
     * shown to or bought by the public" — null when stock isn't tracked at
     * all (unlimited, same as today), otherwise stock_quantity minus the
     * reserved reorder_threshold buffer, floored at 0 so a product already
     * inside its own buffer never reads as negative. Every consumer (the
     * storefront listing, cart, and checkout's own authoritative check)
     * calls this one method rather than re-deriving the arithmetic — see
     * $reorder_threshold's own docblock for why the buffer exists.
     */
    public function availableStock(): ?float
    {
        if (!$this->track_stock) {
            return null;
        }
        return max(0.0, $this->stock_quantity - ($this->reorder_threshold ?? 0.0));
    }
}
