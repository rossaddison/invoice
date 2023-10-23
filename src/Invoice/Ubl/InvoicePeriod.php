<?php

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class InvoicePeriod implements XmlSerializable
{
    private string $startDate;
    private string $endDate;
    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod
    // https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/
    private string $descriptionCode;
    
    public function __construct(string $startDate, string $endDate, string $descriptionCode) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->descriptionCode = $descriptionCode;
    }

    /**
     * @return string
     */
    public function getStartDate(): string
    {
        return $this->startDate;
    }

    /**
     * @return string
     */
    public function getEndDate(): string
    {
        return $this->endDate;
    }
    
    /**
     * The xmlSerialize method is called during xml writing.
     *
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'StartDate' => $this->startDate ?: '',
        ]);
        
        $writer->write([
            Schema::CBC . 'EndDate' => $this->endDate ?: '',
        ]);
        if ($this->descriptionCode !== '') {  
            $writer->write([
                Schema::CBC . 'DescriptionCode' => $this->descriptionCode ?: '',
            ]);
        }
    }
}
