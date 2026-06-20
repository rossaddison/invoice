<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrderItem\Trait;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\Task\Task;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait SalesOrderItemTrait3
{

    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function setDiscountAmount(float $discount_amount): void
    {
        $this->discount_amount = $discount_amount;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): void
    {
        $this->order = $order;
    }

    public function getProductUnit(): ?string
    {
        return $this->product_unit;
    }

    public function setProductUnit(string $product_unit): void
    {
        $this->product_unit = $product_unit;
    }

    public function getProductUnitId(): ?int
    {
        return $this->product_unit_id;
    }

    public function setProductUnitId(int $product_unit_id): void
    {
        $this->product_unit_id = $product_unit_id;
    }
}
