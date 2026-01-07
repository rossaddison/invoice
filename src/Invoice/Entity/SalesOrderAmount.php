<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: SalesOrderAmountRepository::class)]
class SalesOrderAmount
{
    #[BelongsTo(target: SalesOrder::class, nullable: true,
                                                         fkAction: 'NO ACTION')]
    private ?SalesOrder $sales_order = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
            
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $sales_order_id = null,
            
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $item_subtotal = 0.00,
            
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $item_tax_total = 0.00,
            
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private float $packhandleship_total = 0.00,
            
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private float $packhandleship_tax = 0.00,
            
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $tax_total = 0.00,
            
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $total = 0.00)
    {
    }

    public function getSales_order(): ?SalesOrder
    {
        return $this->sales_order;
    }

    public function setSales_order(?SalesOrder $sales_order): void
    {
        $this->sales_order = $sales_order;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSales_order_id(): string
    {
        return (string) $this->sales_order_id;
    }

    public function setSales_order_id(int $sales_order_id): void
    {
        $this->sales_order_id = $sales_order_id;
    }

    public function getItem_subtotal(): ?float
    {
        return $this->item_subtotal;
    }

    public function setItem_subtotal(float $item_subtotal): void
    {
        $this->item_subtotal = $item_subtotal;
    }

    public function getItem_tax_total(): ?float
    {
        return $this->item_tax_total;
    }

    public function setItem_tax_total(float $item_tax_total): void
    {
        $this->item_tax_total = $item_tax_total;
    }
    
    public function getPackhandleship_total(): float
    {
        return $this->packhandleship_total;
    }

    public function setPackhandleship_total(float $packhandleship_total): void
    {
        $this->packhandleship_total = $packhandleship_total;
    }

    public function getPackhandleship_tax(): float
    {
        return $this->packhandleship_tax;
    }

    public function setPackhandleship_tax(float $packhandleship_tax): void
    {
        $this->packhandleship_tax = $packhandleship_tax;
    }

    public function getTax_total(): ?float
    {
        return $this->tax_total;
    }

    public function setTax_total(float $tax_total): void
    {
        $this->tax_total = $tax_total;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }
}
