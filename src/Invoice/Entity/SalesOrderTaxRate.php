<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository::class)]

class SalesOrderTaxRate
{
    #[BelongsTo(target: SalesOrder::class, nullable: false, fkAction: 'NO ACTION')]
    private ?SalesOrder $sales_order = null;

    #[BelongsTo(target: TaxRate::class, nullable: false)]
    private ?TaxRate $tax_rate = null;

    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $sales_order_id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $tax_rate_id = null, #[Column(type: 'integer(1)', nullable: false, default: 0)]
        private ?int $include_item_tax = null, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $sales_order_tax_rate_amount = 0.00)
    {
    }

    public function getSalesOrder(): ?SalesOrder
    {
        return $this->sales_order;
    }

    public function setSalesOrder(?SalesOrder $sales_order): void
    {
        $this->sales_order = $sales_order;
    }

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxRate(?TaxRate $tax_rate): void
    {
        $this->tax_rate = $tax_rate;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSalesOrderId(): string
    {
        return (string) $this->sales_order_id;
    }

    public function setSalesOrderId(int $sales_order_id): void
    {
        $this->sales_order_id = $sales_order_id;
    }

    public function getTaxRateId(): string
    {
        return (string) $this->tax_rate_id;
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function getIncludeItemTax(): ?int
    {
        return $this->include_item_tax;
    }

    public function setIncludeItemTax(int $include_item_tax): void
    {
        $this->include_item_tax = $include_item_tax;
    }

    public function getSalesOrderTaxRateAmount(): ?float
    {
        return $this->sales_order_tax_rate_amount;
    }

    public function setSalesOrderTaxRateAmount(float $sales_order_tax_rate_amount): void
    {
        $this->sales_order_tax_rate_amount = $sales_order_tax_rate_amount;
    }
}
