<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

class CreditNote extends Invoice
{
    public string $xmlTagName = 'CreditNote';
    protected ?int $invoiceTypeCode = InvoiceTypeCode::CREDIT_NOTE;

    /**
     * 
     * @return array|null
     */
    public function getCreditNoteLines(): ?array
    {
        return $this->invoiceLines;
    }

    /**
     * 
     * @param array $creditNoteLines
     * @return CreditNote
     */
    public function setCreditNoteLines(array $creditNoteLines): CreditNote
    {
        $this->invoiceLines = $creditNoteLines;
        return $this;
    }
}
