<?php
declare(strict_types=1);

namespace App\Invoice\Ubl;

class CreditNoteLine extends InvoiceLine
{
    public string $xmlTagName = 'CreditNoteLine';
    public float $invoicedQuantity; 
    protected bool $isCreditNoteLine = true;
    
    public function __construct(float $invoicedQuantity, bool $isCreditNoteLine) {
        $this->invoicedQuantity = $invoicedQuantity;
        $this->isCreditNoteLine = $isCreditNoteLine;
    }
    
    /**
     * @return float
     */
    public function getCreditedQuantity(): float
    {
        return $this->invoicedQuantity;
    }

    /**
     * @param float $invoicedQuantity
     * @return CreditNoteLine
     */
    public function setCreditedQuantity(float $invoicedQuantity): CreditNoteLine
    {
        $this->invoicedQuantity = $invoicedQuantity;
        return $this;
    }
}
