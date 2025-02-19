<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PaymentTerms implements XmlSerializable
{
    public function __construct(private readonly ?string $note)
    {
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PaymentTerms
     * @param Writer $writer
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->note !== null) {
            $writer->write([ Schema::CBC . 'Note' => $this->note ]);
        }
    }
}
