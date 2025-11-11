<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\InvAmount\InvAmountRepository::class)]
class InvAmount
{
    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    public function __construct(#[Column(type: 'primary')]
        private ?int $id = null, #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null, #[Column(type: 'integer(1)', nullable: false, default: 1)]
        private int $sign = 1, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        /** Note the $item_subtotal is a figure after item discount has been subtracted */
        private float $item_subtotal = 0.00, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private float $item_tax_total = 0.00, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private float $packhandleship_total = 0.00, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private float $packhandleship_tax = 0.00, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $tax_total = 0.00, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        /** Note the $total is calculated after taking into account invoice discount i.e. $inv->getDiscount */
        private ?float $total = 0.00, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $paid = 0.00, #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $balance = 0.00) {}

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getInv_id(): string
    {
        return (string) $this->inv_id;
    }

    public function setInv_id(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getSign(): int
    {
        return $this->sign;
    }

    public function setSign(int $sign): void
    {
        $this->sign = $sign;
    }

    public function getItem_subtotal(): float
    {
        return $this->item_subtotal;
    }

    public function setItem_subtotal(float $item_subtotal): void
    {
        $this->item_subtotal = $item_subtotal;
    }

    public function getItem_tax_total(): float
    {
        return $this->item_tax_total;
    }

    public function setItem_tax_total(float $item_tax_total): void
    {
        $this->item_tax_total = $item_tax_total;
    }

    // Holds InvAllowanceCharge accumulative totals
    public function getPackhandleship_total(): float
    {
        return $this->packhandleship_total;
    }

    public function setPackhandleship_total(float $packhandleship_total): void
    {
        $this->packhandleship_total = $packhandleship_total;
    }

    // Holds InvAllowanceCharge accumulative tax totals
    // See src/Invoice/Helpers/NumberHelper function calculate_inv
    // which recalculates this total when the invoice is redirected
    // to the view after adding/deleting/editing an iac
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

    public function getPaid(): ?float
    {
        return $this->paid;
    }

    public function setPaid(float $paid): void
    {
        $this->paid = $paid;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }
}
