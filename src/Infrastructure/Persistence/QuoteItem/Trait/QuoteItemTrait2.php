<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\QuoteItem\Trait;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use DateTime;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait QuoteItemTrait2
{

    public function setProductId(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function getDateAdded(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_added */
        return $this->date_added;
    }

    public function setDateAdded(DateTime $date_added): void
    {
        $this->date_added = $date_added;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

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

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getProductUnit(): ?string
    {
        return $this->product_unit;
    }
}
