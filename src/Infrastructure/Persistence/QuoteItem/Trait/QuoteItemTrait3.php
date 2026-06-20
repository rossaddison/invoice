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
trait QuoteItemTrait3
{

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

    // task can be mutually excluded by product => possible null value
    public function getTaskId(): ?int
    {
        return $this->task_id;
    }

    public function setTaskId(int $task_id): void
    {
        $this->task_id = $task_id;
    }
}
