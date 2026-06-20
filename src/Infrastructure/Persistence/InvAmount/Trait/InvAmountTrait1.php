<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvAmount\Trait;

use App\Infrastructure\Persistence\Inv\Inv;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvAmountTrait1
{

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'InvAmount');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function setInvId(int $inv_id): void
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

    public function getItemSubtotal(): float
    {
        return $this->item_subtotal;
    }

    public function setItemSubtotal(float $item_subtotal): void
    {
        $this->item_subtotal = $item_subtotal;
    }

    public function getItemTaxTotal(): float
    {
        return $this->item_tax_total;
    }

    public function setItemTaxTotal(float $item_tax_total): void
    {
        $this->item_tax_total = $item_tax_total;
    }

    // Holds InvAllowanceCharge accumulative totals
    public function getPackhandleshipTotal(): float
    {
        return $this->packhandleship_total;
    }

    public function setPackhandleshipTotal(float $packhandleship_total): void
    {
        $this->packhandleship_total = $packhandleship_total;
    }

    // to the view after adding/deleting/editing an iac
    public function getPackhandleshipTax(): float
    {
        return $this->packhandleship_tax;
    }
}
