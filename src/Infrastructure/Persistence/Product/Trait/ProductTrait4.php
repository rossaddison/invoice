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
}
