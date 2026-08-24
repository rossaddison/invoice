<?php

declare(strict_types=1);

namespace App\Webshop\Cart;

final readonly class CartItem
{
    public function __construct(
        public int $productId,
        public string $name,
        public float $price,
        public float $quantity,
    ) {
    }

    public function subtotal(): float
    {
        return $this->price * $this->quantity;
    }
}
