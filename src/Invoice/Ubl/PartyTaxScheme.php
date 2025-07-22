<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PartyTaxScheme implements XmlSerializable
{
    public function __construct(private readonly string $companyId, private readonly TaxScheme $taxScheme)
    {
    }

    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    public function getTaxScheme(): TaxScheme
    {
        return $this->taxScheme;
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PartyTaxScheme
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            'name'  => Schema::CBC.'CompanyID',
            'value' => $this->companyId,
        ]);
        $this->taxScheme->xmlSerialize($writer);
    }
}
