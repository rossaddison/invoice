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
}
