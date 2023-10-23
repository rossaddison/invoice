<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/** @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-LegalMonetaryTotal/ */
class LegalMonetaryTotal implements XmlSerializable
{
    // Sum of all Invoice line net amounts in the Invoice. Must be rounded to maximum 2 decimals.    
    private float $lineExtensionAmount = 0.00;
    // The total amount of the Invoice without VAT. Must be rounded to maximum 2 decimals.
    private float $taxExclusiveAmount = 0.00;
    // The total amount of the Invoice with VAT. Must be rounded to maximum 2 decimals.
    private float $taxInclusiveAmount = 0.00;
    // Sum of all allowances on document level in the Invoice. Must be rounded to maximum 2 decimals.
    private float $allowanceTotalAmount = 0.00;
    // The outstanding amount that is requested to be paid. Must be rounded to maximum 2 decimals.
    private float $payableAmount = 0.00;
    private string $document_currency = '';
    
    public function __construct(
        float $lineExtensionAmount, 
        float $taxExclusiveAmount, 
        float $taxInclusiveAmount, 
        float $allowanceTotalAmount, 
        float $payableAmount, 
        string $document_currency) 
        {
        $this->lineExtensionAmount = $lineExtensionAmount;
        $this->taxExclusiveAmount = $taxExclusiveAmount;
        $this->taxInclusiveAmount = $taxInclusiveAmount;
        $this->allowanceTotalAmount = $allowanceTotalAmount;
        $this->payableAmount = $payableAmount;
        $this->document_currency = $document_currency;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        
        $writer->write([
            /**
             * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cbc-LineExtensionAmount/
             */
            [
                'name' => Schema::CBC . 'LineExtensionAmount',
                'value' => number_format($this->lineExtensionAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $this->document_currency
                ]

            ],
            [
                'name' => Schema::CBC . 'TaxExclusiveAmount',
                'value' => number_format($this->taxExclusiveAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $this->document_currency
                ]

            ],
            [
                'name' => Schema::CBC . 'TaxInclusiveAmount',
                'value' => number_format($this->taxInclusiveAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $this->document_currency
                ]

            ],
            [
                'name' => Schema::CBC . 'AllowanceTotalAmount',
                'value' => number_format($this->allowanceTotalAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $this->document_currency
                ]

            ],
            [
                'name' => Schema::CBC . 'PayableAmount',
                'value' => number_format($this->payableAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $this->document_currency
                ]
            ],
        ]);
    }
}
