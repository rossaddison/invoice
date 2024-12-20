<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\Entity\Inv;

#[Entity(repository: \App\Invoice\InvAmount\InvAmountRepository::class)]
class InvAmount
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target:Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $inv_id = null;

    #[Column(type: 'integer(1)', nullable: false, default: 1)]
    private int $sign = 1;

    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private float $item_subtotal = 0.00;

    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private float $item_tax_total = 0.00;

    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $tax_total = 0.00;

    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $total = 0.00;

    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $paid = 0.00;

    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $balance = 0.00;

    public function __construct(
        int $id = null,
        int $inv_id = null,
        int $sign = 1,
        float $item_subtotal = 0.00,
        float $item_tax_total = 0.00,
        float $tax_total = 0.00,
        float $total = 0.00,
        float $paid = 0.00,
        float $balance = 0.00
    ) {
        $this->id = $id;
        $this->inv_id = $inv_id;
        $this->sign = $sign;
        // The sum of all line item's subtotals
        $this->item_subtotal = $item_subtotal;
        // The sum of all line item's tax totals
        $this->item_tax_total = $item_tax_total;
        // The below tax_total is not applicable to VAT based systems and will always be zero in vat based systems
        // Total of additional taxes separate from line item taxes
        $this->tax_total = $tax_total;
        $this->total = $total;
        $this->paid = $paid;
        $this->balance = $balance;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function getId(): string
    {
        return (string)$this->id;
    }

    public function setId(int $id): void
    {
        $this->id =  $id;
    }

    public function getInv_id(): string
    {
        return (string)$this->inv_id;
    }

    public function setInv_id(int $inv_id): void
    {
        $this->inv_id =  $inv_id;
    }

    public function getSign(): int
    {
        return $this->sign;
    }

    public function setSign(int $sign): void
    {
        $this->sign =  $sign;
    }

    public function getItem_subtotal(): float
    {
        return $this->item_subtotal;
    }

    public function setItem_subtotal(float $item_subtotal): void
    {
        $this->item_subtotal =  $item_subtotal;
    }

    public function getItem_tax_total(): float
    {
        return $this->item_tax_total;
    }

    public function setItem_tax_total(float $item_tax_total): void
    {
        $this->item_tax_total =  $item_tax_total;
    }

    public function getTax_total(): float|null
    {
        return $this->tax_total;
    }

    public function setTax_total(float $tax_total): void
    {
        $this->tax_total =  $tax_total;
    }

    public function getTotal(): float|null
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total =  $total;
    }

    public function getPaid(): float|null
    {
        return $this->paid;
    }

    public function setPaid(float $paid): void
    {
        $this->paid =  $paid;
    }

    public function getBalance(): float|null
    {
        return $this->balance;
    }

    public function setBalance(float $balance): void
    {
        $this->balance =  $balance;
    }
}
