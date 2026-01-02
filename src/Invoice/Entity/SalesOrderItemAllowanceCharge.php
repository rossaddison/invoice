<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as SOIACR;

#[Entity(repository: SOIACR::class)]
class SalesOrderItemAllowanceCharge
{
    #[BelongsTo(target: AllowanceCharge::class, nullable: false, fkAction: 'NO ACTION')]
    private ?AllowanceCharge $allowance_charge = null;

    #[BelongsTo(target: SalesOrderItem::class, nullable: false, fkAction: 'NO ACTION')]
    private ?SalesOrderItem $sales_order_item = null;

    #[BelongsTo(target: SalesOrder::class, nullable: false, fkAction: 'NO ACTION')]
    private ?SalesOrder $sales_order = null;

    public function __construct(
        #[Column(type: 'primary')]
            private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
            private ?int $sales_order_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
            private ?int $sales_order_item_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
            private ?int $allowance_charge_id = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
            private ?float $amount = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
            private ?float $vat_or_tax = null)
    {
    }

    public function getAllowanceCharge(): ?AllowanceCharge
    {
        return $this->allowance_charge;
    }

    public function setAllowanceCharge(?AllowanceCharge $allowance_charge): void
    {
        $this->allowance_charge = $allowance_charge;
    }

    public function getSalesOrder(): ?SalesOrder
    {
        return $this->sales_order;
    }

    public function setSalesOrder(?SalesOrder $salesorder): void
    {
        $this->sales_order = $salesorder;
    }

    public function getSalesOrderItem(): ?SalesOrderItem
    {
        return $this->sales_order_item;
    }

    public function setSalesOrderItem(?SalesOrderItem $salesorder_item): void
    {
        $this->sales_order_item = $salesorder_item;
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

    public function getSales_order_item_id(): string
    {
        return (string) $this->sales_order_item_id;
    }

    public function setSales_order_item_id(int $sales_order_item_id): void
    {
        $this->sales_order_item_id = $sales_order_item_id;
    }

    public function getAllowance_charge_id(): string
    {
        return (string) $this->allowance_charge_id;
    }

    public function setAllowance_charge_id(int $allowance_charge_id): void
    {
        $this->allowance_charge_id = $allowance_charge_id;
    }

    public function getAmount(): string
    {
        return (string) $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getVatOrTax(): string
    {
        return (string) $this->vat_or_tax;
    }

    public function setVatOrTax(float $vatOrTax): void
    {
        $this->vat_or_tax = $vatOrTax;
    }

    public function nullifyRelationOnChange(int $allowance_charge_id,
        int $sales_order_item_id, int $sales_order_id): void
    {
        if ($this->allowance_charge_id != $allowance_charge_id) {
            $this->allowance_charge = null;
        }
        if ($this->sales_order_item_id != $sales_order_item_id) {
            $this->sales_order_item = null;
        }
        if ($this->sales_order_id != $sales_order_id) {
            $this->sales_order = null;
        }
    }
}
