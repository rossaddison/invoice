<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

class CreditNoteLine extends InvoiceLine
{
    public string $xmlTagName = 'CreditNoteLine';

    public function __construct(public float $invoicedQuantity, protected bool $isCreditNoteLine)
    {
    }

    public function getCreditedQuantity(): float
    {
        return $this->invoicedQuantity;
    }

    public function setCreditedQuantity(float $invoicedQuantity): self
    {
        $this->invoicedQuantity = $invoicedQuantity;

        return $this;
    }
}
