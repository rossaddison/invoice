<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

use InvalidArgumentException;

class TaxTotal implements XmlSerializable
{
    private array $doc_and_or_supp_currency_tax = [];
    
    public function __construct(array $doc_and_or_supp_currency_tax) {
        $this->doc_and_or_supp_currency_tax = $doc_and_or_supp_currency_tax;
    }

    /**
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate() : void
    {
        if (empty($this->doc_and_or_supp_currency_tax)) {
            throw new InvalidArgumentException('Missing taxtotal taxamount');
        }
    }

    /**
     * @see PeppolHelper/TaxAmounts function
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();
        $tst = $this->doc_and_or_supp_currency_tax;
        /**
         * @var float $tst['supp_tax_cc_tax_amount']
         */
        $supp_tax_cc_tax_amount = $tst['supp_tax_cc_tax_amount'] ?: 0.00;
        /**
         * @var float $tst['doc_cc_tax_amount']
         */
        $doc_cc_tax_amount = $tst['doc_cc_tax_amount'] ?: 0.00;
        /**
         * @var string $tst['supp_tax_cc']
         */
        $supp_cc = $tst['supp_tax_cc'] ?? '';
        /**
         * @var string $tst['doc_cc']
         */
        $doc_cc = $tst['doc_cc'] ?? '';
        
        // One Instance of Tax Total provided because 
        // Document has same currency code as Supplier
        if ($doc_cc === $supp_cc) {
        $writer->write(
          [
            'name' => Schema::CBC . 'TaxAmount',
            'value' => number_format($supp_tax_cc_tax_amount ?: 0.00, 2, '.', ''),
            'attributes' => [
                'currencyID' => $supp_cc,
            ]
          ],
        );
        
        // The suppliers currency is different to the document's currency
        } else {
        // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-TaxTotal/
        // Suppliers Tax Amount in Suppliers Currency without subtotal breakdown
        $writer->write([
          'name' => Schema::CBC . 'TaxAmount',
          'value' => number_format((float)(string)$supp_tax_cc_tax_amount ?: 0.00, 2, '.', ''),
          'attributes' => [
              'currencyID' => $supp_cc
          ],
        ]);
        // Document Recipients TaxAmount in Document Recipient's Currency
        $writer->write([
          [
            'name' => Schema::CBC . 'TaxAmount',
            'value' => number_format((float)(string)$doc_cc_tax_amount ?: 0.00, 2, '.', ''),
            'attributes' => [
                'currencyID' => $doc_cc
            ]
          ],
        ]);
        } // elseif
    } //xmlserialize
}
