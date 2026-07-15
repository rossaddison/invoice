<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Infrastructure\Persistence\Product\Product;

interface ProductRepositoryInterface
{
    public function repoProductquery(int $product_id): ?Product;
}
