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
trait SalesOrderItemTrait2
{

    public function setPeppolPoItemid(string $peppol_po_itemid): void
    {
        $this->peppol_po_itemid = $peppol_po_itemid;
    }

    public function getPeppolPoLineid(): ?string
    {
        return $this->peppol_po_lineid;
    }

    public function setPeppolPoLineid(string $peppol_po_lineid): void
    {
        $this->peppol_po_lineid = $peppol_po_lineid;
    }

    public function getTaxRateId(): ?int
    {
        return $this->tax_rate_id;
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function reqProductId(): int
    {
        return $this->requireId($this->product_id, 'Product');
    }

    public function setProductId(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function reqTaskId(): int
    {
        return $this->requireId($this->task_id, 'Project');
    }

    public function setTaskId(int $task_id): void
    {
        $this->task_id = $task_id;
    }

    public function getDateAdded(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_added */
        return $this->date_added;
    }

    public function setDateAdded(DateTimeImmutable $date_added): void
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
}
