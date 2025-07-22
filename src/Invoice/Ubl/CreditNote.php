<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

class CreditNote extends Invoice
{
    public string $xmlTagName       = 'CreditNote';
    protected ?int $invoiceTypeCode = InvoiceTypeCode::CREDIT_NOTE;

    public function getCreditNoteLines(): ?array
    {
        return $this->invoiceLines;
    }

    public function setCreditNoteLines(array $creditNoteLines): self
    {
        $this->invoiceLines = $creditNoteLines;

        return $this;
    }
}
