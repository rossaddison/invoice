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
trait SalesOrderItemTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'SalesOrderItem');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxRate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): void
    {
        $this->task = $task;
    }

    public function getSalesOrder(): ?SalesOrder
    {
        return $this->sales_order;
    }

    public function setSalesOrder(?SalesOrder $sales_order): void
    {
        $this->sales_order = $sales_order;
    }

    public function reqSalesOrderId(): int
    {
        return $this->requireId($this->sales_order_id, 'SalesOrder');
    }

    public function setSalesOrderId(int $sales_order_id): void
    {
        $this->sales_order_id = $sales_order_id;
    }

    public function reqQuoteItemId(): int
    {
        return $this->requireId($this->quote_item_id, 'QuoteItem');
    }

    public function setQuoteItemId(int $quote_item_id): void
    {
        $this->quote_item_id = $quote_item_id;
    }

    public function getPeppolPoItemid(): ?string
    {
        return $this->peppol_po_itemid;
    }
}
