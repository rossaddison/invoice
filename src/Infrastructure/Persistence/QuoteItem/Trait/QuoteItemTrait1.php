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
trait QuoteItemTrait1
{

    //relation $tax_rate
    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    //set relation $taxrate
    public function setTaxRate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    //set relation $product
    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    //set relation $product
    public function setQuote(?Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    //set relation $task
    public function setTask(?Task $task): void
    {
        $this->task = $task;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'QuoteItem');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqQuoteId(): int
    {
        return $this->requireId($this->quote_id, 'Quote');
    }

    public function setQuoteId(int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function reqTaxRateId(): int
    {
        return $this->requireId($this->tax_rate_id, 'TaxRate');
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    // product can be mutually excluded by task => possible null value
    public function getProductId(): ?int
    {
        return $this->product_id;
    }
}
