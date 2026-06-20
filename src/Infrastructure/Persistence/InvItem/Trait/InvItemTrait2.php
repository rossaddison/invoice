<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvItem\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use DateTime;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvItemTrait2
{

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
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

    // product can be mutually excluded by task => possible null value
    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function setProductId(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    // task can be mutually excluded by product => possible null value
    public function getTaskId(): ?int
    {
        return $this->task_id;
    }

    public function setTaskId(int $task_id): void
    {
        $this->task_id = $task_id;
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
}
