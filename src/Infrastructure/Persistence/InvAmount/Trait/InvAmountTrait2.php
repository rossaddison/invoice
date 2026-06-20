<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvAmount\Trait;

use App\Infrastructure\Persistence\Inv\Inv;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvAmountTrait2
{

    public function setPackhandleshipTax(float $packhandleship_tax): void
    {
        $this->packhandleship_tax = $packhandleship_tax;
    }

    public function getTaxTotal(): ?float
    {
        return $this->tax_total;
    }

    public function setTaxTotal(float $tax_total): void
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
